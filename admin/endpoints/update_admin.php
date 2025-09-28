<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

$id = intval($_POST["id"] ?? 0);
$firstName = trim($_POST["first_name"] ?? "");
$middleName = trim($_POST["middle_name"] ?? "");
$lastName = trim($_POST["last_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$changePassword = isset($_POST["change_password"]) && $_POST["change_password"] === '1';
$newPassword = trim($_POST["new_password"] ?? "");

// ✅ Validate required fields
if ($id <= 0 || empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing"]);
    exit();
}

// ✅ Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit();
}

// ✅ Validate password if changing
if ($changePassword) {
    if (empty($newPassword)) {
        echo json_encode(["success" => false, "message" => "New password is required"]);
        exit();
    }

    // Strong password validation - same as frontend
    $regexStrong = '/(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/';
    if (strlen($newPassword) < 8 || !preg_match($regexStrong, $newPassword)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long"]);
        exit();
    }
}

try {
    // Check for duplicate email (excluding current admin)
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    $stmt->close();

    $imageFileName = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "../../assets/images/user/";

        // Validate image file
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedTypes)) {
            echo json_encode(["success" => false, "message" => "Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed"]);
            exit();
        }

        // Check file size (5MB max)
        if ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
            echo json_encode(["success" => false, "message" => "Image file is too large. Maximum size is 5MB"]);
            exit();
        }

        $dateNow = date("Ymd_His");
        // Example: Doe_20250927_223045.jpg
        $imageFileName = $lastName . "_" . $dateNow . "." . $ext;
        $targetPath = $uploadDir . $imageFileName;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            echo json_encode(["success" => false, "message" => "Failed to upload profile image"]);
            exit();
        }
    }

    // ✅ Update admin - Build query dynamically based on what needs to be updated
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    // Always update basic info
    $updateFields[] = "first_name = ?";
    $updateFields[] = "middle_name = ?";
    $updateFields[] = "last_name = ?";
    $updateFields[] = "email = ?";
    $updateFields[] = "updated_at = NOW()";

    $params[] = $firstName;
    $params[] = $middleName;
    $params[] = $lastName;
    $params[] = $email;
    $paramTypes .= "ssss";

    // Add image if uploaded
    if ($imageFileName) {
        $updateFields[] = "image = ?";
        $params[] = $imageFileName;
        $paramTypes .= "s";
    }

    // Add password if changing
    if ($changePassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateFields[] = "password = ?";
        $params[] = $hashedPassword;
        $paramTypes .= "s";
    }

    // Add ID for WHERE clause
    $params[] = $id;
    $paramTypes .= "i";

    $sql = "UPDATE admin SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "No changes were made or admin not found"]);
        exit();
    }

    $stmt->close();

    // ✅ Insert into activity_logs
    $activity = "Admin Update";
    $descriptionParts = [];

    if ($imageFileName) {
        $descriptionParts[] = "profile image";
    }
    if ($changePassword) {
        $descriptionParts[] = "password";
    }
    if (empty($descriptionParts)) {
        $descriptionParts[] = "credentials";
    }

    $description = "Admin " . implode(" and ", $descriptionParts) . " has been updated";

    $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
    $logStmt->bind_param("ss", $activity, $description);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(["success" => true, "message" => "Admin updated successfully"]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>