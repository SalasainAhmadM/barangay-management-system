<?php
// ========== edit_document_type.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? 0;
$documentName = $data['documentName'] ?? '';
$documentType = $data['documentType'] ?? 'certificate';
$icon = $data['iconClass'] ?? 'fa-certificate';
$processingDays = $data['processingDays'] ?? '';
$fee = $data['fee'] ?? 0;
$description = $data['description'] ?? '';
$requirements = 'N/A';
$isActive = $data['status'] ?? 1;

if (empty($id) || empty($documentName) || empty($processingDays)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE document_types SET name = ?, type = ?, icon = ?, processing_days = ?, fee = ?, description = ?, requirements = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssdssii", $documentName, $documentType, $icon, $processingDays, $fee, $description, $requirements, $isActive, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Document type updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update document type']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>