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

  <!-- Main Content -->
  <main class="main-content">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <h1>Welcome, <?php echo $_SESSION["user_name"]; ?>!</h1>
      <p>Here's what's happening in your barangay today</p>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-grid">
      <div class="stat-card pending">
        <div class="stat-card-header">
          <div>
            <h3>Pending Requests</h3>
            <div class="stat-value">2</div>
            <div class="stat-label">Awaiting approval</div>
          </div>
          <div class="stat-card-icon">
            <i class="fas fa-clock"></i>
          </div>
        </div>
      </div>

      <div class="stat-card approved">
        <div class="stat-card-header">
          <div>
            <h3>Approved Requests</h3>
            <div class="stat-value">5</div>
            <div class="stat-label">Ready for pickup</div>
          </div>
          <div class="stat-card-icon">
            <i class="fas fa-check-circle"></i>
          </div>
        </div>
      </div>

      <div class="stat-card waste">
        <div class="stat-card-header">
          <div>
            <h3>Next Collection</h3>
            <div class="stat-value">2</div>
            <div class="stat-label">Days remaining</div>
          </div>
          <div class="stat-card-icon">
            <i class="fas fa-trash-alt"></i>
          </div>
        </div>
      </div>

      <div class="stat-card announcements">
        <div class="stat-card-header">
          <div>
            <h3>Announcements</h3>
            <div class="stat-value">3</div>
            <div class="stat-label">New updates</div>
          </div>
          <div class="stat-card-icon">
            <i class="fas fa-bullhorn"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Sections Grid -->
    <div class="section-grid">
      <!-- Waste Collection Schedule -->
      <div class="section-card">
        <div class="section-card-header">
          <i class="fas fa-calendar-alt"></i>
          <h2>Waste Collection Schedule</h2>
        </div>
        <div class="section-card-body">
          <div class="schedule-item">
            <div class="schedule-icon">
              <i class="fas fa-recycle"></i>
            </div>
            <div class="schedule-info">
              <h4>Recyclable Waste</h4>
              <p>Next: Friday, October 4, 2025</p>
            </div>
            <span class="schedule-badge">In 2 days</span>
          </div>
          <div class="schedule-item">
            <div class="schedule-icon">
              <i class="fas fa-trash"></i>
            </div>
            <div class="schedule-info">
              <h4>Biodegradable Waste</h4>
              <p>Next: Monday, October 7, 2025</p>
            </div>
            <span class="schedule-badge">In 5 days</span>
          </div>
          <div class="schedule-item">
            <div class="schedule-icon">
              <i class="fas fa-dumpster"></i>
            </div>
            <div class="schedule-info">
              <h4>Non-Biodegradable Waste</h4>
              <p>Next: Wednesday, October 9, 2025</p>
            </div>
            <span class="schedule-badge">In 7 days</span>
          </div>
        </div>
      </div>

      <!-- Recent Requests -->
      <div class="section-card">
        <div class="section-card-header">
          <i class="fas fa-file-alt"></i>
          <h2>Recent Requests</h2>
        </div>
        <div class="section-card-body">
          <div class="request-item pending">
            <div class="request-icon">
              <i class="fas fa-id-card"></i>
            </div>
            <div class="request-info">
              <h4>Barangay Clearance</h4>
              <p>Submitted: Sept 28, 2025</p>
            </div>
            <span class="status-badge pending">Pending</span>
          </div>
          <div class="request-item processing">
            <div class="request-icon">
              <i class="fas fa-certificate"></i>
            </div>
            <div class="request-info">
              <h4>Certificate of Residency</h4>
              <p>Submitted: Sept 25, 2025</p>
            </div>
            <span class="status-badge processing">Processing</span>
          </div>
          <div class="request-item approved">
            <div class="request-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <div class="request-info">
              <h4>Indigency Certificate</h4>
              <p>Approved: Sept 20, 2025</p>
            </div>
            <span class="status-badge approved">Ready</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements -->
    <div class="section-card">
      <div class="section-card-header">
        <i class="fas fa-bullhorn"></i>
        <h2>Barangay Announcements</h2>
      </div>
      <div class="section-card-body">
        <div class="announcement-item">
          <div class="announcement-header">
            <h4>Community Clean-up Drive</h4>
            <span class="announcement-date">Sept 29, 2025</span>
          </div>
          <p>Join us this Saturday, October 5, for our monthly community clean-up drive. Gathering at the barangay hall
            at 6:00 AM.</p>
        </div>
        <div class="announcement-item">
          <div class="announcement-header">
            <h4>Barangay Assembly Meeting</h4>
            <span class="announcement-date">Sept 27, 2025</span>
          </div>
          <p>Monthly assembly meeting scheduled for October 10, 2025, at 5:00 PM. All residents are encouraged to
            attend.</p>
        </div>
        <div class="announcement-item">
          <div class="announcement-header">
            <h4>Holiday Schedule Notice</h4>
            <span class="announcement-date">Sept 25, 2025</span>
          </div>
          <p>The barangay office will be closed on October 31 and November 1 in observance of All Saints' Day.</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions 
    <div class="quick-actions">
      <a href="#" class="action-btn">
        <i class="fas fa-plus-circle"></i>
        <span>New Request</span>
      </a>
      <a href="#" class="action-btn">
        <i class="fas fa-user-edit"></i>
        <span>Update Profile</span>
      </a>
      <a href="#" class="action-btn">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Report Issue</span>
      </a>
      <a href="#" class="action-btn">
        <i class="fas fa-envelope"></i>
        <span>Contact Barangay</span>
      </a>
    </div> -->
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