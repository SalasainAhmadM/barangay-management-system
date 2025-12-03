<?php
// ========== update_request_status.php ==========
session_start();
require_once("../../conn/conn.php");

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/php_errors.log');

header('Content-Type: application/json');

// Log request for debugging
error_log("=== Update Request Status Called ===");
error_log("POST data: " . file_get_contents('php://input'));

if (!isset($_SESSION["admin_id"])) {
    error_log("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION["admin_id"];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$requestId = (int) $input['request_id'];
$status = trim($input['status']);
// Handle empty string as null for payment status
$paymentStatus = isset($input['payment_status']) && trim($input['payment_status']) !== '' 
    ? trim($input['payment_status']) 
    : null;
$rejectionReason = trim($input['rejection_reason'] ?? '');
$notes = trim($input['notes'] ?? '');

// Validate status
$validStatuses = ['pending', 'processing', 'approved', 'ready', 'completed', 'rejected', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Validate payment status if provided
if ($paymentStatus !== null && !in_array($paymentStatus, ['paid', 'unpaid'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment status']);
    exit();
}

// If rejected, rejection reason is required
if ($status === 'rejected' && empty($rejectionReason)) {
    echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
    exit();
}

try {
    $conn->begin_transaction();

    // Get complete document info for logging and PDF generation
    $info_stmt = $conn->prepare("
        SELECT 
            dr.id,
            dr.user_id,
            dr.request_id,
            dr.purpose,
            dr.submitted_date,
            dr.serial_number,
            dr.payment_status as current_payment_status,
            dt.name AS document_name,
            dt.type AS document_type,
            dt.fee,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            CONCAT(
                COALESCE(u.house_number, ''), ' ',
                COALESCE(u.street_name, ''), ', ',
                COALESCE(u.barangay, '')
            ) as address
        FROM document_requests dr
        INNER JOIN document_types dt ON dr.document_type_id = dt.id
        INNER JOIN user u ON dr.user_id = u.id
        WHERE dr.id = ?
    ");
    $info_stmt->bind_param("i", $requestId);
    $info_stmt->execute();
    $result = $info_stmt->get_result();
    $requestInfo = $result->fetch_assoc();
    $info_stmt->close();

    if (!$requestInfo) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }

    // Additional validation: Check payment status for ready/completed status
    $hasFee = floatval($requestInfo['fee']) > 0;
    if ($hasFee && in_array($status, ['ready', 'completed'])) {
        // Determine effective payment status: use new status if provided, otherwise use current
        $effectivePaymentStatus = ($paymentStatus !== null && $paymentStatus !== '') 
            ? $paymentStatus 
            : $requestInfo['current_payment_status'];
        
        if ($effectivePaymentStatus !== 'paid') {
            echo json_encode([
                'success' => false, 
                'message' => "Cannot set status to '$status' when payment is unpaid. Please mark as paid first."
            ]);
            exit();
        }
    }

    // Prepare update query
    $updateFields = "status = ?, updated_at = NOW()";
    $params = [$status];
    $types = "s";

    // Update payment status if provided and has fee
    if ($hasFee && $paymentStatus !== null && $paymentStatus !== '') {
        $updateFields .= ", payment_status = ?";
        $params[] = $paymentStatus;
        $types .= "s";

        // Add payment date if marking as paid
        if ($paymentStatus === 'paid') {
            $updateFields .= ", payment_date = NOW()";
        }
    }

    // Generate serial number for approved, ready, or completed status (if not already generated)
    if (in_array($status, ['approved', 'ready', 'completed']) && empty($requestInfo['serial_number'])) {
        $serialNumber = generateSerialNumber($conn);
        $updateFields .= ", serial_number = ?";
        $params[] = $serialNumber;
        $types .= "s";
        
        // ✅ Update requestInfo with new serial number for PDF generation
        $requestInfo['serial_number'] = $serialNumber;
    }

    // Add date_issued for ready or completed status
    if (in_array($status, ['ready', 'completed'])) {
        $updateFields .= ", date_issued = CURDATE()";
    }

    // Add date fields based on status
    if ($status === 'approved') {
        $updateFields .= ", approved_date = NOW()";
    } elseif ($status === 'completed') {
        $updateFields .= ", released_date = NOW()";
    }

    // Add rejection reason if rejected
    if ($status === 'rejected' && !empty($rejectionReason)) {
        $updateFields .= ", rejection_reason = ?";
        $params[] = $rejectionReason;
        $types .= "s";
    }

    // Add notes if provided
    if (!empty($notes)) {
        $updateFields .= ", notes = ?";
        $params[] = $notes;
        $types .= "s";
    }

    // Generate PDF if status is 'ready' or 'completed' AND payment is paid (or no fee)
    $pdfFilename = null;
    if (in_array($status, ['ready', 'completed'])) {
        $effectivePaymentStatus = ($paymentStatus !== null && $paymentStatus !== '') 
            ? $paymentStatus 
            : $requestInfo['current_payment_status'];
            
        error_log("Attempting PDF generation for status: {$status}, payment: {$effectivePaymentStatus}, hasFee: " . ($hasFee ? 'yes' : 'no'));
            
        if (!$hasFee || $effectivePaymentStatus === 'paid') {
            try {
                $pdfFilename = generateDocumentPDF($requestInfo);
                
                if ($pdfFilename) {
                    error_log("PDF generated successfully: {$pdfFilename}");
                    $updateFields .= ", document_file = ?";
                    $params[] = $pdfFilename;
                    $types .= "s";
                } else {
                    error_log("PDF generation returned false/null");
                }
            } catch (Exception $pdfError) {
                error_log("PDF generation error: " . $pdfError->getMessage());
                // Continue with update even if PDF fails
            }
        }
    }

    // Add WHERE clause
    $params[] = $requestId;
    $types .= "i";

    // Log the query for debugging
    error_log("Update query: UPDATE document_requests SET {$updateFields} WHERE id = ?");
    error_log("Param types: {$types}");
    error_log("Params: " . print_r($params, true));

    // Execute update
    $stmt = $conn->prepare("UPDATE document_requests SET $updateFields WHERE id = ?");
    
    if (!$stmt) {
        $conn->rollback();
        error_log("Failed to prepare statement: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement']);
        exit();
    }
    
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        error_log("Statement executed. Affected rows: " . $stmt->affected_rows);
        
        if ($stmt->affected_rows > 0) {
            // ✅ Log admin activity
            $activity = "Updated document request status";
            $docName = $requestInfo['document_name'] ?? 'Unknown document';

            // Build readable description
            $description = "Updated the status of request ID {$requestId} for '{$docName}' to '{$status}'.";

            if (isset($serialNumber)) {
                $description .= " Serial Number: {$serialNumber}.";
            }

            if ($paymentStatus !== null && $paymentStatus !== '') {
                $description .= " Payment Status: {$paymentStatus}.";
                if ($paymentStatus === 'paid') {
                    $description .= " Payment marked as paid on " . date('Y-m-d H:i:s') . ".";
                }
            }

            if ($pdfFilename) {
                $description .= " Document file generated: {$pdfFilename}.";
            }

            if (!empty($notes)) {
                $description .= " Notes: {$notes}.";
            }

            if ($status === 'rejected' && !empty($rejectionReason)) {
                $description .= " Rejection reason: {$rejectionReason}.";
            }

            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (activity, description, created_at)
                VALUES (?, ?, NOW())
            ");
            $log_stmt->bind_param("ss", $activity, $description);
            $log_stmt->execute();
            $log_stmt->close();

            $conn->commit();
            
            // Prepare success message
            $successMessage = 'Status updated successfully';
            $warnings = [];
            
            if (in_array($status, ['ready', 'completed']) && $pdfFilename === null) {
                $warnings[] = 'Note: PDF generation skipped. TCPDF library not installed.';
                error_log("WARNING: Status updated but PDF not generated. Install TCPDF to enable PDF generation.");
            }
            
            echo json_encode([
                'success' => true,
                'message' => $successMessage,
                'warnings' => $warnings,
                'pdf_generated' => ($pdfFilename !== null),
                'serial_number' => $serialNumber ?? null
            ]);
        } else {
            $conn->rollback();
            error_log("No rows affected by update");
            echo json_encode(['success' => false, 'message' => 'Request not found or no changes made']);
        }
    } else {
        $conn->rollback();
        error_log("Failed to execute statement: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Exception in update_request_status: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}

/**
 * Generate a unique serial number for the document
 * Format: BRG-YYYY-XXXXX (e.g., BRG-2025-00001)
 */
function generateSerialNumber($conn)
{
    $year = date('Y');
    $prefix = "BRG-{$year}-";

    // Get the last serial number for this year
    $stmt = $conn->prepare("
        SELECT serial_number 
        FROM document_requests 
        WHERE serial_number LIKE ? 
        ORDER BY serial_number DESC 
        LIMIT 1
    ");
    $pattern = $prefix . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Extract the number part and increment
        $lastNumber = intval(substr($row['serial_number'], -5));
        $newNumber = $lastNumber + 1;
    } else {
        // First serial number of the year
        $newNumber = 1;
    }

    $stmt->close();

    // Format: BRG-2025-00001
    return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate PDF document for certificate or permit
 * @param array $requestInfo - Request information from database
 * @return string|false - Returns filename on success, false on failure
 */
function generateDocumentPDF($requestInfo)
{
    try {
        // Check if TCPDF is available
        $autoloadPath = '../../vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            error_log("TCPDF not found at: {$autoloadPath}. Skipping PDF generation.");
            error_log("Please install TCPDF via Composer: composer require tecnickcom/tcpdf");
            return false;
        }
        
        // Load TCPDF
        require_once($autoloadPath);
        
        // Verify TCPDF class is available
        if (!class_exists('TCPDF')) {
            error_log("TCPDF class not found after autoload. Check installation.");
            return false;
        }

        // Get absolute path to upload directory
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/document_requests/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename using request_id
        $filename = $requestInfo['request_id'] . ".pdf";
        $filePath = $uploadDir . $filename;

        // Verify the directory is writable
        if (!is_writable($uploadDir)) {
            error_log("Directory not writable: " . $uploadDir);
            return false;
        }

        // Create PDF using TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Barangay Management System');
        $pdf->SetAuthor('Barangay Office');
        $pdf->SetTitle($requestInfo['document_name']);
        $pdf->SetSubject($requestInfo['document_type']);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(25, 25, 25);
        $pdf->SetAutoPageBreak(true, 25);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 11);

        // Generate HTML content
        $html = generateDocumentHTML($requestInfo);

        // Write HTML to PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Save PDF to file using absolute path
        $pdf->Output($filePath, 'F');

        // Verify file was created
        if (!file_exists($filePath)) {
            error_log("PDF file was not created: " . $filePath);
            return false;
        }

        error_log("PDF successfully created: " . $filePath);
        return $filename; // Return only filename, not full path

    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * Generate HTML content for the PDF document
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateDocumentHTML($requestInfo)
{
    $docName = strtolower($requestInfo['document_name']);
    if (strpos($docName, 'indigency') !== false) {
        return generateIndigencyCertificateHTML($requestInfo);
    } elseif (strpos($docName, 'residency') !== false) {
        return generateResidencyCertificateHTML($requestInfo);
    } elseif (strpos($docName, 'clearance') !== false) {
        return generateClearanceCertificateHTML($requestInfo);
    } elseif (strpos($docName, 'good moral') !== false || strpos($docName, 'moral character') !== false) {
        return generateGoodMoralCertificateHTML($requestInfo);
    } else {
        return generateDefaultCertificateHTML($requestInfo);
    }
}

/** 
 * Generate HTML content specifically for Barangay Clearance 
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateClearanceCertificateHTML($requestInfo)
{
    $fullName = trim($requestInfo['first_name'] . ' ' . ($requestInfo['middle_name'] ?? '') . ' ' . $requestInfo['last_name']);
    $address = $requestInfo['address'];
    $purpose = $requestInfo['purpose'];
    $day = date('j');
    $month = date('F');
    $year = date('Y');
    $serialNumber = $requestInfo['serial_number'] ?? 'N/A';
    
    // Calculate validity period (6 months from issue date)
    $issueDate = new DateTime();
    $validUntil = clone $issueDate;
    $validUntil->modify('+6 months');
    $validUntilFormatted = $validUntil->format('F d, Y');

    // Image paths (ensure these exist in tcpdf/images/)
    $headerLogo = K_PATH_IMAGES . 'barangaycouncil.png';
    $footerLogo = K_PATH_IMAGES . 'logocabatoadmin.png';

    $html = '
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .serial-number {
            text-align: right;
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .content {
            margin: 0 40px;
            text-align: justify;
            line-height: 1.8;
        }
        .indent {
            text-indent: 50px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 70px;
        }
        .signature .name {
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .signature .position {
            font-weight: bold;
        }
    </style>

    <table cellspacing="0" cellpadding="0" style="width:100%;">
        <tr>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $headerLogo . '" width="70">
            </td>
            <td style="width:60%; text-align:center; line-height:1.5;">
                <span>Republic of the Philippines</span><br>
                <b>OFFICE OF THE BARANGAY COUNCIL</b><br>
                Baliwasan, Zamboanga City<br>
                <a href="https://www.facebook.com/barangaybaliwasan" style="text-decoration:none; color:black;">
                    www.facebook.com/barangaybaliwasan
                </a>
            </td>
        </tr>
    </table>
    <br>
    <hr style="border: 2px solid black; width: 100%; margin: 0 auto;">
    <br>
    
    <div class="serial-number">Serial No.: <b>' . $serialNumber . '</b></div>

    <div class="title">BARANGAY CLEARANCE</div>

    <div class="content">
        <p class="indent">
            This is to certify that <b><u>' . strtoupper(htmlspecialchars($fullName)) . '</u></b>
            is a bonafide resident of <b><u>' . htmlspecialchars($address) . '</u></b>.
        </p>

        <p class="indent">
            This further certifies that the above-mentioned person is of good moral
            standing without any derogatory record as far as this office is concern.
        </p>

        <p class="indent">
            This certification is being issued upon request for <b><u>' . strtoupper(htmlspecialchars($purpose)) . '</u></b> requirements.
        </p>

        <p class="indent" style="margin-top: 20px;">
            Issued this <b><u>' . $day . ' day of ' . $month . ' ' . $year . '</u></b>
            at Barangay Baliwasan, Zamboanga City.
        </p>
    </div>

    <div class="signature">
        <b class="name">HON. MA. JAMIELY CZARINA N. CABATO</b><br>
        <b class="position">Punong Barangay</b>
    </div>
    
    <br><br>
    
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top: 20px;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:middle; font-size:8pt; color:#666;">
                <i>Not valid without official seal</i>
            </td>
            <td style="width:50%; text-align:right; vertical-align:middle; font-size:8pt; color:#666;">
                <b>Valid until: ' . $validUntilFormatted . '</b>
            </td>
        </tr>
    </table>
    
    <br>
    <table cellspacing="0" cellpadding="0" style="width:100%; position:absolute; bottom:20px; left:25px; right:25px;">
        <tr>
            <td style="width:80%; text-align:center; vertical-align:middle; font-size:8pt; line-height:1.3;">
                <b>Baliwasan Barangay Hall San Jose Road corner Baliwasan Chico Barangay Hall</b><br>
                Zamboanga City, Philippines | facebook.com/barangaybaliwasanofficeofthepunongbarangay<br>
                992-6211 | 926-2639
            </td>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $footerLogo . '" width="70">
            </td>
        </tr>
    </table>';

    return $html;
}

/**
 * Generate HTML content specifically for Good Moral Character Certificate
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateGoodMoralCertificateHTML($requestInfo)
{
    $fullName = trim($requestInfo['first_name'] . ' ' . ($requestInfo['middle_name'] ?? '') . ' ' . $requestInfo['last_name']);
    $address = $requestInfo['address'];
    $purpose = $requestInfo['purpose'];
    $day = date('j');
    $month = date('F');
    $year = date('Y');
    $serialNumber = $requestInfo['serial_number'] ?? 'N/A';
    
    // Calculate validity period (6 months from issue date)
    $issueDate = new DateTime();
    $validUntil = clone $issueDate;
    $validUntil->modify('+6 months');
    $validUntilFormatted = $validUntil->format('F d, Y');

    // Image paths
    $headerLogo = K_PATH_IMAGES . 'barangaycouncil.png';
    $footerLogo = K_PATH_IMAGES . 'logocabatoadmin.png';

    $html = '
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .serial-number {
            text-align: right;
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            text-decoration: underline;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .content {
            margin: 0 40px;
            text-align: justify;
            line-height: 1.8;
        }
        .indent {
            text-indent: 50px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 70px;
        }
        .signature .name {
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .signature .position {
            font-weight: bold;
        }
    </style>

    <table cellspacing="0" cellpadding="0" style="width:100%;">
        <tr>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $headerLogo . '" width="70">
            </td>
            <td style="width:60%; text-align:center; line-height:1.5;">
                <span>Republic of the Philippines</span><br>
                <b>OFFICE OF THE BARANGAY COUNCIL</b><br>
                Baliwasan, Zamboanga City<br>
                <a href="https://www.facebook.com/barangaybaliwasan" style="text-decoration:none; color:black;">
                    www.facebook.com/barangaybaliwasan
                </a>
            </td>
        </tr>
    </table>
    <br>
    <hr style="border: 2px solid black; width: 100%; margin: 0 auto;">
    <br>
    
    <div class="serial-number">Serial No.: <b>' . $serialNumber . '</b></div>

    <div class="title">CERTIFICATE OF GOOD MORAL CHARACTER</div>

    <div class="content">
        <p class="indent">
            This is to certify that <b><u>' . strtoupper(htmlspecialchars($fullName)) . '</u></b>
            is a bonafide resident of <b><u>' . htmlspecialchars($address) . '</u></b>.
        </p>

        <p class="indent">
            This further certifies that the above-mentioned person is of good moral character 
            and has no derogatory record in the barangay as far as this office is concerned.
        </p>

        <p class="indent">
            This certification is being issued upon request for <b><u>' . strtoupper(htmlspecialchars($purpose)) . '</u></b> requirements.
        </p>

        <p class="indent" style="margin-top: 20px;">
            Issued this <b><u>' . $day . ' day of ' . $month . ' ' . $year . '</u></b>
            at Barangay Baliwasan, Zamboanga City.
        </p>
    </div>

    <div class="signature">
        <b class="name">HON. MA. JAMIELY CZARINA N. CABATO</b><br>
        <b class="position">Punong Barangay</b>
    </div>
    
    <br><br>
    
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top: 20px;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:middle; font-size:8pt; color:#666;">
                <i>Not valid without official seal</i>
            </td>
            <td style="width:50%; text-align:right; vertical-align:middle; font-size:8pt; color:#666;">
                <b>Valid until: ' . $validUntilFormatted . '</b>
            </td>
        </tr>
    </table>
    
    <br>
    <table cellspacing="0" cellpadding="0" style="width:100%; position:absolute; bottom:20px; left:25px; right:25px;">
        <tr>
            <td style="width:80%; text-align:center; vertical-align:middle; font-size:8pt; line-height:1.3;">
                <b>Baliwasan Barangay Hall San Jose Road corner Baliwasan Chico Barangay Hall</b><br>
                Zamboanga City, Philippines | facebook.com/barangaybaliwasanofficeofthepunongbarangay<br>
                992-6211 | 926-2639
            </td>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $footerLogo . '" width="70">
            </td>
        </tr>
    </table>';

    return $html;
}

/**
 * Generate HTML content specifically for Certificate of Residency
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateResidencyCertificateHTML($requestInfo)
{
    $fullName = trim($requestInfo['first_name'] . ' ' . ($requestInfo['middle_name'] ?? '') . ' ' . $requestInfo['last_name']);
    $address = $requestInfo['address'];
    $purpose = $requestInfo['purpose'];
    $day = date('j');
    $month = date('F');
    $year = date('Y');
    $serialNumber = $requestInfo['serial_number'] ?? 'N/A';
    
    // Calculate validity period (6 months from issue date)
    $issueDate = new DateTime();
    $validUntil = clone $issueDate;
    $validUntil->modify('+6 months');
    $validUntilFormatted = $validUntil->format('F d, Y');

    // Paths for images
    $headerLogo = K_PATH_IMAGES . 'barangaycouncil.png';
    $footerLogo = K_PATH_IMAGES . 'logocabatoadmin.png';

    $html = '
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .serial-number {
            text-align: right;
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 30px 0 15px 0;
            text-decoration: underline;
        }
        .content {
            margin: 0 30px;
            text-align: justify;
            line-height: 1.8;
        }
        .to-whom {
            font-weight: bold;
            margin-bottom: 15px;
        }
        .indent {
            text-indent: 50px;
        }
        .signature {
            margin-top: 45px;
            text-align: right;
            padding-right: 60px;
        }
        .signature .name {
            font-weight: bold;
            text-transform: uppercase;
        }
        .signature .position {
            font-weight: bold;
        }
    </style>

    <table cellspacing="0" cellpadding="0" style="width:100%;">
        <tr>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $headerLogo . '" width="70">
            </td>
            <td style="width:60%; text-align:center; line-height:1.4;">
                <span>Republic of the Philippines</span><br>
                <b>OFFICE OF THE BARANGAY COUNCIL</b><br>
                Baliwasan, Zamboanga City<br>
                <a href="https://www.facebook.com/barangaybaliwasan" style="text-decoration:none; color:black;">
                    www.facebook.com/barangaybaliwasan
                </a>
            </td>
            <td style="width:20%;"></td>
        </tr>
    </table>

    <br>
    <hr style="border: 2px solid black; width: 100%; margin: 0 auto;">
    <br>
    
    <div class="serial-number">Serial No.: <b>' . $serialNumber . '</b></div>

    <div class="title">CERTIFICATION OF RESIDENCY</div>

    <div class="content">
        <div class="to-whom">TO WHOM IT MAY CONCERN:</div>

        <p class="indent">
            This is to certify that <b><u>' . strtoupper(htmlspecialchars($fullName)) . '</u></b>
            is a bonafide resident of <b><u>' . htmlspecialchars($address) . '</u></b>.
        </p>

        <p class="indent">
            This further certifies that the above-mentioned person is a native resident of this barangay.
        </p>

        <p class="indent">
            This certification is being issued upon request for <b><u>' . strtoupper(htmlspecialchars($purpose)) . '</u></b>.
        </p>

        <p class="indent" style="margin-top: 20px;">
            Issued this <b><u>' . $day . ' day of ' . $month . ' ' . $year . '</u></b> 
            at Barangay Baliwasan, Zamboanga City.
        </p>
    </div>

    <div class="signature">
        <b class="name" style="text-decoration: underline;">HON. MA. JEMIELY CZARINA L. CABATO</b><br>
        <b class="position">Punong Barangay</b>
    </div>
    
    <br><br>
    
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top: 20px;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:middle; font-size:8pt; color:#666;">
                <i>Not valid without official seal</i>
            </td>
            <td style="width:50%; text-align:right; vertical-align:middle; font-size:8pt; color:#666;">
                <b>Valid until: ' . $validUntilFormatted . '</b>
            </td>
        </tr>
    </table>

    <table cellspacing="0" cellpadding="0" style="width:100%; position:absolute; bottom:20px; left:25px; right:25px;">
        <tr>
            <td style="width:80%; text-align:center; vertical-align:middle; font-size:8pt; line-height:1.3;">
                <b>Baliwasan Barangay Hall San Jose Road corner Baliwasan Chico Barangay Hall</b><br>
                Zamboanga City, Philippines | facebook.com/barangaybaliwasanofficeofthepunongbarangay<br>
                992-6211 | 926-2639
            </td>
            <td style="width:20%; text-align:left; vertical-align:middle; padding-left:10px;">
                <img src="' . $footerLogo . '" width="70">
            </td>
        </tr>
    </table>';

    return $html;
}

/**
 * Generate HTML content specifically for Certificate of Indigency
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateIndigencyCertificateHTML($requestInfo)
{
    $fullName = trim($requestInfo['first_name'] . ' ' . ($requestInfo['middle_name'] ?? '') . ' ' . $requestInfo['last_name']);
    $address = $requestInfo['address'];
    $purpose = $requestInfo['purpose'];
    $day = date('j');
    $month = date('F');
    $year = date('Y');
    $serialNumber = $requestInfo['serial_number'] ?? 'N/A';
    
    // Calculate validity period (6 months from issue date)
    $issueDate = new DateTime();
    $validUntil = clone $issueDate;
    $validUntil->modify('+6 months');
    $validUntilFormatted = $validUntil->format('F d, Y');

    $imagePath = K_PATH_IMAGES . 'barangaycouncil.png';

    $html = '
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .serial-number {
            text-align: right;
            font-size: 10pt;
            color: #666;
            margin-bottom: 10px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 30px 0 15px 0;
            text-decoration: underline;
        }
        .content {
            margin: 0 30px;
            text-align: justify;
            line-height: 1.8;
        }
        .to-whom {
            font-weight: bold;
            margin-bottom: 15px;
        }
        .indent {
            text-indent: 50px;
        }
        .signature {
            margin-top: 45px;
            text-align: right;
            padding-right: 60px;
        }
        .signature .name {
            font-weight: bold;
            text-transform: uppercase;
        }
        .signature .position {
            font-weight: bold;
        }
    </style>

    <table cellspacing="0" cellpadding="0" style="width:100%;">
        <tr>
            <td style="width:20%; text-align:right; vertical-align:middle;">
                <img src="' . $imagePath . '" width="70">
            </td>
            <td style="width:60%; text-align:center; line-height:1.4;">
                <span>Republic of the Philippines</span><br>
                <b>OFFICE OF THE BARANGAY COUNCIL</b><br>
                Baliwasan, Zamboanga City<br>
                <a href="https://www.facebook.com/barangaybaliwasan" style="text-decoration:none; color:black;">
                    www.facebook.com/barangaybaliwasan
                </a>
            </td>
            <td style="width:20%;"></td>
        </tr>
    </table>

    <br>
    <hr style="border: 2px solid black; width: 100%; margin: 0 auto;">
    <br>
    
    <div class="serial-number">Serial No.: <b>' . $serialNumber . '</b></div>

    <div class="title">CERTIFICATE OF INDIGENCY</div>

    <div class="content">
        <div class="to-whom">TO WHOM IT MAY CONCERN:</div>

        <p class="indent">
            This is to certify that <b><u>' . strtoupper(htmlspecialchars($fullName)) . '</u></b> 
            is a bonafide resident of <b><u>' . htmlspecialchars($address) . '</u></b>.
        </p>

        <p class="indent">
            This further certifies that the above-mentioned person belongs to the indigent sector of the community.
        </p>

        <p class="indent">
            This certification is being issued upon request for <b><u>' . strtoupper(htmlspecialchars($purpose)) . '</u></b>.
        </p>

        <p class="indent" style="margin-top: 20px;">
            Issued this <b><u>' . $day . ' day of ' . $month . ' ' . $year . '</u></b> 
            at Barangay Baliwasan, Zamboanga City.
        </p>
    </div>

    <div class="signature">
        <b class="name" style="text-decoration: underline;">HON. MA. JEMIELY CZARINA L. CABATO</b><br>
        <b class="position">Punong Barangay</b>
    </div>
    
    <br><br>
    
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top: 20px;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:middle; font-size:8pt; color:#666;">
                <i>Not valid without official seal</i>
            </td>
            <td style="width:50%; text-align:right; vertical-align:middle; font-size:8pt; color:#666;">
                <b>Valid until: ' . $validUntilFormatted . '</b>
            </td>
        </tr>
    </table>

    <div style="position:absolute; bottom:20px; left:0; right:0; text-align:center; font-size:8pt; line-height:1.3;">
        <b>Baliwasan Barangay Hall San Jose Road corner Baliwasan Chico Barangay Hall</b><br>
        Zamboanga City, Philippines | facebook.com/barangaybaliwasanofficeofthepunongbarangay<br>
        992-6211 | 926-2639
    </div>';

    return $html;
}

/**
 * Generate HTML content for default certificates
 * @param array $requestInfo - Request information
 * @return string - HTML content
 */
function generateDefaultCertificateHTML($requestInfo)
{
    $fullName = trim($requestInfo['first_name'] . ' ' . ($requestInfo['middle_name'] ?? '') . ' ' . $requestInfo['last_name']);
    $currentDate = date('F d, Y');
    $serialNumber = $requestInfo['serial_number'] ?? 'N/A';
    
    // Calculate validity period (6 months from issue date)
    $issueDate = new DateTime();
    $validUntil = clone $issueDate;
    $validUntil->modify('+6 months');
    $validUntilFormatted = $validUntil->format('F d, Y');

    // Handle fee display
    $fee = floatval($requestInfo['fee'] ?? 0);
    $feeDisplay = ($fee == 0) ? '<span style="color: #059669; font-weight: bold;">FREE / NO COST</span>' : 'Php ' . number_format($fee, 2);

    $html = '
    <style>
        body { font-family: helvetica, sans-serif; }
        .serial-number {
            text-align: right;
            font-size: 9px;
            color: #666;
            margin-bottom: 10px;
        }
        .header-text {
            text-align: center;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 25px 0 15px 0;
            text-decoration: underline;
        }
        .info-box {
            margin: 15px 0;
            font-size: 10px;
        }
        .content {
            text-align: justify;
            line-height: 1.8;
            font-size: 11px;
        }
        .signature-section {
            margin-top: 40px;
            text-align: right;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin: 5px 0;
        }
        .footer-info {
            margin-top: 30px;
            font-size: 9px;
        }
    </style>
    
    <div class="serial-number">Serial No.: <b>' . $serialNumber . '</b></div>
    
    <div class="header-text">
        <b>Republic of the Philippines</b><br>
        <b>Province of Zamboanga Del Sur</b><br>
        <b>City of Zamboanga</b><br>
        <b>BARANGAY BALIWASAN</b><br>
        <b>Office of the Barangay Captain</b>
    </div>
    
    <div class="title">' . strtoupper(htmlspecialchars($requestInfo['document_name'])) . '</div>
    
    <div class="info-box">
        <b>Request ID:</b> ' . htmlspecialchars($requestInfo['request_id']) . '<br>
        <b>Date Issued:</b> ' . $currentDate . '
    </div>
    
    <div class="content">
        <p><b>TO WHOM IT MAY CONCERN:</b></p>
        
        <p style="text-indent: 40px;">
            This is to certify that <b>' . strtoupper(htmlspecialchars($fullName)) . '</b>, 
            a resident of <b>' . htmlspecialchars($requestInfo['address']) . '</b>, 
            has requested this document for the following purpose:
        </p>
        
        <p style="text-indent: 40px; font-style: italic;">
            <b>"' . htmlspecialchars($requestInfo['purpose']) . '"</b>
        </p>
        
        <p style="text-indent: 40px;">
            This certification is being issued upon the request of the above-named person 
            for whatever legal purpose it may serve.
        </p>
        
        <p style="text-indent: 40px;">
            Issued this <b>' . date('jS') . '</b> day of <b>' . date('F Y') . '</b> 
            at the Barangay Hall, Baliwasan, Zamboanga City, Zamboanga Del Sur 7000, Philippines.
        </p>
    </div>
    
    <div class="signature-section">
        <div class="signature-line"></div><br>
        <b>BARANGAY CAPTAIN</b><br>
        <i>Punong Barangay</i>
    </div>
    
    <div class="footer-info">
        <b>Paid under O.R. No.:</b> ' . ($fee == 0 ? 'N/A' : '__________') . '<br>
        <b>Amount Paid:</b> ' . $feeDisplay . '<br>
        <b>Date Paid:</b> ' . ($fee == 0 ? 'N/A' : $currentDate) . '
    </div>
    
    <table cellspacing="0" cellpadding="0" style="width:100%; margin-top: 20px;">
        <tr>
            <td style="width:50%; text-align:left; vertical-align:middle; font-size:8px; color:#666;">
                <i>Not valid without official seal</i>
            </td>
            <td style="width:50%; text-align:right; vertical-align:middle; font-size:8px; color:#666;">
                <b>Valid until: ' . $validUntilFormatted . '</b>
            </td>
        </tr>
    </table>';

    return $html;
}
?>