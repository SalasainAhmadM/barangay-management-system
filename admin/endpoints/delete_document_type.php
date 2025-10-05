<?php
// ========== delete_document_type.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

date_default_timezone_set("Asia/Manila");

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

try {
    // Check if document type exists
    $checkStmt = $conn->prepare("SELECT id, name, type FROM document_types WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Document type not found']);
        exit();
    }

    $doc = $result->fetch_assoc();
    $documentName = $doc["name"];
    $documentType = $doc["type"];
    $checkStmt->close();

    // Check if there are any pending requests using this document type
    $checkRequests = $conn->prepare("SELECT COUNT(*) AS count FROM document_requests WHERE document_type_id = ?");
    $checkRequests->bind_param("i", $id);
    $checkRequests->execute();
    $reqResult = $checkRequests->get_result();
    $row = $reqResult->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete document type with existing requests. Please deactivate it instead.']);
        exit();
    }
    $checkRequests->close();

    // Proceed with deletion
    $stmt = $conn->prepare("DELETE FROM document_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Log activity
        $activity = "Deleted a Document Type";
        $log_description = "Deleted a document type named '{$documentName}' ({$documentType}).";

        $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
        $log_stmt->bind_param("ss", $activity, $log_description);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Document type deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete document type']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>