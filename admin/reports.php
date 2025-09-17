<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <?php include '../components/header_links.php'; ?>
</head>

<body>

    <?php include '../components/sidebar.php'; ?>

    <section class="home-section">
        <div class="text">Reports</div>
    </section>


    <?php include '../components/cdn_scripts.php'; ?>

</body>

</html>