<?php
// ========== delete_report.php ==========
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$report_id = $data['id'] ?? 0;

if (empty($report_id)) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit();
}

try {
    // First, get the photo path to delete the file
    $stmt = $conn->prepare("SELECT photo_path FROM missed_collections WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    // Delete the report from database
    $stmt = $conn->prepare("DELETE FROM missed_collections WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Delete associated photo file if it exists
            if ($report && $report['photo_path']) {
                $photo_file = '../../assets/waste_reports/' . $report['photo_path'];
                if (file_exists($photo_file)) {
                    unlink($photo_file);
                }
            }
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