<?php
// ========== add_document_type.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$documentName = $data['documentName'] ?? '';
$documentType = $data['documentType'] ?? 'certificate';
$icon = $data['iconClass'] ?? 'fa-certificate';
$processingDays = $data['processingDays'] ?? '';
$fee = $data['fee'] ?? 0;
$description = $data['description'] ?? '';
$requirements = 'N/A';
$isActive = $data['status'] ?? 1;

if (empty($documentName) || empty($processingDays)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO document_types (name, type, icon, processing_days, fee, description, requirements, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdssi", $documentName, $documentType, $icon, $processingDays, $fee, $description, $requirements, $isActive);

    if ($stmt->execute()) {

        $activity = "Add Document Type";
        $feeText = ($fee > 0) ? "with a fee of {$fee}." : "without a fee.";
        $descriptionLog = "Added a new document type named {$documentName} ({$documentType}) {$feeText}";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $activity, $descriptionLog);
        $log_stmt->execute();
        $log_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Document type added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add document type']);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();
?>