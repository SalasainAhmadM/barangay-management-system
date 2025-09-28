<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

$id = intval($_POST["id"] ?? 0);
$firstName = trim($_POST["first_name"] ?? "");
$middleName = trim($_POST["middle_name"] ?? "");
$lastName = trim($_POST["last_name"] ?? "");
$email = trim($_POST["email"] ?? "");

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
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $dateNow = date("Ymd_His");

        // Example: Doe_20250927_223045.jpg
        $imageFileName = $lastName . "_" . $dateNow . "." . $ext;
        $targetPath = $uploadDir . $imageFileName;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            echo json_encode(["success" => false, "message" => "Failed to upload profile image"]);
            exit();
        }
    }

    // ✅ Update admin
    if ($imageFileName) {
        $stmt = $conn->prepare("UPDATE admin 
            SET first_name = ?, middle_name = ?, last_name = ?, email = ?, 
                image = ?, updated_at = NOW() 
            WHERE id = ?");
        $stmt->bind_param(
            "sssssi",
            $firstName,
            $middleName,
            $lastName,
            $email,
            $imageFileName,
            $id
        );
    } else {
        $stmt = $conn->prepare("UPDATE admin 
            SET first_name = ?, middle_name = ?, last_name = ?, email = ?, 
                updated_at = NOW() 
            WHERE id = ?");
        $stmt->bind_param(
            "ssssi",
            $firstName,
            $middleName,
            $lastName,
            $email,
            $id
        );
    }

    $stmt->execute();

    // ✅ Insert into activity_logs
    $activity = "Admin Update";
    $description = $imageFileName
        ? "admin profile has been updated"
        : "admin credentials has been updated";

    $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
    $logStmt->bind_param("ss", $activity, $description);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
