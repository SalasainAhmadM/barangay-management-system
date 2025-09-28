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

        <!-- Dashboard Cards Grid -->
        <div class="dashboard-grid">
            <!-- Total Residents Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon residents-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-number">1,247</h3>
                        <p class="card-title">Total Residents</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Active Households:</span>
                        <span class="detail-value">423</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">New this month:</span>
                        <span class="detail-value">+18</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge badge-success">Updated</span>
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
                        <h3 class="card-number">23</h3>
                        <p class="card-title">Pending Requests</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Certificates:</span>
                        <span class="detail-value">15</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Permits:</span>
                        <span class="detail-value">8</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Priority:</span>
                        <span class="status-badge badge-warning">Action Needed</span>
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
                        <h3 class="card-number">3</h3>
                        <p class="card-title">Next Collection</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Schedule:</span>
                        <span class="detail-value">Mon, Wed, Fri</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Missed Reports:</span>
                        <span class="detail-value">2 this week</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge badge-info">On Schedule</span>
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
                        <h3 class="card-number">12</h3>
                        <p class="card-title">Monthly Reports</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-details">
                        <span class="detail-label">Generated:</span>
                        <span class="detail-value">8 reports</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Pending:</span>
                        <span class="detail-value">4 reports</span>
                    </div>
                    <div class="card-details">
                        <span class="detail-label">Status:</span>
                        <span class="status-badge badge-success">Up to Date</span>
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
                <i class="fas fa-download"></i>
                Generate Report
            </a>
        </div>

        <!-- Recent Activities -->
        <div class="recent-section">
            <div class="recent-header">
                <h3 class="recent-title">Recent Activities</h3>
            </div>
            <ul class="recent-list">
                <?php
                // Set timezone to Asia/Manila
                date_default_timezone_set('Asia/Manila');

                // Fetch last 5 activity logs
                $stmt = $conn->prepare("SELECT activity, description, created_at 
                                FROM activity_logs 
                                ORDER BY created_at DESC 
                                LIMIT 5");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Format time ago
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
</body>

</html>