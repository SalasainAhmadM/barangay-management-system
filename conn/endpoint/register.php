<?php
session_start();
require_once("../conn.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
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

    // Validate
    if ($password !== $confirm_pass) {
        $_SESSION["register_error"] = "Passwords do not match.";
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
        header("Location: ../../index.php?register=error");
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
        header("Location: ../../index.php?register=error");
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
        header("Location: ../../index.php?register=error");
        exit();
    }
    $stmt->close();

    // Check if address (house_number + street_name) already exists
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM user WHERE house_number = ? AND street_name = ? AND barangay = ?");
    $stmt->bind_param("sss", $house_number, $street_name, $barangay);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $existing_user = $result->fetch_assoc();
        $existing_name = $existing_user['first_name'];
        if (!empty($existing_user['middle_name'])) {
            $existing_name .= " " . strtoupper(substr($existing_user['middle_name'], 0, 1)) . ".";
        }
        $existing_name .= " " . $existing_user['last_name'];

        $_SESSION["register_error"] = "This address (House #: {$house_number}, Street: {$street_name}) is already registered under {$existing_name}.";
        header("Location: ../../index.php?register=error");
        exit();
    }
    $stmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert with address fields
    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, contact_number, password, house_number, street_name, barangay) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $first_name, $middle_name, $last_name, $email, $contact, $hashedPassword, $house_number, $street_name, $barangay);

    if ($stmt->execute()) {

        $activity = "New Account";
        $description = "A new user account was created for {$first_name} {$middle_name} {$last_name} ({$email}) at {$house_number} {$street_name}, {$barangay}";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $activity, $description);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION["register_success"] = "Account created successfully!";
        header("Location: ../../index.php?register=success");
        exit();
    } else {
        $_SESSION["register_error"] = "Something went wrong. Try again.";
        header("Location: ../../index.php?register=error");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>