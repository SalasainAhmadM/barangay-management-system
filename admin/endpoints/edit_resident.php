<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

$id = intval($_POST["id"] ?? 0);
$firstName = trim($_POST["firstName"] ?? "");
$middleName = trim($_POST["middleName"] ?? "");
$lastName = trim($_POST["lastName"] ?? "");
$email = trim($_POST["email"] ?? "");
$contactNumber = trim($_POST["contactNumber"] ?? "");
$dateOfBirth = $_POST["dateOfBirth"] ?? null;
$gender = $_POST["gender"] ?? null;
$civilStatus = $_POST["civilStatus"] ?? null;
$occupation = trim($_POST["occupation"] ?? "");
$houseNumber = trim($_POST["houseNumber"] ?? "");
$streetName = trim($_POST["streetName"] ?? "");
$barangay = trim($_POST["barangay"] ?? "Baliwasan");
$status = $_POST["status"] ?? "active";

// Validate required fields
if ($id <= 0 || empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing"]);
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit();
}

// Validate contact number format (09xxxxxxxxx)
if (!preg_match("/^09\d{9}$/", $contactNumber)) {
    echo json_encode(["success" => false, "message" => "Invalid contact number format. Must start with 09 and be 11 digits."]);
    exit();
}

try {
    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    $stmt->close();

    // Check for duplicate contact number
    $stmt = $conn->prepare("SELECT id FROM user WHERE contact_number = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $contactNumber, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Contact number already exists"]);
        exit();
    }
    $stmt->close();

    $imageFileName = null;
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $uploadDir = "../../assets/images/user/";
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $dateNow = date("Ymd_His");

        // Example: Santos_09123456789_20250921_223045.jpg
        $imageFileName = $lastName . "_" . $contactNumber . "_" . $dateNow . "." . $ext;
        $targetPath = $uploadDir . $imageFileName;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            echo json_encode(["success" => false, "message" => "Failed to upload image"]);
            exit();
        }
    }

    // ✅ Update resident with ALL fields
    if ($imageFileName) {
        $stmt = $conn->prepare("UPDATE user 
            SET first_name = ?, middle_name = ?, last_name = ?, email = ?, contact_number = ?, 
                date_of_birth = ?, gender = ?, civil_status = ?, occupation = ?, 
                house_number = ?, street_name = ?, barangay = ?, status = ?, 
                image = ?, updated_at = NOW() 
            WHERE id = ?");
        $stmt->bind_param(
            "ssssssssssssssi",
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
            $status,
            $imageFileName,
            $id
        );
    } else {
        $stmt = $conn->prepare("UPDATE user 
            SET first_name = ?, middle_name = ?, last_name = ?, email = ?, contact_number = ?, 
                date_of_birth = ?, gender = ?, civil_status = ?, occupation = ?, 
                house_number = ?, street_name = ?, barangay = ?, status = ?, 
                updated_at = NOW() 
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
            $status,
            $id
        );
    }
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
