<?php
require_once("../../conn/conn.php");
session_start();

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

$userId = $_SESSION["user_id"];

try {
    // Select all required fields for the logged-in user
    $stmt = $conn->prepare("SELECT 
            id, 
            first_name, 
            middle_name, 
            last_name, 
            email, 
            contact_number, 
            date_of_birth, 
            gender, 
            civil_status, 
            occupation, 
            house_number, 
            street_name, 
            barangay, 
            status, 
            image, 
            created_at, 
            updated_at
        FROM user
        WHERE id = ? LIMIT 1");

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();

    if ($profile) {
        // Ensure nulls are handled properly
        foreach ($profile as $key => $value) {
            if ($value === null) {
                $profile[$key] = null;
            }
        }

        // Ensure image field is properly handled (null if empty)
        $profile['image'] = !empty($profile['image']) ? $profile['image'] : null;

        echo json_encode(["success" => true, "profile" => $profile]);
    } else {
        echo json_encode(["success" => false, "message" => "Profile not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>