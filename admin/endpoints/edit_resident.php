<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

$id = intval($_POST["id"] ?? 0);
$firstName = trim($_POST["firstName"] ?? "");
$middleName = trim($_POST["middleName"] ?? "");
$lastName = trim($_POST["lastName"] ?? "");
$email = trim($_POST["email"] ?? "");
$contactNumber = trim($_POST["contactNumber"] ?? "");
$changePassword = isset($_POST["changePassword"]) && $_POST["changePassword"] === '1';
$password = trim($_POST["password"] ?? "");
$dateOfBirth = $_POST["dateOfBirth"] ?? null;
$gender = $_POST["gender"] ?? null;
$civilStatus = $_POST["civilStatus"] ?? null;
$occupation = trim($_POST["occupation"] ?? "");
$houseNumber = trim($_POST["houseNumber"] ?? "");
$streetName = trim($_POST["streetName"] ?? "");
$barangay = trim($_POST["barangay"] ?? "Baliwasan");
$status = $_POST["status"] ?? "active";

if ($id <= 0 || empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing"]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address"]);
    exit();
}

if (!preg_match("/^09\d{9}$/", $contactNumber)) {
    echo json_encode(["success" => false, "message" => "Invalid contact number format. Must start with 09 and be 11 digits."]);
    exit();
}

// ✅ Validate password if changing
if ($changePassword) {
    if (empty($password)) {
        echo json_encode(["success" => false, "message" => "New password is required"]);
        exit();
    }

    // Strong password validation - same as frontend
    $regexStrong = '/(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/';
    if (strlen($password) < 8 || !preg_match($regexStrong, $password)) {
        echo json_encode(["success" => false, "message" => "Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long"]);
        exit();
    }
}

try {
    // ✅ Fetch old data for comparison
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, house_number, street_name, barangay, image 
                            FROM user WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $oldData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$oldData) {
        echo json_encode(["success" => false, "message" => "Resident not found"]);
        exit();
    }

    $oldFullName = trim($oldData["first_name"] . " " . $oldData["middle_name"] . " " . $oldData["last_name"]);
    $oldAddress = trim($oldData["house_number"] . " " . $oldData["street_name"] . ", " . $oldData["barangay"]);

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    $stmt->close();

    // Check duplicate contact number
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
        $imageFileName = $lastName . "_" . $contactNumber . "_" . $dateNow . "." . $ext;
        $targetPath = $uploadDir . $imageFileName;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
            echo json_encode(["success" => false, "message" => "Failed to upload image"]);
            exit();
        }
    }

    // ✅ Update resident - Build query dynamically based on what needs to be updated
    $updateFields = [];
    $params = [];
    $paramTypes = "";

    // Always update basic info
    $updateFields[] = "first_name = ?";
    $updateFields[] = "middle_name = ?";
    $updateFields[] = "last_name = ?";
    $updateFields[] = "email = ?";
    $updateFields[] = "contact_number = ?";
    $updateFields[] = "date_of_birth = ?";
    $updateFields[] = "gender = ?";
    $updateFields[] = "civil_status = ?";
    $updateFields[] = "occupation = ?";
    $updateFields[] = "house_number = ?";
    $updateFields[] = "street_name = ?";
    $updateFields[] = "barangay = ?";
    $updateFields[] = "status = ?";
    $updateFields[] = "updated_at = NOW()";

    $params[] = $firstName;
    $params[] = $middleName;
    $params[] = $lastName;
    $params[] = $email;
    $params[] = $contactNumber;
    $params[] = $dateOfBirth;
    $params[] = $gender;
    $params[] = $civilStatus;
    $params[] = $occupation;
    $params[] = $houseNumber;
    $params[] = $streetName;
    $params[] = $barangay;
    $params[] = $status;
    $paramTypes .= "sssssssssssss";

    // Add image if uploaded
    if ($imageFileName) {
        $updateFields[] = "image = ?";
        $params[] = $imageFileName;
        $paramTypes .= "s";
    }

    // Add password if changing
    if ($changePassword) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updateFields[] = "password = ?";
        $params[] = $hashedPassword;
        $paramTypes .= "s";
    }

    // Add ID for WHERE clause
    $params[] = $id;
    $paramTypes .= "i";

    $sql = "UPDATE user SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "No changes were made or resident not found"]);
        exit();
    }

    $stmt->close();

    // ✅ Activity Logs
    $newFullName = trim($firstName . " " . $middleName . " " . $lastName);
    $newAddress = trim($houseNumber . " " . $streetName . ", " . $barangay);

    $description = "Resident {$newFullName} credentials has been updated"; // default

    $nameChanged = ($oldFullName !== $newFullName);
    $addressChanged = ($oldAddress !== $newAddress);
    $profileChanged = ($imageFileName !== null);
    $passwordChanged = $changePassword;

    if ($nameChanged && !$addressChanged && !$profileChanged && !$passwordChanged) {
        $description = "Resident name was changed from {$oldFullName} to {$newFullName}";
    } elseif (!$nameChanged && $addressChanged && !$profileChanged && !$passwordChanged) {
        $description = "Resident address was changed from {$oldAddress} to {$newAddress}";
    } elseif (!$nameChanged && !$addressChanged && $profileChanged && !$passwordChanged) {
        $description = "Resident {$newFullName} profile was updated";
    } elseif (!$nameChanged && !$addressChanged && !$profileChanged && $passwordChanged) {
        $description = "Resident {$newFullName} password was updated";
    } else {
        // Multiple changes
        $changes = [];
        if ($nameChanged)
            $changes[] = "name";
        if ($addressChanged)
            $changes[] = "address";
        if ($profileChanged)
            $changes[] = "profile image";
        if ($passwordChanged)
            $changes[] = "password";

        if (count($changes) > 0) {
            $description = "Resident {$newFullName} " . implode(" and ", $changes) . " has been updated";
        }
    }

    $stmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
    $activity = "Edit Resident";
    $stmt->bind_param("ss", $activity, $description);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => true, "message" => "Resident updated successfully"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>