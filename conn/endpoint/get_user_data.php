<?php
session_start();
require_once("../conn.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    
    $stmt = $conn->prepare("
        SELECT 
            first_name, 
            middle_name, 
            last_name, 
            contact_number,
            house_number,
            street_name,
            selfie_image,
            gov_id_type,
            gov_id_image
        FROM user 
        WHERE email = ? AND is_approved = 'rejected'
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $userData = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $userData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or not rejected.']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>