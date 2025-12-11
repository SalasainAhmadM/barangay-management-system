
<?php
session_start();
header('Content-Type: application/json');
require_once("../../conn/conn.php");

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get report ID from query parameter
$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reportId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit();
}

try {
    // Fetch report with user information
    $stmt = $conn->prepare("
        SELECT 
            sr.*,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.image as user_image
        FROM safety_reports sr
        LEFT JOIN user u ON sr.user_id = u.id
        WHERE sr.id = ?
    ");
    
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit();
    }
    
    $report = $result->fetch_assoc();
    
    // For anonymous reports, clear personal info
    if ($report['is_anonymous']) {
        $report['first_name'] = 'Anonymous';
        $report['last_name'] = '';
        $report['middle_name'] = '';
        $report['email'] = null;
        $report['contact_number'] = null;
    }
    
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching report: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
