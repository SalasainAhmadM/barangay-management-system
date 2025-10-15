<?php
session_start();

// ✅ Set timezone to Asia/Manila at the very start
date_default_timezone_set('Asia/Manila');

require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// ✅ Load .env manually (no composer)
$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue; // skip comments
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Get Semaphore API key
$api_key = $_ENV['SEMAPHORE_API_KEY'] ?? '';

// ✅ Fetch Semaphore account credits
$credits_data = null;
if (!empty($api_key)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.semaphore.co/api/v4/account?apikey=$api_key");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $credits_response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (!$curl_error && $credits_response) {
        $credits_data = json_decode($credits_response, true);
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <?php include '../components/header_links.php'; ?>
    <?php include '../components/admin_side_header.php'; ?>

</head>

<body>
    <?php include '../components/sidebar.php'; ?>

    <section class="home-section">
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Dashboard Overview</h2>
                <div class="table-actions">
                    <span style="color: #e5e7eb; font-size: 14px;">Last updated:
                        <?php echo date('M d, Y - g:i A'); ?></span>
                </div>
            </div>
        </div>

        <!-- SMS Credits Balance Section -->
        <div class="table-container" style="background: #fff; margin-bottom: 20px;">
            <div class="table-header" style="background: #fff;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="../assets/logo/semaphore.png" alt="SMS"
                        style="width: 40px; height: 40px; object-fit: contain;">
                    <div>
                        <h2 class="table-title" style="color: #374151;margin-bottom: 5px;">
                            <?php
                            if ($credits_data && isset($credits_data['credit_balance'])) {
                                echo number_format($credits_data['credit_balance'], 2);
                            } else {
                                echo 'Unavailable';
                            }
                            ?>
                        </h2>
                        <span style="color: #374151; font-size: 14px; opacity: 0.95;">SMS Credits Balance</span>
                    </div>
                </div>
                <div class="table-actions" style="display: flex; align-items: center; gap: 20px;">
                    <div style="text-align: right;">
                        <div style="color: #374151;font-size: 13px; opacity: 0.9; margin-bottom: 3px;">Service Provider:
                            <strong>Semaphore API</strong>
                        </div>
                        <div style="color: #374151;font-size: 13px; opacity: 0.9; margin-bottom: 3px;">Last Updated:
                            <strong><?php echo date('g:i A'); ?></strong>
                        </div>
                        <div style="color: #374151;font-size: 13px; opacity: 0.9;">
                            Status:
                            <?php
                            if ($credits_data && isset($credits_data['credit_balance'])) {
                                $balance = floatval($credits_data['credit_balance']);
                                if ($balance >= 100) {
                                    echo '<strong>HEALTHY BALANCE</strong>';
                                } elseif ($balance >= 50) {
                                    echo '<strong>LOW BALANCE</strong>';
                                } elseif ($balance > 0) {
                                    echo '<strong>CRITICAL</strong>';
                                } else {
                                    echo '<strong>NO CREDITS</strong>';
                                }
                            } else {
                                echo '<strong>CONNECTION ERROR</strong>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Fetch dashboard statistics
        
        // Total Residents (active status)
        $totalResidentsQuery = $conn->query("SELECT COUNT(*) as total FROM user WHERE status = 'active'");
        $totalResidents = $totalResidentsQuery->fetch_assoc()['total'];

        // Active Households (count distinct house_number + street_name combinations)
        $activeHouseholdsQuery = $conn->query("SELECT COUNT(DISTINCT CONCAT(house_number, street_name)) as total FROM user WHERE status = 'active' AND house_number IS NOT NULL");
        $activeHouseholds = $activeHouseholdsQuery->fetch_assoc()['total'];

        // New residents this month (using Asia/Manila timezone)
        $currentMonth = date('Y-m');
        $newResidentsQuery = $conn->query("SELECT COUNT(*) as total FROM user WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'");
        $newResidents = $newResidentsQuery->fetch_assoc()['total'];

        // Pending document requests
        $pendingRequestsQuery = $conn->query("SELECT COUNT(*) as total FROM document_requests WHERE status = 'pending'");
        $pendingRequests = $pendingRequestsQuery->fetch_assoc()['total'];

        // Certificate requests (pending)
        $certificatesQuery = $conn->query("SELECT COUNT(*) as total FROM document_requests dr 
                                           JOIN document_types dt ON dr.document_type_id = dt.id 
                                           WHERE dr.status = 'pending' AND dt.type = 'certificate'");
        $pendingCertificates = $certificatesQuery->fetch_assoc()['total'];

        // Permit requests (pending)
        $permitsQuery = $conn->query("SELECT COUNT(*) as total FROM document_requests dr 
                                      JOIN document_types dt ON dr.document_type_id = dt.id 
                                      WHERE dr.status = 'pending' AND dt.type = 'permit'");
        $pendingPermits = $permitsQuery->fetch_assoc()['total'];

        // Active waste schedules count
        $activeSchedulesQuery = $conn->query("SELECT COUNT(*) as total FROM waste_schedules WHERE is_active = 1");
        $activeSchedules = $activeSchedulesQuery->fetch_assoc()['total'];

        // Missed collection reports this week (using Asia/Manila timezone)
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $missedReportsQuery = $conn->query("SELECT COUNT(*) as total FROM missed_collections 
                                            WHERE created_at >= '$startOfWeek' AND status = 'pending'");
        $missedReports = $missedReportsQuery->fetch_assoc()['total'];

        // Activity logs this month (using Asia/Manila timezone)
        $activityLogsQuery = $conn->query("SELECT COUNT(*) as total FROM activity_logs 
                                           WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'");
        $monthlyActivities = $activityLogsQuery->fetch_assoc()['total'];

        // Resolved reports this month (using Asia/Manila timezone)
        $resolvedReportsQuery = $conn->query("SELECT COUNT(*) as total FROM missed_collections 
                                              WHERE DATE_FORMAT(resolved_date, '%Y-%m') = '$currentMonth' 
                                              AND status = 'resolved'");
        $resolvedReports = $resolvedReportsQuery->fetch_assoc()['total'];

        // Pending missed collection reports
        $pendingMissedQuery = $conn->query("SELECT COUNT(*) as total FROM missed_collections WHERE status = 'pending'");
        $pendingMissedReports = $pendingMissedQuery->fetch_assoc()['total'];
        ?>

        <!-- Dashboard Cards Grid -->
        <div class="dashboard-grid">
            <!-- Total Residents Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon residents-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-number"><?php echo number_format($totalResidents); ?></h3>
                        <p class="card-title">Total Residents</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Active Households:</span>
                        <span class="detail-value"><?php echo number_format($activeHouseholds); ?></span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">New this month:</span>
                        <span class="detail-value">+<?php echo $newResidents; ?></span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge <?php echo $newResidents > 0 ? 'badge-success' : 'badge-info'; ?>">
                            <?php echo $newResidents > 0 ? 'Updated' : 'No Changes'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon requests-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-number"><?php echo $pendingRequests; ?></h3>
                        <p class="card-title">Pending Requests</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Certificates:</span>
                        <span class="detail-value"><?php echo $pendingCertificates; ?></span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Permits:</span>
                        <span class="detail-value"><?php echo $pendingPermits; ?></span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Priority:</span>
                        <span
                            class="status-badge <?php echo $pendingRequests > 0 ? 'badge-warning' : 'badge-success'; ?>">
                            <?php echo $pendingRequests > 0 ? 'Action Needed' : 'All Clear'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Waste Schedule Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon waste-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-number"><?php echo $activeSchedules; ?></h3>
                        <p class="card-title">Active Schedules</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Schedule Types:</span>
                        <span class="detail-value"><?php echo $activeSchedules; ?> types</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Missed Reports:</span>
                        <span class="detail-value"><?php echo $missedReports; ?> this week</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span
                            class="status-badge <?php echo $missedReports == 0 ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $missedReports == 0 ? 'On Schedule' : 'Issues Reported'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Reports Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon reports-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-number"><?php echo $monthlyActivities; ?></h3>
                        <p class="card-title">Monthly Activities</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Resolved:</span>
                        <span class="detail-value"><?php echo $resolvedReports; ?> reports</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Pending:</span>
                        <span class="detail-value"><?php echo $pendingMissedReports; ?> reports</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge <?php
                        if ($pendingMissedReports == 0) {
                            echo 'badge-success';
                        } elseif ($pendingMissedReports <= 5) {
                            echo 'badge-info';
                        } else {
                            echo 'badge-warning';
                        }
                        ?>">
                            <?php
                            if ($pendingMissedReports == 0) {
                                echo 'Up to Date';
                            } elseif ($pendingMissedReports <= 5) {
                                echo 'On Track';
                            } else {
                                echo 'Needs Attention';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="residents.php" class="action-btn">
                <i class="fas fa-plus"></i>
                Add New Resident
            </a>
            <a href="request_certificates.php" class="action-btn">
                <i class="fas fa-check"></i>
                Review Requests
            </a>
            <a href="waste_management.php" class="action-btn">
                <i class="fas fa-calendar"></i>
                Manage Schedule
            </a>
            <a href="reports.php" class="action-btn">
                <i class="fas fa-chart-pie"></i>
                Reports
            </a>
        </div>

        <!-- Recent Activities -->
        <div class="recent-section">
            <div class="recent-header">
                <h3 class="recent-title">Recent Activities</h3>
            </div>
            <ul class="recent-list">
                <?php
                // Fetch last 5 activity logs
                $stmt = $conn->prepare("SELECT activity, description, created_at 
                                FROM activity_logs 
                                ORDER BY created_at DESC 
                                LIMIT 5");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Format time ago (already using Asia/Manila timezone set at the top)
                        $createdAt = new DateTime($row["created_at"], new DateTimeZone('Asia/Manila'));
                        $now = new DateTime("now", new DateTimeZone('Asia/Manila'));
                        $diff = $now->diff($createdAt);

                        if ($diff->y > 0) {
                            $timeAgo = $diff->y . " year" . ($diff->y > 1 ? "s" : "") . " ago";
                        } elseif ($diff->m > 0) {
                            $timeAgo = $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " ago";
                        } elseif ($diff->d > 0) {
                            $timeAgo = $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
                        } elseif ($diff->h > 0) {
                            $timeAgo = $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
                        } elseif ($diff->i > 0) {
                            $timeAgo = $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
                        } else {
                            $timeAgo = "Just now";
                        }
                        ?>
                        <li class="recent-item">
                            <div class="item-info">
                                <h4 class="item-title"><?php echo htmlspecialchars($row["activity"]); ?></h4>
                                <p class="item-subtitle"><?php echo htmlspecialchars($row["description"]); ?></p>
                            </div>
                            <span class="item-time"><?php echo $timeAgo; ?></span>
                        </li>
                        <?php
                    }
                } else {
                    echo "<li class='recent-item'><div class='item-info'><p>No recent activities found</p></div></li>";
                }
                $stmt->close();
                ?>
            </ul>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>

    <!-- Admin Welcome Alert Script -->
    <?php if (isset($_SESSION["show_admin_welcome"]) && $_SESSION["show_admin_welcome"] === true): ?>
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
                    text: 'Welcome back, <?php echo htmlspecialchars($_SESSION["admin_name"]); ?>!'
                });
            });
        </script>
        <?php
        // Remove the flag so it won't show again
        unset($_SESSION["show_admin_welcome"]);
        ?>
    <?php endif; ?>
</body>

</html>