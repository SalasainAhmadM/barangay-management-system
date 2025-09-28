<?php
require_once("../../conn/conn.php");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit();
}

$firstName = trim($_POST["firstName"] ?? "");
$middleName = trim($_POST["middleName"] ?? "");
$lastName = trim($_POST["lastName"] ?? "");
$email = trim($_POST["email"] ?? "");
$contactNumber = trim($_POST["contactNumber"] ?? "");
$password = trim($_POST["password"] ?? "");
$date_of_birth = trim($_POST["dateOfBirth"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$civil_status = trim($_POST["civilStatus"] ?? "");
$occupation = trim($_POST["occupation"] ?? "");
$house_number = trim($_POST["houseNumber"] ?? "");
$street_name = trim($_POST["streetName"] ?? "");
$barangay = trim($_POST["barangay"] ?? "Baliwasan");
$status = trim($_POST["status"] ?? "active");

// ✅ Validate required fields
if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber) || empty($password)) {
    echo json_encode(["success" => false, "message" => "First name, last name, email, contact number, and password are required"]);
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

// ✅ Validate password strength
$regexStrong = '/(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/';
if (strlen($password) < 8 || !preg_match($regexStrong, $password)) {
    echo json_encode(["success" => false, "message" => "Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long"]);
    exit();
}

try {
    // ✅ Check email uniqueness
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Email already exists"]);
        exit();
    }
    $stmt->close();

    // ✅ Check contact number uniqueness
    $stmt = $conn->prepare("SELECT id FROM user WHERE contact_number = ? LIMIT 1");
    $stmt->bind_param("s", $contactNumber);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Contact number already exists"]);
        exit();
    }
    $stmt->close();

    // ✅ Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // ✅ Handle Image Upload
    $imageFileName = "";
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

    // ✅ Insert into DB
    $stmt = $conn->prepare("INSERT INTO user 
        (first_name, middle_name, last_name, email, contact_number, password, date_of_birth, gender, civil_status, occupation, house_number, street_name, barangay, status, image, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param(
        "sssssssssssssss",
        $firstName,
        $middleName,
        $lastName,
        $email,
        $contactNumber,
        $hashedPassword,
        $date_of_birth,
        $gender,
        $civil_status,
        $occupation,
        $house_number,
        $street_name,
        $barangay,
        $status,
        $imageFileName
    );

    if ($stmt->execute()) {
        // ✅ Insert Activity Log
        $fullName = trim($firstName . " " . $middleName . " " . $lastName);
        $fullAddress = trim($house_number . " " . $street_name . ", " . $barangay);
        $activity = "New Resident";
        $description = "Added new resident {$fullName} from {$fullAddress}";

        $logStmt = $conn->prepare("INSERT INTO activity_logs (activity, description, created_at) VALUES (?, ?, NOW())");
        $logStmt->bind_param("ss", $activity, $description);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode(["success" => true, "message" => "Resident added successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add resident"]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>