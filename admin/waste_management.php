<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Determine active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'schedules';

// Pagination for schedules
$schedulesPerPage = 8;
$schedPage = isset($_GET['sched_page']) ? (int) $_GET['sched_page'] : 1;
$schedPage = max(1, $schedPage);
$schedOffset = ($schedPage - 1) * $schedulesPerPage;

// Pagination for reports
$reportsPerPage = 8;
$reportPage = isset($_GET['report_page']) ? (int) $_GET['report_page'] : 1;
$reportPage = max(1, $reportPage);
$reportOffset = ($reportPage - 1) * $reportsPerPage;

// Fetch waste schedules
$schedules = [];
$totalSchedules = 0;
try {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM waste_schedules");
    $countStmt->execute();
    $totalSchedules = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT * FROM waste_schedules ORDER BY schedule_id LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $schedulesPerPage, $schedOffset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
} catch (Exception $e) {
    die("Error fetching schedules: " . $e->getMessage());
}

// Fetch missed collection reports with user info
$reports = [];
$totalReports = 0;
try {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM missed_collections");
    $countStmt->execute();
    $totalReports = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("
        SELECT mc.*, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number 
        FROM missed_collections mc
        LEFT JOIN user u ON mc.user_id = u.id
        ORDER BY mc.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $reportsPerPage, $reportOffset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
} catch (Exception $e) {
    die("Error fetching reports: " . $e->getMessage());
}

// Calculate pagination
$totalSchedPages = ceil($totalSchedules / $schedulesPerPage);
$totalReportPages = ceil($totalReports / $reportsPerPage);

// Get report statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'investigating' => 0,
    'resolved' => 0,
    'rejected' => 0
];
try {
    $statsStmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM missed_collections
    ");
    $stats = $statsStmt->fetch_assoc();
} catch (Exception $e) {
    // Keep default stats
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
                <h2 class="table-title">Waste Management</h2>
                <div class="table-actions">
                    <button class="btn btn-primary" onclick="addSchedule()">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn <?= $activeTab === 'schedules' ? 'active' : ''; ?>"
                    onclick="switchTab('schedules')">
                    <i class="fas fa-calendar-alt"></i> Collection Schedules
                </button>
                <button class="tab-btn <?= $activeTab === 'reports' ? 'active' : ''; ?>" onclick="switchTab('reports')">
                    <i class="fas fa-exclamation-triangle"></i> Missed Collections
                </button>
            </div>

            <!-- Schedules Tab -->
            <div id="schedulesTab" style="display: <?= $activeTab === 'schedules' ? 'block' : 'none'; ?>">
                <div class="search-container">
                    <div class="search-box-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search schedules..."
                            id="searchScheduleInput" onkeyup="searchSchedules()">
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="table-responsive">
                    <table class="residents-table" id="schedulesTable">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Waste Type</th>
                                <th>Collection Days</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($schedules) > 0): ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <div
                                                class="waste-icon <?= htmlspecialchars($schedule['color'] ?? 'biodegradable'); ?>">
                                                <i class="fas <?= htmlspecialchars($schedule['icon'] ?? 'fa-trash'); ?>"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="resident-name"><?= htmlspecialchars($schedule['waste_type']); ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['collection_days']); ?></td>
                                        <td><?= htmlspecialchars($schedule['description'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?= $schedule['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?= $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($schedule['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-edit"
                                                    onclick="editSchedule(<?= $schedule['schedule_id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete"
                                                    onclick="deleteSchedule(<?= $schedule['schedule_id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-calendar-alt"></i>
                                        <p>No schedules found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="mobile-cards">
                    <?php if (count($schedules) > 0): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <div class="resident-card">
                                <div class="card-header">
                                    <div class="waste-icon <?= htmlspecialchars($schedule['color'] ?? 'biodegradable'); ?>">
                                        <i class="fas <?= htmlspecialchars($schedule['icon'] ?? 'fa-trash'); ?>"></i>
                                    </div>
                                    <div>
                                        <div class="resident-name"><?= htmlspecialchars($schedule['waste_type']); ?></div>
                                        <div class="resident-email"><?= htmlspecialchars($schedule['collection_days']); ?></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-field">Description:</div>
                                    <div class="card-value"><?= htmlspecialchars($schedule['description'] ?? 'N/A'); ?></div>
                                    <div class="card-field">Status:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= $schedule['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?= $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Created:</div>
                                    <div class="card-value"><?= date("M d, Y", strtotime($schedule['created_at'])); ?></div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-edit"
                                        onclick="editSchedule(<?= $schedule['schedule_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete"
                                        onclick="deleteSchedule(<?= $schedule['schedule_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination for Schedules -->
                <?php if ($totalSchedules > 0): ?>
                    <?php if ($totalSchedules > 0): ?>
                        <?php renderPagination($schedPage, $totalSchedPages, $totalSchedules, $schedOffset, $schedulesPerPage, 'sched_page', 'schedules'); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Reports Tab -->
            <div id="reportsTab" style="display: <?= $activeTab === 'reports' ? 'block' : 'none'; ?>">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-value"><?= $stats['total']; ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?= $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card investigating">
                        <div class="stat-icon"><i class="fas fa-search"></i></div>
                        <div class="stat-value"><?= $stats['investigating']; ?></div>
                        <div class="stat-label">Investigating</div>
                    </div>
                    <div class="stat-card resolved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?= $stats['resolved']; ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value"><?= $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <div class="search-container">
                    <div class="search-box-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search reports..." id="searchReportInput"
                            onkeyup="searchReports()">
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="table-responsive">
                    <table class="residents-table" id="reportsTable">
                        <thead>
                            <tr>
                                <th>Reporter</th>
                                <th>Waste Type</th>
                                <th>Location</th>
                                <th>Collection Date</th>
                                <th>Status</th>
                                <th>Reported Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reports) > 0): ?>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <div class="resident-name">
                                                <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                            </div>
                                            <div class="resident-email"><?= htmlspecialchars($report['email'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($report['waste_type']); ?></td>
                                        <td><?= htmlspecialchars($report['location']); ?></td>
                                        <td><?= date("M d, Y", strtotime($report['collection_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?= htmlspecialchars($report['status']); ?>">
                                                <?= ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-view"
                                                    onclick="viewReport(<?= $report['report_id']; ?>)" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-edit"
                                                    onclick="updateReportStatus(<?= $report['report_id']; ?>)"
                                                    title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete"
                                                    onclick="deleteReport(<?= $report['report_id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No reports found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="mobile-cards">
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $report): ?>
                            <div class="resident-card">
                                <div class="card-header">
                                    <div>
                                        <div class="resident-name">
                                            <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                        </div>
                                        <div class="resident-email"><?= htmlspecialchars($report['email'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-field">Waste Type:</div>
                                    <div class="card-value"><?= htmlspecialchars($report['waste_type']); ?></div>
                                    <div class="card-field">Location:</div>
                                    <div class="card-value"><?= htmlspecialchars($report['location']); ?></div>
                                    <div class="card-field">Collection Date:</div>
                                    <div class="card-value"><?= date("M d, Y", strtotime($report['collection_date'])); ?></div>
                                    <div class="card-field">Status:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= htmlspecialchars($report['status']); ?>">
                                            <?= ucfirst($report['status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Reported:</div>
                                    <div class="card-value"><?= date("M d, Y", strtotime($report['created_at'])); ?></div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-view" onclick="viewReport(<?= $report['report_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-edit"
                                        onclick="updateReportStatus(<?= $report['report_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete" onclick="deleteReport(<?= $report['report_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination for Reports -->
                <?php if ($totalReports > 0): ?>
                    <?php renderPagination($reportPage, $totalReportPages, $totalReports, $reportOffset, $reportsPerPage, 'report_page', 'reports'); ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
    <script src="./js/waste_management.js"></script>

</body>

</html>

<?php
function renderPagination($currentPage, $totalPages, $totalItems, $offset, $itemsPerPage, $pageParam, $tab)
{
    $startRecord = $totalItems > 0 ? $offset + 1 : 0;
    $endRecord = min($offset + $itemsPerPage, $totalItems);
    ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <?= $startRecord; ?>-<?= $endRecord; ?> of <?= $totalItems; ?> items
        </div>
        <div class="pagination-controls">
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?tab=<?= $tab; ?>&<?= $pageParam; ?>=<?= $currentPage - 1; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);

                if ($startPage > 1):
                    ?>
                    <a href="?tab=<?= $tab; ?>&<?= $pageParam; ?>=1">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="current"><?= $i; ?></span>
                    <?php else: ?>
                        <a href="?tab=<?= $tab; ?>&<?= $pageParam; ?>=<?= $i; ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                    <a href="?tab=<?= $tab; ?>&<?= $pageParam; ?>=<?= $totalPages; ?>"><?= $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?tab=<?= $tab; ?>&<?= $pageParam; ?>=<?= $currentPage + 1; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>