<?php
// ========== update_report_status.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION["admin_id"];
$data = json_decode(file_get_contents('php://input'), true);

$report_id = $data['id'] ?? 0;
$newStatus = $data['newStatus'] ?? '';
$resolutionNotes = trim($data['resolutionNotes'] ?? '');

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
    // Get report details for logging
    $stmt = $conn->prepare("SELECT location FROM missed_collections WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit();
    }

    // Update status
    if ($newStatus === 'resolved') {
        $stmt = $conn->prepare("
            UPDATE missed_collections 
            SET status = ?, resolution_notes = ?, resolved_date = NOW() 
            WHERE report_id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            UPDATE missed_collections 
            SET status = ?, resolution_notes = ? 
            WHERE report_id = ?
        ");
    }

    $stmt->bind_param("ssi", $newStatus, $resolutionNotes, $report_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {

            // ✅ Log activity
            $activity = "Updated report status";
            $location = $report['location'] ?? 'Unknown location';
            $feeText = !empty($resolutionNotes)
                ? "Resolution notes added."
                : "No resolution notes.";
            $description = "Updated report ID {$report_id} status to '{$newStatus}' at '{$location}'. {$feeText}";

            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (activity, description, created_at)
                VALUES (?, ?, NOW())
            ");
            $log_stmt->bind_param("ss", $activity, $description);
            $log_stmt->execute();
            $log_stmt->close();

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