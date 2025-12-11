
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
if (!isset($data['id']) || !isset($data['newStatus'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$reportId = intval($data['id']);
$newStatus = trim($data['newStatus']);
$responseNotes = isset($data['responseNotes']) ? trim($data['responseNotes']) : null;
$adminId = $_SESSION['admin_id'];

// Validate status
$validStatuses = ['pending', 'under_review', 'in_progress', 'resolved', 'closed'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get current report status
    $checkStmt = $conn->prepare("SELECT status, user_id FROM safety_reports WHERE id = ?");
    $checkStmt->bind_param("i", $reportId);
    $checkStmt->execute();
    $currentReport = $checkStmt->get_result()->fetch_assoc();
    
    if (!$currentReport) {
        throw new Exception('Report not found');
    }
    
    // Update report status
    $resolvedAt = null;
    if ($newStatus === 'resolved' || $newStatus === 'closed') {
        $resolvedAt = date('Y-m-d H:i:s');
    }
    
    if ($resolvedAt) {
        $updateStmt = $conn->prepare("
            UPDATE safety_reports 
            SET status = ?, 
                response_notes = ?, 
                assigned_to = ?,
                resolved_at = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->bind_param("ssisi", $newStatus, $responseNotes, $adminId, $resolvedAt, $reportId);
    } else {
        $updateStmt = $conn->prepare("
            UPDATE safety_reports 
            SET status = ?, 
                response_notes = ?, 
                assigned_to = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->bind_param("ssii", $newStatus, $responseNotes, $adminId, $reportId);
    }
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update report');
    }
    
    // Log status change in history
    $historyStmt = $conn->prepare("
        INSERT INTO safety_report_history (report_id, status, notes, changed_by, changed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $historyStmt->bind_param("issi", $reportId, $newStatus, $responseNotes, $adminId);
    $historyStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // TODO: Send notification to user if not anonymous
    // You can add email notification here
    
    echo json_encode([
        'success' => true,
        'message' => 'Report status updated successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error updating report: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
