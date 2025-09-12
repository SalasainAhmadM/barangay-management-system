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

      <h1>Welcome to BMS</h1>
      <p>Welcome <?php echo $_SESSION["user_name"]; ?>.</p>

    </div>
  </main>

  <?php include '../components/cdn_scripts.php'; ?>
  <?php include '../components/footer.php'; ?>
  <?php if (isset($_SESSION["needs_contact"]) && $_SESSION["needs_contact"]): ?>
    <script>
      window.onload = function () {
        Swal.fire({
          title: 'Please input your Contact Number',
          html: `
            <div class="swal-form">
                <div class="form-group">
                    <label for="contact-number" class="form-label">Contact Number</label>
                    <input type="number" id="contact-number" class="swal2-input" placeholder="e.g. 09771029233" required>
                </div>
            </div>
          `,
          focusConfirm: false,
          showCancelButton: true,
          confirmButtonText: 'Save',
          cancelButtonText: 'Add Later',
          didOpen: () => {
            document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
            document.body.classList.remove("swal2-shown", "swal2-height-auto");
          },
          preConfirm: () => {
            const contact = document.getElementById('contact-number').value;
            if (!contact) {
              Swal.showValidationMessage('Contact number is required');
              return false;
            }
            return contact;
          }
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('./endpoints/save_contact.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ contact: result.value })
            })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  Swal.fire({
                    title: 'Saved!',
                    text: 'Your contact number has been updated.',
                    icon: 'success',
                    didOpen: () => {
                      document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                      document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    }
                  });
                } else {
                  Swal.fire({
                    title: 'Error',
                    text: data.message || 'Could not save contact.',
                    icon: 'error',
                    didOpen: () => {
                      document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                      document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    }
                  });
                }
              })
              .catch(() => {
                Swal.fire({
                  title: 'Error',
                  text: 'Unable to save contact number.',
                  icon: 'error',
                  didOpen: () => {
                    document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                    document.body.classList.remove("swal2-shown", "swal2-height-auto");
                  }
                });
              });
          }
        });
      };
    </script>
    <?php unset($_SESSION["needs_contact"]); endif; ?>


</body>

</html>