
<?php
session_start();
header('Content-Type: application/json');
require_once("../../conn/conn.php");

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing report ID']);
    exit();
}

$reportId = intval($data['id']);

if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get report images before deletion
    $imagesStmt = $conn->prepare("SELECT images FROM safety_reports WHERE id = ?");
    $imagesStmt->bind_param("i", $reportId);
    $imagesStmt->execute();
    $result = $imagesStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Report not found');
    }
    
    $report = $result->fetch_assoc();
    $imagesJson = $report['images'];
    
    // Delete associated images from safety_report_images table
    $deleteImagesStmt = $conn->prepare("DELETE FROM safety_report_images WHERE report_id = ?");
    $deleteImagesStmt->bind_param("i", $reportId);
    $deleteImagesStmt->execute();
    
    // Delete history records
    $deleteHistoryStmt = $conn->prepare("DELETE FROM safety_report_history WHERE report_id = ?");
    $deleteHistoryStmt->bind_param("i", $reportId);
    $deleteHistoryStmt->execute();
    
    // Delete the main report
    $deleteReportStmt = $conn->prepare("DELETE FROM safety_reports WHERE id = ?");
    $deleteReportStmt->bind_param("i", $reportId);
    
    if (!$deleteReportStmt->execute()) {
        throw new Exception('Failed to delete report');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Delete physical image files
    if ($imagesJson) {
        $images = json_decode($imagesJson, true);
        if (is_array($images)) {
            foreach ($images as $imagePath) {
                $fullPath = "../../" . $imagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Report deleted successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting report: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
