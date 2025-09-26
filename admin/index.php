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
    <style>
        /* Dashboard Specific Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 36, 124, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 36, 124, 0.15);
        }

        .card-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        .residents-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .requests-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .waste-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .reports-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .card-info {
            text-align: right;
        }

        .card-number {
            font-size: 32px;
            font-weight: 700;
            color: #00247c;
            margin: 0;
            line-height: 1;
        }

        .card-title {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0 0 0;
            font-weight: 500;
        }

        .card-body {
            padding: 0 20px 20px 20px;
        }

        .card-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-top: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .detail-label {
            color: #6b7280;
        }

        .detail-value {
            font-weight: 600;
            color: #374151;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .recent-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 36, 124, 0.1);
            margin: 20px;
            overflow: hidden;
        }

        .recent-header {
            background: #f8fafc;
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .recent-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        .recent-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .recent-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item:hover {
            background: #f8fafc;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: 500;
            color: #374151;
            margin: 0 0 4px 0;
        }

        .item-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        .item-time {
            font-size: 12px;
            color: #9ca3af;
            white-space: nowrap;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            margin: 20px;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 200px;
            padding: 16px 20px;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .action-btn:hover {
            border-color: #00247c;
            background: #f8fafc;
            color: #00247c;
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                margin: 15px;
                gap: 15px;
            }

            .recent-section {
                margin: 15px;
            }

            .quick-actions {
                margin: 15px;
                flex-direction: column;
            }

            .action-btn {
                min-width: auto;
            }

            .card-header {
                padding: 15px;
            }

            .card-body {
                padding: 0 15px 15px 15px;
            }

            .card-number {
                font-size: 28px;
            }

            .card-icon {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }

        @media (max-width: 480px) {
            .card-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .card-info {
                text-align: center;
            }

            .card-details {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

            .recent-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .item-time {
                align-self: flex-end;
            }
        }
    </style>
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
            <a href="requests_certificates.php" class="action-btn">
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
                <li class="recent-item">
                    <div class="item-info">
                        <h4 class="item-title">New Certificate Request</h4>
                        <p class="item-subtitle">Maria Santos requested Barangay Clearance</p>
                    </div>
                    <span class="item-time">2 hours ago</span>
                </li>
                <li class="recent-item">
                    <div class="item-info">
                        <h4 class="item-title">Waste Collection Report</h4>
                        <p class="item-subtitle">Missed collection reported on Mango Street</p>
                    </div>
                    <span class="item-time">5 hours ago</span>
                </li>
                <li class="recent-item">
                    <div class="item-info">
                        <h4 class="item-title">New Resident Registration</h4>
                        <p class="item-subtitle">Juan Dela Cruz registered as new resident</p>
                    </div>
                    <span class="item-time">1 day ago</span>
                </li>
                <li class="recent-item">
                    <div class="item-info">
                        <h4 class="item-title">Permit Approved</h4>
                        <p class="item-subtitle">Business permit approved for ABC Store</p>
                    </div>
                    <span class="item-time">2 days ago</span>
                </li>
                <li class="recent-item">
                    <div class="item-info">
                        <h4 class="item-title">SMS Notification Sent</h4>
                        <p class="item-subtitle">Waste collection reminder sent to 1,200+ residents</p>
                    </div>
                    <span class="item-time">3 days ago</span>
                </li>
            </ul>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
</body>

</html>