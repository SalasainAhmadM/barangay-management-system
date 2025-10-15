<?php
session_start();
require_once("../../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../index.php?auth=error");
    exit();
}

$user_id = $_SESSION["user_id"];
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    die("Invalid request ID");
}

// Fetch request details with user information
$query = "SELECT 
            dr.*,
            dt.name as document_name,
            dt.icon,
            dt.type as document_type,
            dt.fee,
            dt.processing_days,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.image as user_image,
            CONCAT(
                COALESCE(u.house_number, ''), ' ',
                COALESCE(u.street_name, ''), ', ',
                COALESCE(u.barangay, '')
            ) as address
          FROM document_requests dr
          INNER JOIN document_types dt ON dr.document_type_id = dt.id
          INNER JOIN user u ON dr.user_id = u.id
          WHERE dr.id = ? AND dr.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    die("Request not found or unauthorized access");
}

$request = $result->fetch_assoc();
$stmt->close();

// Check if document is ready for download
if (!in_array($request['status'], ['ready', 'completed'])) {
    $conn->close();
    die("Document is not yet available for download. Current status: " . ucfirst($request['status']));
}

$conn->close();

// Check if document file exists and is not empty
$file_path = null;
$file_name = null;

if (!empty($request['document_file']) && file_exists("../../uploads/document_requests/" . $request['document_file'])) {
    // Use the actual document file
    $file_path = "../../uploads/document_requests/" . $request['document_file'];
    $file_name = $request['request_id'] . '_' . str_replace(' ', '_', $request['document_name']) . '.pdf';
} else {
    // Use default "no document found" file
    $file_path = "../../uploads/document_requests/no_document_found.pdf";
    $file_name = "no_document_found.pdf";
    
    // Check if default file exists
    if (!file_exists($file_path)) {
        die("Document file not found and default file is missing at: " . $file_path);
    }
}

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Clear output buffer
ob_clean();
flush();

// Read and output file
readfile($file_path);
exit();
?>