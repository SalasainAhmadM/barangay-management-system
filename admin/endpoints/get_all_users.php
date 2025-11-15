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
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number,
            u.street_name,
            COALESCE(np.waste_reminders, 1) as waste_reminders,
            COALESCE(np.request_updates, 1) as request_updates,
            COALESCE(np.announcements, 1) as announcements,
            COALESCE(np.sms_notifications, 0) as sms_notifications
        FROM user u
        LEFT JOIN notification_preferences np ON u.id = np.user_id
        ORDER BY u.first_name ASC, u.last_name ASC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'contact_number' => $row['contact_number'],
            'street_name' => $row['street_name'] ?? '',
            'preferences' => [
                'waste_reminders' => (bool) $row['waste_reminders'],
                'request_updates' => (bool) $row['request_updates'],
                'announcements' => (bool) $row['announcements'],
                'sms_notifications' => (bool) $row['sms_notifications']
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    error_log("Error in get_all_users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch users: ' . $e->getMessage()
    ]);
}

$conn->close();
?>