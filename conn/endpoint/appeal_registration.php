<?php
session_start();
require_once("../conn.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $firstname = trim($_POST["firstname"]);
    $middle = trim($_POST["middle"]);
    $lastname = trim($_POST["lastname"]);
    $contact = trim($_POST["contact"]);
    $houseNumber = trim($_POST["house_number"]);
    $streetName = trim($_POST["street_name"]);
    $govIdType = trim($_POST["gov_id_type"]);
    
    // Validate email exists and is rejected
    $checkStmt = $conn->prepare("SELECT id, is_approved FROM user WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found in system.']);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    if ($user['is_approved'] !== 'rejected') {
        echo json_encode(['success' => false, 'message' => 'This account is not in rejected status.']);
        exit();
    }
    
    $userId = $user['id'];
    
    // ✅ Define separate upload directories
    $selfieDir = "../../uploads/selfies/";
    $govIdDir = "../../uploads/gov_id/";
    
    // Ensure directories exist
    if (!file_exists($selfieDir)) {
        mkdir($selfieDir, 0777, true);
    }
    if (!file_exists($govIdDir)) {
        mkdir($govIdDir, 0777, true);
    }
    
    // Upload selfie
    $selfieFileName = null;
    if (isset($_FILES['selfie_image']) && $_FILES['selfie_image']['error'] === 0) {
        $selfieFileName = uniqid('selfie_') . '_' . time() . '.' . pathinfo($_FILES['selfie_image']['name'], PATHINFO_EXTENSION);
        $selfieTarget = $selfieDir . $selfieFileName;
        
        if (!move_uploaded_file($_FILES['selfie_image']['tmp_name'], $selfieTarget)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload selfie image.']);
            exit();
        }
    }
    
    // Upload government ID
    $govIdFileName = null;
    if (isset($_FILES['gov_id_image']) && $_FILES['gov_id_image']['error'] === 0) {
        $govIdFileName = uniqid('govid_') . '_' . time() . '.' . pathinfo($_FILES['gov_id_image']['name'], PATHINFO_EXTENSION);
        $govIdTarget = $govIdDir . $govIdFileName;
        
        if (!move_uploaded_file($_FILES['gov_id_image']['tmp_name'], $govIdTarget)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload government ID image.']);
            exit();
        }
    }
    
    // Update user information
    $updateStmt = $conn->prepare("
        UPDATE user 
        SET first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            contact_number = ?,
            house_number = ?,
            street_name = ?,
            selfie_image = COALESCE(?, selfie_image),
            gov_id_type = ?,
            gov_id_image = COALESCE(?, gov_id_image),
            is_approved = 'pending'
        WHERE id = ?
    ");
    
    $updateStmt->bind_param(
        "sssssssssi",
        $firstname,
        $middle,
        $lastname,
        $contact,
        $houseNumber,
        $streetName,
        $selfieFileName,
        $govIdType,
        $govIdFileName,
        $userId
    );
    
    if ($updateStmt->execute()) {
        // Log the appeal activity
        $activity = "Registration Appeal";
        $description = "User {$firstname} {$lastname} ({$email}) has resubmitted their registration after rejection.";
        $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
        $logStmt->bind_param("ss", $activity, $description);
        $logStmt->execute();
        $logStmt->close();
        
        unset($_SESSION['rejected_email']);
        echo json_encode(['success' => true, 'message' => 'Appeal submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update information.']);
    }
    
    $updateStmt->close();
    $checkStmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>