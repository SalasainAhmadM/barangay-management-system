<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit();
}

$requestId = (int) $input['request_id'];

try {
    $conn->begin_transaction();

    // First, delete any attachments from the request_attachments table
    $stmtAttachments = $conn->prepare("DELETE FROM request_attachments WHERE request_id = ?");
    $stmtAttachments->bind_param("i", $requestId);
    $stmtAttachments->execute();
    $stmtAttachments->close();

    // Then delete the request
    $stmt = $conn->prepare("DELETE FROM document_requests WHERE id = ?");
    $stmt->bind_param("i", $requestId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Request deleted successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete request']);
    }

    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>