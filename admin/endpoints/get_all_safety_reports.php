<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    // Fetch all safety reports with user information
    $stmt = $conn->prepare("
        SELECT 
            sr.id,
            sr.user_id,
            sr.title,
            sr.description,
            sr.incident_type,
            sr.location,
            sr.urgency_level,
            sr.status,
            sr.is_anonymous,
            sr.witness_info,
            sr.response_notes,
            sr.created_at,
            sr.resolved_at,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number
        FROM safety_reports sr
        LEFT JOIN user u ON sr.user_id = u.id
        ORDER BY sr.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'total' => count($reports)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching safety reports: ' . $e->getMessage()
    ]);
}
?>