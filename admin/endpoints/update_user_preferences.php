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
if (!isset($data['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit();
}

$userId = intval($data['user_id']);
$wasteReminders = isset($data['waste_reminders']) ? intval($data['waste_reminders']) : 1;
$requestUpdates = isset($data['request_updates']) ? intval($data['request_updates']) : 1;
$announcements = isset($data['announcements']) ? intval($data['announcements']) : 1;
$smsNotifications = isset($data['sms_notifications']) ? intval($data['sms_notifications']) : 1;
$adminNotes = isset($data['notes']) ? trim($data['notes']) : '';

// Validate user exists and get user name
try {
    $userCheckStmt = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM user WHERE id = ?");
    $userCheckStmt->bind_param("i", $userId);
    $userCheckStmt->execute();
    $userCheckResult = $userCheckStmt->get_result();

    if ($userCheckResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    $user = $userCheckResult->fetch_assoc();
    $userName = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);

    // Start transaction
    $conn->begin_transaction();

    // Update or insert preferences
    $stmt = $conn->prepare("
        INSERT INTO notification_preferences 
        (user_id, waste_reminders, request_updates, announcements, sms_notifications, updated_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            waste_reminders = VALUES(waste_reminders),
            request_updates = VALUES(request_updates),
            announcements = VALUES(announcements),
            sms_notifications = VALUES(sms_notifications),
            updated_at = NOW()
    ");

    $stmt->bind_param(
        "iiiii",
        $userId,
        $wasteReminders,
        $requestUpdates,
        $announcements,
        $smsNotifications
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update preferences");
    }

    // Log the admin action to activity_logs
    $activity = "Notification Preferences Update";

    // Build preference status string
    $prefStatus = [];
    $prefStatus[] = "Waste Reminders: " . ($wasteReminders ? 'Enabled' : 'Disabled');
    $prefStatus[] = "Request Updates: " . ($requestUpdates ? 'Enabled' : 'Disabled');
    $prefStatus[] = "Announcements: " . ($announcements ? 'Enabled' : 'Disabled');
    $prefStatus[] = "SMS Notifications: " . ($smsNotifications ? 'Enabled' : 'Disabled');

    $description = "Updated notification preferences for user '$userName' (ID: $userId). " . implode(", ", $prefStatus);

    if (!empty($adminNotes)) {
        $description .= ". Admin notes: " . $adminNotes;
    }

    $logStmt = $conn->prepare("
        INSERT INTO activity_logs 
        (activity, description, created_at) 
        VALUES (?, ?, NOW())
    ");

    if ($logStmt) {
        $logStmt->bind_param("ss", $activity, $description);
        $logStmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Notification preferences updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }

    error_log("Error in update_user_preferences.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update preferences: ' . $e->getMessage()
    ]);
}

$conn->close();
?>