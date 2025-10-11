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

// Validate user_id parameter
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit();
}

$userId = intval($_GET['user_id']);

try {
    // Fetch user information
    $userStmt = $conn->prepare("
        SELECT 
            id,
            first_name,
            middle_name,
            last_name,
            email,
            contact_number
        FROM user 
        WHERE id = ?
    ");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    $user = $userResult->fetch_assoc();

    // Fetch notification preferences
    $prefsStmt = $conn->prepare("
        SELECT 
            waste_reminders,
            request_updates,
            announcements,
            sms_notifications,
            updated_at
        FROM notification_preferences 
        WHERE user_id = ?
    ");
    $prefsStmt->bind_param("i", $userId);
    $prefsStmt->execute();
    $prefsResult = $prefsStmt->get_result();

    // If preferences don't exist, create default ones
    if ($prefsResult->num_rows === 0) {
        $createStmt = $conn->prepare("
            INSERT INTO notification_preferences 
            (user_id, waste_reminders, request_updates, announcements, sms_notifications) 
            VALUES (?, 1, 1, 1, 1)
        ");
        $createStmt->bind_param("i", $userId);
        $createStmt->execute();

        // Fetch the newly created preferences
        $prefsStmt->execute();
        $prefsResult = $prefsStmt->get_result();
    }

    $preferences = $prefsResult->fetch_assoc();

    echo json_encode([
        'success' => true,
        'user' => $user,
        'preferences' => [
            'waste_reminders' => (bool) $preferences['waste_reminders'],
            'request_updates' => (bool) $preferences['request_updates'],
            'announcements' => (bool) $preferences['announcements'],
            'sms_notifications' => (bool) $preferences['sms_notifications'],
            'updated_at' => $preferences['updated_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_user_preferences.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>