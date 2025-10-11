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

// Validate notification ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Notification ID is required'
    ]);
    exit();
}

$notificationId = intval($_GET['id']);

try {
    $stmt = $conn->prepare("
        SELECT 
            n.*,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email,
            u.contact_number
        FROM notifications n
        INNER JOIN user u ON n.user_id = u.id
        WHERE n.id = ?
    ");

    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Notification not found'
        ]);
        exit();
    }

    $notification = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'notification' => $notification
    ]);

} catch (Exception $e) {
    error_log("Error in get_notification.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>