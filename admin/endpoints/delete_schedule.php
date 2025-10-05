<?php
// ========== delete_schedule.php ==========
session_start();
require_once("../../conn/conn.php");
header('Content-Type: application/json');

// Ensure admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing schedule ID']);
    exit();
}

try {
    // Fetch schedule details before deletion (for logging)
    $fetchStmt = $conn->prepare("SELECT waste_type, collection_days FROM waste_schedules WHERE schedule_id = ?");
    $fetchStmt->bind_param("i", $id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit();
    }

    $schedule = $result->fetch_assoc();
    $fetchStmt->close();

    // Delete schedule
    $deleteStmt = $conn->prepare("DELETE FROM waste_schedules WHERE schedule_id = ?");
    $deleteStmt->bind_param("i", $id);

    if ($deleteStmt->execute()) {
        // Add to activity logs
        $activity = "Deleted Waste Schedule";
        $description = "Removed schedule (ID: {$id}) - Waste Type: '{$schedule['waste_type']}', Collection Days: '{$schedule['collection_days']}'.";

        $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
        $logStmt->bind_param("ss", $activity, $description);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule']);
    }

    $deleteStmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>