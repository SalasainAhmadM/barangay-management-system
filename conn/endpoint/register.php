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

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert
    $stmt = $conn->prepare("INSERT INTO user (first_name, middle_name, last_name, email, contact_number, password) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $first_name, $middle_name, $last_name, $email, $contact, $hashedPassword);

    if ($stmt->execute()) {

        $activity = "New Account";
        $description = "A new user account was created for {$first_name} {$middle_name} {$last_name} ({$email})";
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
