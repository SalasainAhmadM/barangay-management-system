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
if (!isset($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Notification ID is required'
    ]);
    exit();
}

$notificationId = intval($data['id']);

// Validate ID
if ($notificationId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID'
    ]);
    exit();
}

try {
    $conn->begin_transaction();

    // Get notification details before deletion for activity log
    $getStmt = $conn->prepare("
        SELECT 
            n.id,
            n.type,
            n.title,
            n.message,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.id as user_id
        FROM notifications n
        INNER JOIN user u ON n.user_id = u.id
        WHERE n.id = ?
    ");
    $getStmt->bind_param("i", $notificationId);
    $getStmt->execute();
    $result = $getStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Notification not found");
    }

    $notification = $result->fetch_assoc();
    $userName = $notification['first_name'] . ' ' . $notification['middle_name'] . ' ' . $notification['last_name'];
    $notifType = $notification['type'];
    $notifTitle = $notification['title'];
    $userId = $notification['user_id'];

    // Delete the notification
    $deleteStmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $deleteStmt->bind_param("i", $notificationId);

    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete notification");
    }

    // Check if any rows were affected
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("Notification not found or already deleted");
    }

    // Log to activity_logs
    // $activity = "Notification Deleted";
    // $description = "Deleted '$notifType' notification (ID: $notificationId) for user '$userName' (ID: $userId). Title: '$notifTitle'";
    // $logStmt = $conn->prepare("
    //     INSERT INTO activity_logs 
    //     (activity, description, created_at) 
    //     VALUES (?, ?, NOW())
    // ");
    // $logStmt->bind_param("ss", $activity, $description);
    // $logStmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Notification deleted successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in delete_notification.php: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete notification: ' . $e->getMessage()
    ]);
}

$conn->close();
?>