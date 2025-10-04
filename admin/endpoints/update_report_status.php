<?php
// ========== update_report_status.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$report_id = $data['id'] ?? 0;
$newStatus = $data['newStatus'] ?? '';
$resolutionNotes = $data['resolutionNotes'] ?? '';

if (empty($report_id) || empty($newStatus)) {
    echo json_encode(['success' => false, 'message' => 'Report ID and status are required']);
    exit();
}

// Validate status
$validStatuses = ['pending', 'investigating', 'resolved', 'rejected'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Set resolved_date if status is resolved
    if ($newStatus === 'resolved') {
        $stmt = $conn->prepare("
            UPDATE missed_collections 
            SET status = ?, resolution_notes = ?, resolved_date = NOW() 
            WHERE report_id = ?
        ");
        $stmt->bind_param("ssi", $newStatus, $resolutionNotes, $report_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE missed_collections 
            SET status = ?, resolution_notes = ? 
            WHERE report_id = ?
        ");
        $stmt->bind_param("ssi", $newStatus, $resolutionNotes, $report_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Report status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Report not found or no changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update report status']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>