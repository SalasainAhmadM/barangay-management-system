<?php
// ========== delete_document_type.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

try {
    // Check if there are any pending requests using this document type
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM document_requests WHERE document_type_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete document type with existing requests. Please deactivate it instead.']);
        exit();
    }
    $checkStmt->close();

    // Proceed with deletion if no requests exist
    $stmt = $conn->prepare("DELETE FROM document_types WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Document type deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete document type']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>