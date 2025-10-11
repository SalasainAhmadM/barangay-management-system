<?php
session_start();
require_once("../../conn/conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION["admin_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.type,
            n.title,
            n.message,
            n.is_read,
            n.created_at,
            u.first_name,
            u.middle_name,
            u.last_name,
            u.email
        FROM notifications n
        INNER JOIN user u ON n.user_id = u.id
        ORDER BY n.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => count($notifications)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}
?>