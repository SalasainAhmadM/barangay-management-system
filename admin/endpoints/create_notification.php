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
if (!isset($data['userIds']) || !isset($data['type']) || !isset($data['title']) || !isset($data['message'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$userIds = $data['userIds'];
$recipientType = $data['recipientType'] ?? 'single';
$type = $data['type'];
$title = trim($data['title']);
$message = trim($data['message']);
$streetFilter = isset($data['streetFilter']) ? $data['streetFilter'] : null;

// Validate userIds is an array
if (!is_array($userIds) || empty($userIds)) {
    echo json_encode([
        'success' => false,
        'message' => 'No recipients selected'
    ]);
    exit();
}

// Map type to icon
$iconMap = [
    'waste' => 'fa-trash-alt',
    'request' => 'fa-file-alt',
    'announcement' => 'fa-bullhorn',
    'alert' => 'fa-exclamation-triangle',
    'success' => 'fa-check-circle'
];
$icon = $iconMap[$type] ?? 'fa-bell';

// Map notification type to preference column
$preferenceMap = [
    'waste' => 'waste_reminders',
    'request' => 'request_updates',
    'announcement' => 'announcements'
];

try {
    $conn->begin_transaction();

    $targetUserIds = [];
    $userNames = [];

    // Determine which users to send notifications to
    if ($recipientType === 'all' || (count($userIds) === 1 && $userIds[0] === 'all')) {
        // "All Users" mode - get users based on preferences and street filter
        $preferenceColumn = $preferenceMap[$type] ?? null;

        // Build the query with street filter
        $whereConditions = [];
        $params = [];
        $types = "";

        if ($preferenceColumn) {
            // Add preference condition (enabled or NULL means enabled by default)
            $whereConditions[] = "(np.{$preferenceColumn} = 1 OR np.{$preferenceColumn} IS NULL)";
        }

        // Add street filter if provided and not "all"
        if ($streetFilter && $streetFilter !== 'all') {
            $whereConditions[] = "u.street_name = ?";
            $params[] = $streetFilter;
            $types .= "s";
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $userQuery = "
            SELECT u.id, u.first_name, u.middle_name, u.last_name, u.street_name
            FROM user u
            LEFT JOIN notification_preferences np ON u.id = np.user_id
            {$whereClause}
        ";

        if (!empty($params)) {
            $userStmt = $conn->prepare($userQuery);
            $userStmt->bind_param($types, ...$params);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
        } else {
            $userResult = $conn->query($userQuery);
        }

        while ($row = $userResult->fetch_assoc()) {
            $targetUserIds[] = $row['id'];
            $userNames[] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        }

        if (empty($targetUserIds)) {
            throw new Exception("No users found with this notification preference enabled" .
                ($streetFilter && $streetFilter !== 'all' ? " in {$streetFilter}" : ""));
        }

    } elseif ($recipientType === 'multiple' || count($userIds) > 1) {
        // Multiple specific users selected
        $preferenceColumn = $preferenceMap[$type] ?? null;

        // Build query to get users and check their preferences
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        if ($preferenceColumn) {
            $userQuery = "
                SELECT u.id, u.first_name, u.middle_name, u.last_name,
                       COALESCE(np.{$preferenceColumn}, 1) as preference_enabled
                FROM user u
                LEFT JOIN notification_preferences np ON u.id = np.user_id
                WHERE u.id IN ($placeholders)
            ";
        } else {
            $userQuery = "
                SELECT id, first_name, middle_name, last_name, 1 as preference_enabled 
                FROM user 
                WHERE id IN ($placeholders)
            ";
        }

        $userStmt = $conn->prepare($userQuery);
        
        // Convert userIds to integers
        $intUserIds = array_map('intval', $userIds);
        $types = str_repeat('i', count($intUserIds));
        $userStmt->bind_param($types, ...$intUserIds);
        $userStmt->execute();
        $userResult = $userStmt->get_result();

        while ($row = $userResult->fetch_assoc()) {
            // Only add users who have the preference enabled
            if ($row['preference_enabled']) {
                $targetUserIds[] = $row['id'];
                $userNames[] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
            }
        }

        if (empty($targetUserIds)) {
            throw new Exception("None of the selected users have this notification preference enabled");
        }

    } else {
        // Single user notification
        $userIdInt = intval($userIds[0]);
        $preferenceColumn = $preferenceMap[$type] ?? null;

        if ($preferenceColumn) {
            // Check if user has this notification type enabled
            $userCheck = $conn->prepare("
                SELECT u.first_name, u.middle_name, u.last_name, u.street_name,
                       COALESCE(np.{$preferenceColumn}, 1) as preference_enabled
                FROM user u
                LEFT JOIN notification_preferences np ON u.id = np.user_id
                WHERE u.id = ?
            ");
        } else {
            // For types without preference mapping, check user only
            $userCheck = $conn->prepare("
                SELECT first_name, middle_name, last_name, street_name, 1 as preference_enabled 
                FROM user 
                WHERE id = ?
            ");
        }

        $userCheck->bind_param("i", $userIdInt);
        $userCheck->execute();
        $userResult = $userCheck->get_result();

        if ($userResult->num_rows === 0) {
            throw new Exception("User not found");
        }

        $user = $userResult->fetch_assoc();

        // Check if user has disabled this notification type
        if (!$user['preference_enabled']) {
            echo json_encode([
                'success' => false,
                'message' => 'User has disabled this type of notification'
            ]);
            exit();
        }

        $targetUserIds[] = $userIdInt;
        $userNames[] = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
    }

    // Insert notification for each target user
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type, icon, title, message, is_read, created_at) 
        VALUES (?, ?, ?, ?, ?, 0, NOW())
    ");

    $insertedCount = 0;
    foreach ($targetUserIds as $uid) {
        $stmt->bind_param("issss", $uid, $type, $icon, $title, $message);
        if ($stmt->execute()) {
            $insertedCount++;
        }
    }

    // Log to activity_logs
    $activity = "Notification Created";
    
    if ($recipientType === 'all') {
        $streetInfo = ($streetFilter && $streetFilter !== 'all') ? " in {$streetFilter}" : " (all streets)";
        $description = "Created '$type' notification for all users with preference enabled ($insertedCount users{$streetInfo}). Title: '$title'";
    } elseif ($recipientType === 'multiple' || count($userNames) > 1) {
        $userList = implode(', ', array_slice($userNames, 0, 3));
        if (count($userNames) > 3) {
            $userList .= ' and ' . (count($userNames) - 3) . ' more';
        }
        $description = "Created '$type' notification for multiple users ($insertedCount recipients: $userList). Title: '$title'";
    } else {
        $userName = $userNames[0];
        $description = "Created '$type' notification for user '$userName' (ID: {$targetUserIds[0]}). Title: '$title'";
    }

    $logStmt = $conn->prepare("
        INSERT INTO activity_logs 
        (activity, description, created_at) 
        VALUES (?, ?, NOW())
    ");
    $logStmt->bind_param("ss", $activity, $description);
    $logStmt->execute();

    $conn->commit();

    // Build success message
    $successMessage = "Notification sent successfully";
    if ($insertedCount === 1) {
        $successMessage = "Notification sent to 1 user successfully";
    } else {
        $successMessage = "Notification sent to $insertedCount users successfully";
        if ($streetFilter && $streetFilter !== 'all') {
            $successMessage .= " in {$streetFilter}";
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'count' => $insertedCount
    ]);

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