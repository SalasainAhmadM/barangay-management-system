<?php
session_start();
require_once("../conn.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult->num_rows === 1) {
        $admin = $adminResult->fetch_assoc();

        if (password_verify($password, $admin["password"])) {

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_name"] = $admin["first_name"] . " " . $user["middle_name"] . ". " . $user["last_name"];
            $_SESSION["admin_email"] = $admin["email"];

            header("Location: ../../admin/index.php");
            exit();
        } else {
            $_SESSION["login_error"] = "Invalid email or password.";
            header("Location: ../../index.php?login=error");
            exit();
        }
    }

    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, password FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $userResult = $stmt->get_result();

    if ($userResult->num_rows === 1) {
        $user = $userResult->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["first_name"] . " " . $user["middle_name"] . ". " . $user["last_name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["needs_contact"] = empty($user["contact_number"]);

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