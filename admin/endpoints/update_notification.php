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

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!isset($data['id']) || !isset($data['type']) || !isset($data['title']) || !isset($data['message'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$notificationId = intval($data['id']);
$type = $data['type'];
$title = trim($data['title']);
$message = trim($data['message']);
$isRead = isset($data['isRead']) ? intval($data['isRead']) : 0;

// Map type to icon
$iconMap = [
    'waste' => 'fa-trash-alt',
    'request' => 'fa-file-alt',
    'announcement' => 'fa-bullhorn',
    'alert' => 'fa-exclamation-triangle',
    'success' => 'fa-check-circle'
];
$icon = $iconMap[$type] ?? 'fa-bell';

try {
    // Get notification details before update for logging
    $getStmt = $conn->prepare("
        SELECT n.*, u.first_name, u.last_name 
        FROM notifications n
        INNER JOIN user u ON n.user_id = u.id
        WHERE n.id = ?
    ");
    $getStmt->bind_param("i", $notificationId);
    $getStmt->execute();
    $getResult = $getStmt->get_result();

    if ($getResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Notification not found'
        ]);
        exit();
    }

    $oldNotif = $getResult->fetch_assoc();
    $userName = $oldNotif['first_name'] . ' ' . $oldNotif['last_name'];

    $conn->begin_transaction();

    // Update notification
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET type = ?, 
            icon = ?, 
            title = ?, 
            message = ?, 
            is_read = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param("ssssii", $type, $icon, $title, $message, $isRead, $notificationId);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update notification");
    }

    // Build change log
    $changes = [];
    if ($oldNotif['type'] !== $type) {
        $changes[] = "Type: '{$oldNotif['type']}' → '$type'";
    }
    if ($oldNotif['title'] !== $title) {
        $changes[] = "Title updated";
    }
    if ($oldNotif['message'] !== $message) {
        $changes[] = "Message updated";
    }
    if ($oldNotif['is_read'] != $isRead) {
        $changes[] = "Status: " . ($isRead ? "Marked as read" : "Marked as unread");
    }

    // Log to activity_logs
    $activity = "Notification Updated";
    $changeDesc = !empty($changes) ? implode(", ", $changes) : "No changes detected";
    $description = "Updated notification #$notificationId for user '$userName'. Changes: $changeDesc";

    $logStmt = $conn->prepare("
        INSERT INTO activity_logs 
        (activity, description, created_at) 
        VALUES (?, ?, NOW())
    ");
    $logStmt->bind_param("ss", $activity, $description);
    $logStmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Notification updated successfully'
    ]);

} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    error_log("Error in update_notification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update notification: ' . $e->getMessage()
    ]);
}

$conn->close();
?>