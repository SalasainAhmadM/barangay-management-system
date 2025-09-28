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
    // ✅ Fetch old data BEFORE update
    $oldDataStmt = $conn->prepare("SELECT first_name, middle_name, last_name, house_number, street_name, barangay, image FROM user WHERE id = ?");
    $oldDataStmt->bind_param("i", $userId);
    $oldDataStmt->execute();
    $oldDataResult = $oldDataStmt->get_result();
    $oldData = $oldDataResult->fetch_assoc();
    $oldDataStmt->close();

    $oldFullName = trim($oldData["first_name"] . " " . $oldData["middle_name"] . " " . $oldData["last_name"]);
    $oldAddress = trim($oldData["house_number"] . " " . $oldData["street_name"] . ", " . $oldData["barangay"]);
    $oldImage = $oldData["image"] ?? null;

    // Get form data
    $firstName = trim($_POST["firstName"]) ?: null;
    $middleName = trim($_POST["middleName"]) ?: null;
    $lastName = trim($_POST["lastName"]) ?: null;
    $email = trim($_POST["email"]) ?: null;
    $contactNumber = trim($_POST["contactNumber"]) ?: null;
    $dateOfBirth = $_POST["dateOfBirth"] ?: null;
    $gender = $_POST["gender"] ?: null;
    $civilStatus = $_POST["civilStatus"] ?: null;
    $occupation = trim($_POST["occupation"]) ?: null;
    $houseNumber = trim($_POST["houseNumber"]) ?: null;
    $streetName = trim($_POST["streetName"]) ?: null;
    $barangay = trim($_POST["barangay"]) ?: "Baliwasan";

    // Validate required fields
    if (!$firstName || !$lastName || !$email || !$contactNumber) {
        echo json_encode(["success" => false, "message" => "First name, last name, email, and contact number are required"]);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format"]);
        exit();
    }

    // Validate contact number format (must start with 09 and be 11 digits)
    if (!preg_match("/^09\d{9}$/", $contactNumber)) {
        echo json_encode(["success" => false, "message" => "Contact number must start with 09 and be 11 digits long"]);
        exit();
    }

    // Check if email already exists for other users
    $emailCheckStmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $emailCheckStmt->bind_param("si", $email, $userId);
    $emailCheckStmt->execute();
    $emailResult = $emailCheckStmt->get_result();

    if ($emailResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email address already exists"]);
        exit();
    }

    // Handle image upload
    $imageName = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
        $fileType = $_FILES["image"]["type"];

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(["success" => false, "message" => "Only JPEG, PNG, and GIF images are allowed"]);
            exit();
        }

        // Check file size (5MB limit)
        if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
            echo json_encode(["success" => false, "message" => "Image size must be less than 5MB"]);
            exit();
        }

        // Generate unique filename
        $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $imageName = "profile_" . $userId . "_" . time() . "." . $extension;
        $uploadPath = "../../assets/images/user/" . $imageName;

        // Create uploads directory if it doesn't exist
        if (!file_exists("../../assets/images/user/")) {
            mkdir("../../assets/images/user/", 0777, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $uploadPath)) {
            echo json_encode(["success" => false, "message" => "Failed to upload image"]);
            exit();
        }

        // Delete old image if it exists
        if ($oldImage && $oldImage != '') {
            $oldImagePath = "../../assets/images/user/" . $oldImage;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
    }

    // Prepare update query based on whether image is being updated
    if ($imageName) {
        // Update with image
        $stmt = $conn->prepare("UPDATE user SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                email = ?, 
                contact_number = ?, 
                date_of_birth = ?, 
                gender = ?, 
                civil_status = ?, 
                occupation = ?, 
                house_number = ?, 
                street_name = ?, 
                barangay = ?, 
                image = ?
            WHERE id = ?");
        $stmt->bind_param(
            "sssssssssssssi",
            $firstName,
            $middleName,
            $lastName,
            $email,
            $contactNumber,
            $dateOfBirth,
            $gender,
            $civilStatus,
            $occupation,
            $houseNumber,
            $streetName,
            $barangay,
            $imageName,
            $userId
        );
    } else {
        // Update without image
        $stmt = $conn->prepare("UPDATE user SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                email = ?, 
                contact_number = ?, 
                date_of_birth = ?, 
                gender = ?, 
                civil_status = ?, 
                occupation = ?, 
                house_number = ?, 
                street_name = ?, 
                barangay = ?
            WHERE id = ?");
        $stmt->bind_param(
            "ssssssssssssi",
            $firstName,
            $middleName,
            $lastName,
            $email,
            $contactNumber,
            $dateOfBirth,
            $gender,
            $civilStatus,
            $occupation,
            $houseNumber,
            $streetName,
            $barangay,
            $userId
        );
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // ✅ New values after update
            $newFullName = trim($firstName . " " . $middleName . " " . $lastName);
            $newAddress = trim($houseNumber . " " . $streetName . ", " . $barangay);

            // ✅ Default description
            $description = "Resident {$newFullName} profile has been updated";

            // ✅ Detect changes
            $nameChanged = ($oldFullName !== $newFullName);
            $addressChanged = ($oldAddress !== $newAddress);
            $profileChanged = ($imageName !== null);

            if ($nameChanged && !$addressChanged && !$profileChanged) {
                $description = "Resident name was changed from {$oldFullName} to {$newFullName}";
            } elseif (!$nameChanged && $addressChanged && !$profileChanged) {
                $description = "Resident address was changed from {$oldAddress} to {$newAddress}";
            } elseif (!$nameChanged && !$addressChanged && $profileChanged) {
                $description = "Resident {$newFullName} profile picture was updated";
            } elseif ($nameChanged && $addressChanged) {
                $description = "Resident {$oldFullName}'s name and address were updated";
            } elseif ($nameChanged && $profileChanged) {
                $description = "Resident {$oldFullName}'s name and profile picture were updated";
            } elseif ($addressChanged && $profileChanged) {
                $description = "Resident {$oldFullName}'s address and profile picture were updated";
            }

            // ✅ Insert activity log
            $activityStmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
            $activity = "Profile Update";
            $activityStmt->bind_param("ss", $activity, $description);
            $activityStmt->execute();
            $activityStmt->close();

            echo json_encode([
                "success" => true,
                "message" => "Profile updated successfully",
                "image" => $imageName
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "No changes were made to the profile"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile: " . $stmt->error]);
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error occurred. Please try again."]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>