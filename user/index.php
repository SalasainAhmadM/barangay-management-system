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

  <?php if (isset($_SESSION["show_user_welcome"]) && $_SESSION["show_user_welcome"] === true): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        // Show the welcome toast
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
        });

        Toast.fire({
          icon: 'success',
          text: 'Welcome back, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!'
        }).then(() => {
          <?php if (isset($_SESSION["needs_details"]) && $_SESSION["needs_details"]): ?>
            // Show the complete profile modal *after* welcome toast
            Swal.fire({
              title: 'Complete Your Profile',
              html: `
              <div class="swal-form">
                  <div class="form-group">
                      <label for="date-of-birth" class="form-label">Date of Birth</label>
                      <input type="date" id="date-of-birth" class="swal2-input" required>
                  </div>
                  <div class="form-group">
                      <label for="gender" class="form-label">Gender</label>
                      <select id="gender" class="swal2-select" required>
                          <option value="">-- Select Gender --</option>
                          <option value="male">Male</option>
                          <option value="female">Female</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="civil-status" class="form-label">Civil Status</label>
                      <select id="civil-status" class="swal2-select">
                          <option value="">-- Select Status --</option>
                          <option value="single">Single</option>
                          <option value="married">Married</option>
                          <option value="divorced">Divorced</option>
                          <option value="widowed">Widowed</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label for="occupation" class="form-label">Occupation</label>
                      <input type="text" id="occupation" class="swal2-input" placeholder="e.g. Teacher">
                  </div>
                  <div class="form-group">
                      <label for="house-number" class="form-label">House Number</label>
                      <input type="text" id="house-number" class="swal2-input" placeholder="e.g. 123">
                  </div>
                  <div class="form-group">
                      <label for="street-name" class="form-label">Street Name</label>
                      <input type="text" id="street-name" class="swal2-input" placeholder="e.g. Mango St.">
                  </div>
                  <div class="form-group">
                      <label for="barangay" class="form-label">Barangay</label>
                      <input type="text" id="barangay" class="swal2-input" value="Baliwasan">
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
                const dob = document.getElementById('date-of-birth').value;
                const gender = document.getElementById('gender').value;

                if (!dob || !gender) {
                  Swal.showValidationMessage('Date of Birth and Gender are required');
                  return false;
                }

                return {
                  date_of_birth: dob,
                  gender: gender,
                  civil_status: document.getElementById('civil-status').value,
                  occupation: document.getElementById('occupation').value,
                  house_number: document.getElementById('house-number').value,
                  street_name: document.getElementById('street-name').value,
                  barangay: document.getElementById('barangay').value
                };
              }
            }).then((result) => {
              if (result.isConfirmed) {
                fetch('./endpoints/save_details.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify(result.value)
                })
                  .then(res => res.json())
                  .then(data => {
                    if (data.success) {
                      Swal.fire({
                        title: 'Saved!',
                        text: 'Your profile details have been updated.',
                        icon: 'success',
                        didOpen: () => {
                          document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                          document.body.classList.remove("swal2-shown", "swal2-height-auto");
                        }
                      });
                    } else {
                      Swal.fire({
                        title: 'Error',
                        text: data.message || 'Could not save details.',
                        icon: 'error'
                      });
                    }
                  })
                  .catch(() => {
                    Swal.fire({
                      title: 'Error',
                      text: 'Unable to save details.',
                      icon: 'error'
                    });
                  });
              }
            });
          <?php endif; ?>
        });
      });
    </script>
    <?php unset($_SESSION["show_user_welcome"]); ?>
    <?php if (isset($_SESSION["needs_details"]))
      unset($_SESSION["needs_details"]); ?>
  <?php endif; ?>

</body>

</html>