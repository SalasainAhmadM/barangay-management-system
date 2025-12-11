<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Determine active tab (can be expanded for future tabs)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Pagination
$reportsPerPage = 8;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $reportsPerPage;

// Fetch safety reports with user info
$reports = [];
$totalReports = 0;

try {
    // Build query based on active tab
    $whereClause = "";
    if ($activeTab !== 'all') {
        $whereClause = "WHERE sr.status = ?";
    }
    
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM safety_reports sr $whereClause");
    if ($activeTab !== 'all') {
        $countStmt->bind_param("s", $activeTab);
    }
    $countStmt->execute();
    $totalReports = $countStmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("
        SELECT sr.*, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number 
        FROM safety_reports sr
        LEFT JOIN user u ON sr.user_id = u.id
        $whereClause
        ORDER BY sr.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    if ($activeTab !== 'all') {
        $stmt->bind_param("sii", $activeTab, $reportsPerPage, $offset);
    } else {
        $stmt->bind_param("ii", $reportsPerPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
} catch (Exception $e) {
    die("Error fetching reports: " . $e->getMessage());
}

// Calculate pagination
$totalPages = ceil($totalReports / $reportsPerPage);

// Get report statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'under_review' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'closed' => 0
];

try {
    $statsStmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
        FROM safety_reports
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
<style>
    .swal2-textarea {
    margin: 0 !important;
    padding: 12px 16px !important;
    box-sizing: border-box !important;
}

.report-form-group .swal2-textarea {
    width: 100% !important;
    margin: 0 !important;
}
.urgency-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .urgency-low {
            background: #d1fae5;
            color: #065f46;
        }
        
        .urgency-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .urgency-high {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .urgency-emergency {
            background: #fecaca;
            color: #991b1b;
        }
        
        /* Status badges - extending existing ones */
        .status-badge.under-review {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.in-progress {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Stats cards variations */
        .stat-card.warning {
            border-left: 4px solid #f59e0b;
        }
        
        .stat-card.warning:hover,
        .stat-card.warning.active {
            border-color: #d97706;
        }
        
        .stat-card.warning .stat-icon {
            background-color: #fef3c7;
            color: #f59e0b;
        }
        
        .stat-card.closed {
            border-left: 4px solid #6b7280;
        }
        
        .stat-card.closed:hover,
        .stat-card.closed.active {
            border-color: #4b5563;
        }
        
        .stat-card.closed .stat-icon {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        
        /* Card status for mobile */
        .card-status.urgency-low {
            background: #d1fae5;
            color: #065f46;
        }
        
        .card-status.urgency-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .card-status.urgency-high {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .card-status.urgency-emergency {
            background: #fecaca;
            color: #991b1b;
        }
        
        .card-status.under-review {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .card-status.in-progress {
            background: #fef3c7;
            color: #92400e;
        }
</style>
<body>
    <?php include '../components/sidebar.php'; ?>

    <section class="home-section">
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Safety Reports Management</h2>
                <div class="table-actions">
                    <button class="btn btn-secondary" onclick="exportReports()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid" style="margin-top: 20px;">
                <div class="stat-card total">
                    <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
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
                    <div class="stat-value"><?= $stats['under_review']; ?></div>
                    <div class="stat-label">Under Review</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-value"><?= $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card resolved">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value"><?= $stats['resolved']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-card closed">
                    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                    <div class="stat-value"><?= $stats['closed']; ?></div>
                    <div class="stat-label">Closed</div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn <?= $activeTab === 'all' ? 'active' : ''; ?>" onclick="switchTab('all')">
                    <i class="fas fa-list"></i> All Reports
                </button>
                <button class="tab-btn <?= $activeTab === 'pending' ? 'active' : ''; ?>" onclick="switchTab('pending')">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="tab-btn <?= $activeTab === 'under_review' ? 'active' : ''; ?>"
                    onclick="switchTab('under_review')">
                    <i class="fas fa-search"></i> Under Review
                </button>
                <button class="tab-btn <?= $activeTab === 'in_progress' ? 'active' : ''; ?>"
                    onclick="switchTab('in_progress')">
                    <i class="fas fa-tasks"></i> In Progress
                </button>
                <button class="tab-btn <?= $activeTab === 'resolved' ? 'active' : ''; ?>"
                    onclick="switchTab('resolved')">
                    <i class="fas fa-check-circle"></i> Resolved
                </button>
            </div>

            <div class="search-container">
                <div class="search-box-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search reports by title, location, or reporter..."
                        id="searchReportInput" onkeyup="searchReports()">
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="table-responsive">
                <table class="residents-table" id="reportsTable">
                    <thead>
                        <tr>
                            <th>Reporter</th>
                            <th>Incident Type</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Urgency</th>
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
                                        <?php if ($report['is_anonymous']): ?>
                                            <div class="resident-name">Anonymous</div>
                                            <div class="resident-email">Anonymous Report</div>
                                        <?php else: ?>
                                            <div class="resident-name">
                                                <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                            </div>
                                            <div class="resident-email"><?= htmlspecialchars($report['email'] ?? 'N/A'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $incidentIcons = [
                                            'crime' => '🚨',
                                            'emergency' => '🚑',
                                            'infrastructure' => '🏗️',
                                            'environmental' => '🌿',
                                            'stray_animals' => '🐕',
                                            'drugs' => '💊',
                                            'noise' => '🔊',
                                            'other' => '📝'
                                        ];
                                        echo $incidentIcons[$report['incident_type']] ?? '📝';
                                        ?>
                                        <?= ucfirst(str_replace('_', ' ', $report['incident_type'])); ?>
                                    </td>
                                    <td><?= htmlspecialchars($report['title']); ?></td>
                                    <td><?= htmlspecialchars($report['location']); ?></td>
                                    <td>
                                        <span
                                            class="urgency-badge urgency-<?= htmlspecialchars($report['urgency_level']); ?>">
                                            <?= ucfirst($report['urgency_level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= str_replace('_', '-', $report['status']); ?>">
                                            <?= ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?= date("M d, Y", strtotime($report['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-view" onclick="viewReport(<?= $report['id']; ?>)"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-edit"
                                                onclick="updateReportStatus(<?= $report['id']; ?>)" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-delete"
                                                onclick="deleteReport(<?= $report['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <i class="fas fa-shield-alt"></i>
                                    <p>No safety reports found</p>
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
                                    <?php if ($report['is_anonymous']): ?>
                                        <div class="resident-name">Anonymous Report</div>
                                        <div class="resident-email">Anonymous</div>
                                    <?php else: ?>
                                        <div class="resident-name">
                                            <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                        </div>
                                        <div class="resident-email"><?= htmlspecialchars($report['email'] ?? 'N/A'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="card-field">Title:</div>
                                <div class="card-value"><?= htmlspecialchars($report['title']); ?></div>

                                <div class="card-field">Incident Type:</div>
                                <div class="card-value">
                                    <?= ucfirst(str_replace('_', ' ', $report['incident_type'])); ?>
                                </div>

                                <div class="card-field">Location:</div>
                                <div class="card-value"><?= htmlspecialchars($report['location']); ?></div>

                                <div class="card-field">Urgency:</div>
                                <div class="card-value">
                                    <span class="card-status urgency-<?= htmlspecialchars($report['urgency_level']); ?>">
                                        <?= ucfirst($report['urgency_level']); ?>
                                    </span>
                                </div>

                                <div class="card-field">Status:</div>
                                <div class="card-value">
                                    <span class="card-status <?= str_replace('_', '-', $report['status']); ?>">
                                        <?= ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </div>

                                <div class="card-field">Reported:</div>
                                <div class="card-value"><?= date("M d, Y", strtotime($report['created_at'])); ?></div>
                            </div>
                            <div class="card-actions">
                                <button class="btn btn-sm btn-view" onclick="viewReport(<?= $report['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-edit" onclick="updateReportStatus(<?= $report['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-delete" onclick="deleteReport(<?= $report['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalReports > 0): ?>
                <?php renderPagination($page, $totalPages, $totalReports, $offset, $reportsPerPage, $activeTab); ?>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
    <script src="./js/safety_reports.js"></script>
</body>

</html>

<?php
function renderPagination($currentPage, $totalPages, $totalItems, $offset, $itemsPerPage, $tab)
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
                    <a href="?tab=<?= $tab; ?>&page=<?= $currentPage - 1; ?>">
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
                    <a href="?tab=<?= $tab; ?>&page=1">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="current"><?= $i; ?></span>
                    <?php else: ?>
                        <a href="?tab=<?= $tab; ?>&page=<?= $i; ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                    <a href="?tab=<?= $tab; ?>&page=<?= $totalPages; ?>"><?= $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?tab=<?= $tab; ?>&page=<?= $currentPage + 1; ?>">
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