<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$requestId = (int) $_GET['id'];

try {
    // Fetch request details with user and document type info
    $stmt = $conn->prepare("
        SELECT 
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
            u.house_number,
            u.street_name,
            u.barangay,
            CONCAT_WS(', ', 
                NULLIF(u.house_number, ''), 
                NULLIF(u.street_name, ''), 
                NULLIF(u.barangay, '')
            ) as address,
            u.image as user_image
        FROM document_requests dr
        INNER JOIN document_types dt ON dr.document_type_id = dt.id
        INNER JOIN user u ON dr.user_id = u.id
        WHERE dr.id = ?
    ");

    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();

        // Format address if components are null
        if (empty($request['address'])) {
            $request['address'] = 'N/A';
        }

        // Fetch uploaded documents/attachments for this request
        $filesStmt = $conn->prepare("
            SELECT 
                id,
                request_id,
                file_name,
                file_path,
                file_type,
                uploaded_at
            FROM request_attachments
            WHERE request_id = ?
            ORDER BY uploaded_at DESC
        ");

        $filesStmt->bind_param("i", $requestId);
        $filesStmt->execute();
        $filesResult = $filesStmt->get_result();

        $files = [];
        while ($file = $filesResult->fetch_assoc()) {
            $files[] = $file;
        }

        $request['uploaded_files'] = $files;
        $filesStmt->close();

        echo json_encode(['success' => true, 'request' => $request]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>