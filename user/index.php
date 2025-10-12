<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}

$user_id = $_SESSION["user_id"];

// Fetch user's document request statistics
$stmt = $conn->prepare("
  SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status IN ('approved', 'ready') THEN 1 END) as approved_count
  FROM document_requests 
  WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch next waste collection dates
$current_day = date('l'); // Get current day name
$stmt = $conn->prepare("
  SELECT waste_type, collection_days, icon, color 
  FROM waste_schedules 
  WHERE is_active = 1
  ORDER BY schedule_id
");
$stmt->execute();
$waste_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate days until next collection for each waste type
function getNextCollectionDate($collection_days)
{
  $days = array_map('trim', explode(',', $collection_days));
  $current_day = date('l');
  $current_date = new DateTime();

  $day_map = [
    'Sunday' => 0,
    'Monday' => 1,
    'Tuesday' => 2,
    'Wednesday' => 3,
    'Thursday' => 4,
    'Friday' => 5,
    'Saturday' => 6
  ];

  $current_day_num = $day_map[$current_day];
  $min_days_away = 7;
  $next_day_name = '';

  foreach ($days as $day) {
    if (isset($day_map[$day])) {
      $target_day_num = $day_map[$day];
      $days_away = ($target_day_num - $current_day_num + 7) % 7;
      if ($days_away == 0)
        $days_away = 7; // If today, set to next week

      if ($days_away < $min_days_away) {
        $min_days_away = $days_away;
        $next_day_name = $day;
      }
    }
  }

  $next_date = clone $current_date;
  $next_date->modify("+{$min_days_away} days");

  return [
    'days_away' => $min_days_away,
    'date' => $next_date->format('l, F j, Y'),
    'day_name' => $next_day_name
  ];
}

// Get the nearest collection
$nearest_collection = null;
foreach ($waste_schedules as $schedule) {
  $next = getNextCollectionDate($schedule['collection_days']);
  if (!$nearest_collection || $next['days_away'] < $nearest_collection['days_away']) {
    $nearest_collection = $next;
  }
}

$limit = count($waste_schedules) > 0 ? count($waste_schedules) : 5;

$stmt = $conn->prepare("
  SELECT 
    dr.request_id,
    dr.status,
    dr.submitted_date,
    dr.approved_date,
    dt.name as document_name,
    dt.icon
  FROM document_requests dr
  JOIN document_types dt ON dr.document_type_id = dt.id
  WHERE dr.user_id = ?
  ORDER BY dr.submitted_date DESC
  LIMIT ?
");
$stmt->bind_param("ii", $user_id, $limit);
$stmt->execute();
$recent_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Fetch announcements for this user
$stmt = $conn->prepare("
  SELECT 
    title, 
    message, 
    icon, 
    DATE_FORMAT(created_at, '%b %e, %Y') AS formatted_date
  FROM notifications 
  WHERE user_id = ? AND type = 'announcement'
  ORDER BY created_at DESC
  LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count announcements dynamically
$announcement_count = count($announcements);

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
      <h1>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h1>
      <p>Here's what's happening in your barangay today</p>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-grid">
      <div class="stat-card pending">
        <div class="stat-card-header">
          <div>
            <h3>Pending Requests</h3>
            <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
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
            <div class="stat-value"><?php echo $stats['approved_count']; ?></div>
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
            <?php
            $days = $nearest_collection ? $nearest_collection['days_away'] : 0;
            $label = ($days == 1) ? 'Day remaining' : 'Days remaining';
            ?>
            <div class="stat-value"><?php echo $days; ?></div>
            <div class="stat-label"><?php echo $label; ?></div>
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
            <div class="stat-value"><?php echo $announcement_count; ?></div>
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
          <?php foreach ($waste_schedules as $schedule):
            $next_collection = getNextCollectionDate($schedule['collection_days']);
            ?>
            <div class="schedule-item">
              <div class="schedule-icon">
                <i class="fas <?php echo htmlspecialchars($schedule['icon']); ?>"></i>
              </div>
              <div class="schedule-info">
                <h4><?php echo htmlspecialchars($schedule['waste_type']); ?></h4>
                <p>Next: <?php echo $next_collection['date']; ?></p>
              </div>
              <span class="schedule-badge">In <?php echo $next_collection['days_away']; ?>
                day<?php echo $next_collection['days_away'] != 1 ? 's' : ''; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Requests -->
      <div class="section-card">
        <div class="section-card-header">
          <i class="fas fa-file-alt"></i>
          <h2>Recent Requests</h2>
        </div>
        <div class="section-card-body">
          <?php if (empty($recent_requests)): ?>
            <p style="text-align: center; color: #6c757d; padding: 20px;">No document requests yet</p>
          <?php else: ?>
            <?php foreach ($recent_requests as $request):
              $status_class = strtolower($request['status']);
              $status_label = ucfirst($request['status']);
              if ($request['status'] == 'ready' || $request['status'] == 'approved') {
                $status_label = 'Ready';
              }
              $date_display = $request['status'] == 'approved' && $request['approved_date']
                ? date('M j, Y', strtotime($request['approved_date']))
                : date('M j, Y', strtotime($request['submitted_date']));
              ?>
              <div class="request-item <?php echo $status_class; ?>">
                <div class="request-icon">
                  <i class="fas <?php echo htmlspecialchars($request['icon']); ?>"></i>
                </div>
                <div class="request-info">
                  <h4><?php echo htmlspecialchars($request['document_name']); ?></h4>
                  <p><?php echo $request['status'] == 'approved' ? 'Approved' : 'Submitted'; ?>:
                    <?php echo $date_display; ?>
                  </p>
                </div>
                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
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
        <?php if (empty($announcements)): ?>
          <p style="text-align: center; color: #6c757d; padding: 20px;">No announcements at this time</p>
        <?php else: ?>
          <?php foreach ($announcements as $a): ?>
            <div class="announcement-item">
              <div class="announcement-header">
                <h4><?php echo htmlspecialchars($a['title']); ?></h4>
                <span class="announcement-date"><?php echo htmlspecialchars($a['formatted_date']); ?></span>
              </div>
              <p><?php echo nl2br(htmlspecialchars($a['message'])); ?></p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <?php include '../components/cdn_scripts.php'; ?>
  <?php include '../components/footer.php'; ?>

  <?php if (isset($_SESSION["show_user_welcome"]) && $_SESSION["show_user_welcome"] === true): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        // Determine if first-time login
        const isFirstLogin = <?php echo isset($_SESSION["is_first_login"]) && $_SESSION["is_first_login"] ? 'true' : 'false'; ?>;

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

        // Different messages for first-time vs returning users
        const welcomeMessage = isFirstLogin
          ? 'Welcome to Barangay Management System, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!'
          : 'Welcome back, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!';

        Toast.fire({
          icon: 'success',
          text: welcomeMessage
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
                  occupation: document.getElementById('occupation').value
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
    <?php
    unset($_SESSION["show_user_welcome"]);
    if (isset($_SESSION["needs_details"]))
      unset($_SESSION["needs_details"]);
    if (isset($_SESSION["is_first_login"]))
      unset($_SESSION["is_first_login"]);
    ?>
  <?php endif; ?>

</body>

</html>