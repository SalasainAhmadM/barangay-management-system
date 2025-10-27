<?php
// ========== update_request_status.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
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
$rejectionReason = trim($input['rejection_reason'] ?? '');
$notes = trim($input['notes'] ?? '');

// Validate status
$validStatuses = ['pending', 'processing', 'approved', 'ready', 'completed', 'rejected', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
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

    // Prepare update query
    $updateFields = "status = ?, updated_at = NOW()";
    $params = [$status];
    $types = "s";

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

    // Generate PDF if status is 'ready' or 'completed'
    $pdfFilename = null;
    if (in_array($status, ['ready', 'completed'])) {
        $pdfFilename = generateDocumentPDF($requestInfo);

        if ($pdfFilename) {
            $updateFields .= ", document_file = ?";
            $params[] = $pdfFilename;
            $types .= "s";
        }
    }

    // Add WHERE clause
    $params[] = $requestId;
    $types .= "i";

    // Execute update
    $stmt = $conn->prepare("UPDATE document_requests SET $updateFields WHERE id = ?");
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // ✅ Log admin activity
            $activity = "Updated document request status";
            $docName = $requestInfo['document_name'] ?? 'Unknown document';

            // Build readable description
            $description = "Updated the status of request ID {$requestId} for '{$docName}' to '{$status}'.";

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
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'pdf_generated' => ($pdfFilename !== null)
            ]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Request not found or no changes made']);
        }
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

    $stmt->close();
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Error in update_request_status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}

/**
 * Generate PDF document for certificate or permit
 * @param array $requestInfo - Request information from database
 * @return string|false - Returns filename on success, false on failure
 */
function generateDocumentPDF($requestInfo)
{
    try {
        // Load TCPDF
        require_once('../../vendor/autoload.php');

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
    // Check if this is a Certificate of Indigency
    $isIndigency = (stripos($requestInfo['document_name'], 'indigency') !== false);

    if ($isIndigency) {
        return generateIndigencyCertificateHTML($requestInfo);
    } else {
        return generateDefaultCertificateHTML($requestInfo);
    }
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

    // ✅ Use absolute path for TCPDF image rendering
    $imagePath = K_PATH_IMAGES . 'barangaycouncil.png'; // Make sure bms.png is inside tcpdf/images folder

    $html = '
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }
        .header-section {
            text-align: center;
            line-height: 1.3;
            margin-bottom: 25px;
            position: relative;
        }
        .header-section img {
            width: 75px;
            height: 75px;
            position: absolute;
            top: 15px;
            left: 60px;
        }
        .barangay-name {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
        }
        .facebook-link {
            font-size: 9pt;
            margin-top: 2px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 35px 0 20px 0;
            text-decoration: underline;
        }
        .content {
            margin: 0 30px;
            text-align: justify;
            line-height: 1.9;
        }
        .to-whom {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .indent {
            text-indent: 50px;
        }
        .signature {
            margin-top: 60px;
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
        .seal {
            position: absolute;
            left: 80px;
            margin-top: 100px;
            font-style: italic;
        }
        .footer-section {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            line-height: 1.3;
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

        <p class="indent" style="margin-top: 25px;">
            Issued this <b><u>' . $day . ' day of ' . $month . ' ' . $year . '</u></b> 
            at Barangay Baliwasan, Zamboanga City.
        </p>
    </div>

    <div class="signature">
        <b class="name" style="text-decoration: underline;">HON. MA. JEMIELY CZARINA L. CABATO</b><br>
        <b class="position">Punong Barangay</b>
    </div>

    <div class="seal">- SEAL -</div>

    <div class="footer-section">
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

    // Handle fee display
    $fee = floatval($requestInfo['fee'] ?? 0);
    $feeDisplay = ($fee == 0) ? '<span style="color: #059669; font-weight: bold;">FREE / NO COST</span>' : 'Php ' . number_format($fee, 2);

    $html = '
    <style>
        body { font-family: helvetica, sans-serif; }
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
    
    <div style="margin-top: 20px; font-size: 8px; text-align: center; color: #666;">
        <i>Not valid without official seal</i>
    </div>';

    return $html;
}
?>