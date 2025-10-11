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
if (!isset($data['userId']) || !isset($data['type']) || !isset($data['title']) || !isset($data['message'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$userId = $data['userId'];
$type = $data['type'];
$title = trim($data['title']);
$message = trim($data['message']);

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
    $conn->begin_transaction();

    // If "all" users selected, get all user IDs
    if ($userId === 'all') {
        $userStmt = $conn->query("SELECT id FROM user");
        $userIds = [];
        while ($row = $userStmt->fetch_assoc()) {
            $userIds[] = $row['id'];
        }

        if (empty($userIds)) {
            throw new Exception("No users found");
        }

        // Insert notification for each user
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, icon, title, message, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        $insertedCount = 0;
        foreach ($userIds as $uid) {
            $stmt->bind_param("issss", $uid, $type, $icon, $title, $message);
            if ($stmt->execute()) {
                $insertedCount++;
            }
        }

        // Log to activity_logs
        $activity = "Bulk Notification Created";
        $description = "Created '$type' notification for all users ($insertedCount users). Title: '$title'";

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
            'message' => "Notification sent to $insertedCount users successfully"
        ]);

    } else {
        // Single user notification
        $userIdInt = intval($userId);

        // Verify user exists
        $userCheck = $conn->prepare("SELECT first_name, middle_name, last_name FROM user WHERE id = ?");
        $userCheck->bind_param("i", $userIdInt);
        $userCheck->execute();
        $userResult = $userCheck->get_result();

        if ($userResult->num_rows === 0) {
            throw new Exception("User not found");
        }

        $user = $userResult->fetch_assoc();
        $userName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];

        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, icon, title, message, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        $stmt->bind_param("issss", $userIdInt, $type, $icon, $title, $message);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create notification");
        }

        // Log to activity_logs
        $activity = "Notification Created";
        $description = "Created '$type' notification for user '$userName' (ID: $userIdInt). Title: '$title'";

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
            'message' => 'Notification sent successfully'
        ]);
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in create_notification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create notification: ' . $e->getMessage()
    ]);
}

$conn->close();
?>