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
            id,
            first_name,
            middle_name,
            last_name,
            email
        FROM user 
        ORDER BY first_name ASC, last_name ASC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    error_log("Error in get_all_users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>