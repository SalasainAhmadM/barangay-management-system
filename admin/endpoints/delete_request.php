<?php
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$requestId = isset($data['request_id']) ? (int) $data['request_id'] : 0;

if ($requestId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

try {
    $conn->begin_transaction();

    // Fetch request details before deletion (for logging)
    $stmtFetch = $conn->prepare("
        SELECT 
            dr.id,
            dt.name AS document_name,
            dt.type AS document_type,
            dt.fee,
            u.first_name,
            u.middle_name,
            u.last_name
        FROM document_requests dr
        INNER JOIN document_types dt ON dr.document_type_id = dt.id
        INNER JOIN user u ON dr.user_id = u.id
        WHERE dr.id = ?
    ");
    $stmtFetch->bind_param("i", $requestId);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();
    $requestData = $result->fetch_assoc();
    $stmtFetch->close();

    if (!$requestData) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }

    // 🗑️ Delete related attachments first
    $stmtAttachments = $conn->prepare("DELETE FROM request_attachments WHERE request_id = ?");
    $stmtAttachments->bind_param("i", $requestId);
    $stmtAttachments->execute();
    $stmtAttachments->close();

    // 🗑️ Delete the main document request
    $stmtDelete = $conn->prepare("DELETE FROM document_requests WHERE id = ?");
    $stmtDelete->bind_param("i", $requestId);
    $stmtDelete->execute();

    if ($stmtDelete->affected_rows > 0) {
        // ✅ Log activity
        $residentName = trim("{$requestData['first_name']} {$requestData['middle_name']} {$requestData['last_name']}");
        $feeText = ($requestData['fee'] > 0) ? "with a fee of {$requestData['fee']}." : "without a fee.";
        $activity = "Deleted Document Request";
        $description = "Deleted a {$requestData['document_type']} request (ID: {$requestId}) for '{$requestData['document_name']}' {$feeText} Submitted by {$residentName}.";

        $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
        $logStmt->bind_param("ss", $activity, $description);
        $logStmt->execute();
        $logStmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Document request deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete document request']);
    }

    $stmtDelete->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>