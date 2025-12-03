<?php
session_start();
require_once("../conn.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if this is the initial registration or verification step
    if (isset($_FILES['selfie_image']) && isset($_FILES['gov_id_image'])) {
        // This is the verification step
        handleVerificationUpload($conn);
    } else {
        // This is the initial registration step
        handleInitialRegistration($conn);
    }
}

function handleInitialRegistration($conn)
{
    $first_name = trim($_POST["student_firstname"]);
    $middle_name = trim($_POST["student_middle"]);
    $last_name = trim($_POST["student_lastname"]);
    $email = trim($_POST["email"]);
    $contact = trim($_POST["contact"]);
    $password = trim($_POST["password"]);
    $confirm_pass = trim($_POST["confirm-password"]);

    // Get address fields
    $house_number = trim($_POST["house_number"]);
    $street_name = trim($_POST["street_name"]);
    $barangay = isset($_POST["barangay"]) ? trim($_POST["barangay"]) : "Baliwasan";

    // Save form values to session
    $_SESSION['form_values'] = [
        'student_firstname' => $first_name,
        'student_middle' => $middle_name,
        'student_lastname' => $last_name,
        'email' => $email,
        'contact' => $contact,
        'house_number' => $house_number,
        'street_name' => $street_name
    ];

    // Initialize field errors array
    $_SESSION['field_errors'] = [];

    // Validate password match
    if ($password !== $confirm_pass) {
        $_SESSION["register_error"] = "Passwords do not match.";
        $_SESSION['field_errors']['password'] = "Passwords do not match";
        header("Location: ../../index.php?register=error");
        exit();
    }

    // Check if email exists in user table
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["register_error"] = "Email already registered.";
        $_SESSION['field_errors']['email'] = "This email is already registered";
        header("Location: ../../index.php?register=error");
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Check if email exists in admin table
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["register_error"] = "Email already registered.";
        $_SESSION['field_errors']['email'] = "This email is already registered";
        header("Location: ../../index.php?register=error");
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Check if contact_number exists in user table
    $stmt = $conn->prepare("SELECT id FROM user WHERE contact_number = ?");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION["register_error"] = "Contact number already registered.";
        $_SESSION['field_errors']['contact'] = "This contact number is already registered";
        header("Location: ../../index.php?register=error");
        $stmt->close();
        exit();
    }
    $stmt->close();

    // REMOVED: Address duplicate check - users can now have the same address

    // If all validations pass, clear field errors and form values
    unset($_SESSION['field_errors']);

    // Store registration data in session for verification step
    $_SESSION['pending_registration'] = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'email' => $email,
        'contact' => $contact,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'house_number' => $house_number,
        'street_name' => $street_name,
        'barangay' => $barangay
    ];

    // Clear form values since we're proceeding to verification
    unset($_SESSION['form_values']);

    $_SESSION["show_verification"] = true;
    header("Location: ../../index.php?register=verify");
    exit();
}

function handleVerificationUpload($conn)
{
    if (!isset($_SESSION['pending_registration'])) {
        $_SESSION["register_error"] = "Session expired. Please register again.";
        header("Location: ../../index.php?register=error");
        exit();
    }

    $reg_data = $_SESSION['pending_registration'];
    $gov_id_type = trim($_POST['gov_id_type']);

    // Create upload directories if they don't exist
    $selfie_dir = "../../uploads/selfies/";
    $gov_id_dir = "../../uploads/gov_id/";

    if (!file_exists($selfie_dir)) {
        mkdir($selfie_dir, 0755, true);
    }
    if (!file_exists($gov_id_dir)) {
        mkdir($gov_id_dir, 0755, true);
    }

    // Generate filename components
    $full_name = $reg_data['first_name'] . '-' . $reg_data['middle_name'] . '-' . $reg_data['last_name'];
    $full_name = preg_replace('/[^A-Za-z0-9\-]/', '', $full_name); // Remove special characters
    $date_stamp = date('Y-m-d-His');

    // Handle selfie upload
    $selfie_file = $_FILES['selfie_image'];
    $selfie_ext = pathinfo($selfie_file['name'], PATHINFO_EXTENSION);
    $selfie_filename = $full_name . '-' . $date_stamp . '.' . $selfie_ext;
    $selfie_path = $selfie_dir . $selfie_filename;

    // Handle government ID upload
    $gov_id_file = $_FILES['gov_id_image'];
    $gov_id_ext = pathinfo($gov_id_file['name'], PATHINFO_EXTENSION);
    $gov_id_filename = $gov_id_type . '-' . $full_name . '-' . $date_stamp . '.' . $gov_id_ext;
    $gov_id_path = $gov_id_dir . $gov_id_filename;

    // Validate file types (only images)
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    if (!in_array($selfie_file['type'], $allowed_types)) {
        $_SESSION["register_error"] = "Selfie must be an image file (JPG, PNG, or GIF).";
        header("Location: ../../index.php?register=verify");
        exit();
    }

    if (!in_array($gov_id_file['type'], $allowed_types)) {
        $_SESSION["register_error"] = "Government ID must be an image file (JPG, PNG, or GIF).";
        header("Location: ../../index.php?register=verify");
        exit();
    }

    // Move uploaded files
    if (!move_uploaded_file($selfie_file['tmp_name'], $selfie_path)) {
        $_SESSION["register_error"] = "Failed to upload selfie. Please try again.";
        header("Location: ../../index.php?register=verify");
        exit();
    }

    if (!move_uploaded_file($gov_id_file['tmp_name'], $gov_id_path)) {
        // Clean up selfie if gov ID upload fails
        unlink($selfie_path);
        $_SESSION["register_error"] = "Failed to upload government ID. Please try again.";
        header("Location: ../../index.php?register=verify");
        exit();
    }

    // Insert user with verification data
    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, contact_number, password, house_number, street_name, barangay, selfie_image, gov_id_type, gov_id_image, is_approved) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

    $stmt->bind_param(
        "ssssssssssss",
        $reg_data['first_name'],
        $reg_data['middle_name'],
        $reg_data['last_name'],
        $reg_data['email'],
        $reg_data['contact'],
        $reg_data['password'],
        $reg_data['house_number'],
        $reg_data['street_name'],
        $reg_data['barangay'],
        $selfie_filename,
        $gov_id_type,
        $gov_id_filename
    );

    if ($stmt->execute()) {
        // Log activity
        $activity = "New Account - Pending Approval";
        $description = "A new user account was created for {$reg_data['first_name']} {$reg_data['middle_name']} {$reg_data['last_name']} ({$reg_data['email']}) at {$reg_data['house_number']} {$reg_data['street_name']}, {$reg_data['barangay']}. Pending admin approval.";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $activity, $description);
        $log_stmt->execute();
        $log_stmt->close();

        // Clear session data
        unset($_SESSION['pending_registration']);
        unset($_SESSION['show_verification']);

        $_SESSION["register_success"] = "Registration complete! Please wait for admin approval.";
        header("Location: ../../index.php?register=success");
        exit();
    } else {
        // Clean up uploaded files on failure
        unlink($selfie_path);
        unlink($gov_id_path);

        $_SESSION["register_error"] = "Something went wrong. Try again.";
        header("Location: ../../index.php?register=verify");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>