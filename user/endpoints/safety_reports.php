<?php
session_start();
require_once("../../conn/conn.php");

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please log in.'
    ]);
    exit();
}

$user_id = $_SESSION["user_id"];

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_my_reports') {
        getMyReports($conn, $user_id);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit();
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'create_report':
            createReport($conn, $user_id, $data);
            break;
            
        case 'update_status':
            updateReportStatus($conn, $user_id, $data);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    exit();
}

// Invalid request method
echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
exit();

/**
 * Create a new safety report
 */
function createReport($conn, $user_id, $data) {
    try {
        // Validate required fields
        $required_fields = ['incidentType', 'title', 'urgency', 'location', 'description'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Sanitize and validate input
        $incident_type = trim($data['incidentType']);
        $title = trim($data['title']);
        $urgency = trim($data['urgency']);
        $location = trim($data['location']);
        $description = trim($data['description']);
        $witness_info = isset($data['witnessInfo']) ? trim($data['witnessInfo']) : null;
        $is_anonymous = isset($data['isAnonymous']) && $data['isAnonymous'] ? 1 : 0;
        
        // Validate incident type
        $valid_types = ['crime', 'emergency', 'infrastructure', 'environmental', 'stray_animals', 'drugs', 'noise', 'other'];
        if (!in_array($incident_type, $valid_types)) {
            throw new Exception('Invalid incident type');
        }
        
        // Validate urgency level
        $valid_urgency = ['low', 'medium', 'high', 'emergency'];
        if (!in_array($urgency, $valid_urgency)) {
            throw new Exception('Invalid urgency level');
        }
        
        // Validate title length
        if (strlen($title) < 5 || strlen($title) > 255) {
            throw new Exception('Title must be between 5 and 255 characters');
        }
        
        // Validate description length
        if (strlen($description) < 20) {
            throw new Exception('Description must be at least 20 characters');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert the report
        $stmt = $conn->prepare("
            INSERT INTO safety_reports (
                user_id, 
                incident_type, 
                title, 
                description, 
                location, 
                incident_date,
                urgency_level, 
                status, 
                is_anonymous, 
                witness_info,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pending', ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "isssssss",
            $user_id,
            $incident_type,
            $title,
            $description,
            $location,
            $urgency,
            $is_anonymous,
            $witness_info
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create report: ' . $stmt->error);
        }
        
        $report_id = $stmt->insert_id;
        $stmt->close();
        
        // Create initial history entry
        $stmt = $conn->prepare("
            INSERT INTO safety_report_history (
                report_id, 
                status, 
                notes, 
                changed_by, 
                changed_at
            ) VALUES (?, 'pending', 'Report submitted', ?, NOW())
        ");
        
        $stmt->bind_param("ii", $report_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Create notification for admins (optional - if you have admin notification system)
        createAdminNotification($conn, $report_id, $title, $urgency);
        
        echo json_encode([
            'success' => true,
            'message' => 'Safety report submitted successfully',
            'report_id' => $report_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get all reports for the current user
 */
function getMyReports($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id,
                incident_type,
                title,
                description,
                location,
                urgency_level,
                status,
                is_anonymous,
                witness_info,
                response_notes,
                created_at,
                updated_at,
                resolved_at
            FROM safety_reports
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'reports' => $reports
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve reports: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update report status (can be used by admins or for user actions)
 */
function updateReportStatus($conn, $user_id, $data) {
    try {
        $report_id = $data['report_id'] ?? null;
        $new_status = $data['status'] ?? null;
        $notes = $data['notes'] ?? null;
        
        if (!$report_id || !$new_status) {
            throw new Exception('Missing required fields');
        }
        
        // Validate status
        $valid_statuses = ['pending', 'under_review', 'in_progress', 'resolved', 'closed'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid status');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update report status
        $stmt = $conn->prepare("
            UPDATE safety_reports 
            SET status = ?, 
                updated_at = NOW(),
                resolved_at = IF(? = 'resolved', NOW(), resolved_at)
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssi", $new_status, $new_status, $report_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update status');
        }
        
        $stmt->close();
        
        // Add to history
        $stmt = $conn->prepare("
            INSERT INTO safety_report_history (
                report_id, 
                status, 
                notes, 
                changed_by, 
                changed_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("issi", $report_id, $new_status, $notes, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Create notification for admins about new safety report
 */
function createAdminNotification($conn, $report_id, $title, $urgency) {
    try {
        // Get all admin users (adjust this query based on your user roles system)
        $stmt = $conn->prepare("
            SELECT id FROM users WHERE role = 'admin' OR role = 'barangay_official'
        ");
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            $urgency_emoji = [
                'low' => '🟢',
                'medium' => '🟡',
                'high' => '🟠',
                'emergency' => '🔴'
            ];
            
            $emoji = $urgency_emoji[$urgency] ?? '';
            
            while ($admin = $result->fetch_assoc()) {
                // Insert notification for each admin
                $notify_stmt = $conn->prepare("
                    INSERT INTO notifications (
                        user_id, 
                        type, 
                        title, 
                        message, 
                        icon, 
                        created_at
                    ) VALUES (?, 'safety', ?, ?, 'fa-shield-alt', NOW())
                ");
                
                $notify_title = "New Safety Report";
                $notify_message = "$emoji $title - Urgency: " . strtoupper($urgency);
                
                $notify_stmt->bind_param(
                    "iss",
                    $admin['id'],
                    $notify_title,
                    $notify_message
                );
                
                $notify_stmt->execute();
                $notify_stmt->close();
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to create admin notifications: " . $e->getMessage());
    }
}
?>