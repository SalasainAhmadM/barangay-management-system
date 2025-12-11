<?php
// ========== delete_report.php ==========
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

if (empty($report_id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit();
}

try {
    // First, get the photo path and details for activity log
    $stmt = $conn->prepare("SELECT photo_path, report_type, location, description FROM community_reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit();
    }

    // Delete the report from database
    $stmt = $conn->prepare("DELETE FROM community_reports WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Delete associated photo file if it exists
            if (!empty($report['photo_path'])) {
                $photo_file = '../../assets/community_reports/' . $report['photo_path'];
                if (file_exists($photo_file)) {
                    unlink($photo_file);
                }
            }

            // Log activity
            $activity = "Deleted a report";
            $report_type = $report['report_type'] ?? 'Unknown type';
            $desc_location = $report['location'] ?? 'Unknown location';
            $description = "Deleted a '{$report_type}' report at '{$desc_location}' (Report ID: {$report_id}).";

            $log_stmt = $conn->prepare("
                INSERT INTO activity_logs (activity, description, created_at)
                VALUES (?, ?, NOW())
            ");
            $log_stmt->bind_param("ss", $activity, $description);
            $log_stmt->execute();
            $log_stmt->close();

            echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete report']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>