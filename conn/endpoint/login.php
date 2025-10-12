<?php
session_start();
require_once("../conn.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Check admin first
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows === 1) {
        $admin = $adminResult->fetch_assoc();
        if (password_verify($password, $admin["password"])) {
            $_SESSION["admin_id"] = $admin["id"];
            $adminName = $admin["first_name"];
            if (!empty($admin["middle_name"])) {
                $adminName .= " " . strtoupper(substr($admin["middle_name"], 0, 1)) . ".";
            }
            $adminName .= " " . $admin["last_name"];
            $_SESSION["admin_name"] = $adminName;
            $_SESSION["admin_email"] = $admin["email"];
            $_SESSION["show_admin_welcome"] = true;
            header("Location: ../../admin/index.php");
            exit();
        } else {
            $_SESSION["login_error"] = "Invalid email or password.";
            header("Location: ../../index.php?login=error");
            exit();
        }
    }

    // Check user
    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, date_of_birth, gender, password, is_new FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows === 1) {
        $user = $userResult->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $userName = $user["first_name"];
            if (!empty($user["middle_name"])) {
                $userName .= " " . strtoupper(substr($user["middle_name"], 0, 1)) . ".";
            }
            $userName .= " " . $user["last_name"];
            $_SESSION["user_name"] = $userName;
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["needs_details"] = (empty($user["date_of_birth"]) || empty($user["gender"]));
            $_SESSION["show_user_welcome"] = true;

            // Check if first-time login
            $_SESSION["is_first_login"] = ($user["is_new"] == 1);

            // Update is_new to 0 after first login
            if ($user["is_new"] == 1) {
                $update_stmt = $conn->prepare("UPDATE user SET is_new = 0 WHERE id = ?");
                $update_stmt->bind_param("i", $user["id"]);
                $update_stmt->execute();
                $update_stmt->close();
            }

            $activity = "User Login";
            $description = "User {$userName} ({$user['email']}) has logged in.";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (activity, description) VALUES (?, ?)");
            $log_stmt->bind_param("ss", $activity, $description);
            $log_stmt->execute();
            $log_stmt->close();

            header("Location: ../../user/index.php");
            exit();
        } else {
            $_SESSION["login_error"] = "Invalid email or password.";
            header("Location: ../../index.php?login=error");
            exit();
        }
    }

    $_SESSION["login_error"] = "No account found with that email.";
    header("Location: ../../index.php?login=error");
    exit();

    $stmt->close();
    $conn->close();
}
?>