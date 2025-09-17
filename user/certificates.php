<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
  <?php include '../components/header_links.php'; ?>
  <?php include '../components/user_side_header.php'; ?>
</head>

<body>
  <?php include '../components/navbar.php'; ?>

  <main class="main-content">
    <div class="content-wrapper">

      <h1>Certificates</h1>
      <p>Welcome <?php echo $_SESSION["user_name"]; ?>.</p>

    </div>
  </main>

  <?php include '../components/cdn_scripts.php'; ?>
  <?php include '../components/footer.php'; ?>

</body>

</html>