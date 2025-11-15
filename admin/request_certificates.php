<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Determine active tab - now only 'types' or 'requests'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'types';

// Pagination
$requestsPerPage = 8;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$currentPage = max(1, $currentPage);
$offset = ($currentPage - 1) * $requestsPerPage;

// Initialize variables
$requests = [];
$documentTypes = [];
$totalRequests = 0;
$totalTypes = 0;

// Fetch data based on active tab
if ($activeTab === 'requests') {
    // Fetch document requests
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM document_requests dr");
        $countStmt->execute();
        $totalRequests = $countStmt->get_result()->fetch_assoc()['total'];

        $stmt = $conn->prepare("
            SELECT 
                dr.*,
                dt.name as document_name,
                dt.icon,
                dt.type as document_type,
                dt.fee,
                dt.processing_days,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                u.contact_number,
                u.image as user_image
            FROM document_requests dr
            INNER JOIN document_types dt ON dr.document_type_id = dt.id
            INNER JOIN user u ON dr.user_id = u.id
            ORDER BY dr.submitted_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $requestsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    } catch (Exception $e) {
        die("Error fetching requests: " . $e->getMessage());
    }

    // Get request statistics
    $stats = [
        'total' => 0,
        'pending' => 0,
        'processing' => 0,
        'approved' => 0,
        'ready' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0
    ];
    try {
        $statsStmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM document_requests
        ");
        $stats = $statsStmt->fetch_assoc();
    } catch (Exception $e) {
        // Keep default stats
    }
} else {
    // Fetch document types
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM document_types");
        $countStmt->execute();
        $totalTypes = $countStmt->get_result()->fetch_assoc()['total'];

        $stmt = $conn->prepare("
            SELECT 
                dt.*,
                COUNT(dr.id) as request_count
            FROM document_types dt
            LEFT JOIN document_requests dr ON dt.id = dr.document_type_id
            GROUP BY dt.id
            ORDER BY dt.name ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $requestsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $documentTypes[] = $row;
        }
    } catch (Exception $e) {
        die("Error fetching document types: " . $e->getMessage());
    }
}

// Calculate pagination
$totalPages = $activeTab === 'requests' ? ceil($totalRequests / $requestsPerPage) : ceil($totalTypes / $requestsPerPage);
$totalItems = $activeTab === 'requests' ? $totalRequests : $totalTypes;
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <?php include '../components/header_links.php'; ?>
    <?php include '../components/admin_side_header.php'; ?>
</head>
<style>
    /* Uploaded Files Section */
    .uploaded-files-grid {
        display: grid;
        gap: 12px;
        margin-bottom: 24px;
    }

    .file-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .file-item:hover {
        background: #e9ecef;
        border-color: #dee2e6;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .file-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 8px;
        font-size: 20px;
    }

    .file-info {
        flex: 1;
        min-width: 0;
    }

    .file-name {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }

    .file-meta {
        font-size: 12px;
        color: #6c757d;
    }

    .file-action {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 6px;
        color: #00247c;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .file-item:hover .file-action {
        background: #00247c;
        color: white;
    }

    .status-badge.approved-unpaid {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
        border: 1px solid #ffc107;
        font-weight: 600;
    }

    .status-badge.approved-paid {
        background: linear-gradient(135deg, #d4edda 0%, #95e1d3 100%);
        color: #155724;
        border: 1px solid #28a745;
        font-weight: 600;
    }

    .card-status.approved-unpaid {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffc107;
    }

    .card-status.approved-paid {
        background: #d4edda;
        color: #155724;
        border: 1px solid #28a745;
    }

    /* Payment receipt section styling */
    .payment-receipt-section {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
        text-align: center;
    }

    .payment-receipt-section h4 {
        color: #28a745;
        margin-bottom: 15px;
        font-size: 16px;
        font-weight: 700;
    }

    .receipt-image {
        max-width: 100%;
        max-height: 400px;
        border: 3px solid #dee2e6;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .receipt-image:hover {
        transform: scale(1.02);
    }

    .payment-info-badge {
        display: inline-block;
        padding: 8px 16px;
        background: #28a745;
        color: white;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin: 10px 0;
    }

    .payment-date-info {
        color: #6c757d;
        font-size: 13px;
        margin-top: 10px;
    }

    /* Responsive file grid */
    @media (max-width: 768px) {
        .file-item {
            padding: 10px 12px;
        }

        .file-icon {
            width: 36px;
            height: 36px;
            font-size: 18px;
        }

        .file-name {
            font-size: 13px;
        }

        .file-meta {
            font-size: 11px;
        }

        .file-action {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }
    }
</style>

<body>
    <?php include '../components/sidebar.php'; ?>

    <section class="home-section">
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Document Management</h2>
                <div class="table-actions">
                    <?php if ($activeTab === 'types'): ?>
                        <button class="btn btn-primary" onclick="addDocumentType()">
                            <i class="fas fa-plus"></i> Add Document Type
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" onclick="exportRequests()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Navigation - Only 2 Tabs -->
            <div class="tab-navigation">
                <button class="tab-btn <?= $activeTab === 'types' ? 'active' : ''; ?>" onclick="switchTab('types')">
                    <i class="fas fa-file-alt"></i> Document Types
                </button>
                <button class="tab-btn <?= $activeTab === 'requests' ? 'active' : ''; ?>"
                    onclick="switchTab('requests')">
                    <i class="fas fa-list"></i> Document Requests
                </button>
            </div>

            <?php if ($activeTab === 'requests'): ?>
                <!-- Statistics Cards for Requests -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-value"><?= $stats['total']; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?= $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card processing">
                        <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                        <div class="stat-value"><?= $stats['processing']; ?></div>
                        <div class="stat-label">Processing</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?= $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card ready">
                        <div class="stat-icon"><i class="fas fa-box-open"></i></div>
                        <div class="stat-value"><?= $stats['ready']; ?></div>
                        <div class="stat-label">Ready</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                        <div class="stat-value"><?= $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="search-container">
                <div class="search-box-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input"
                        placeholder="<?= $activeTab === 'types' ? 'Search document types...' : 'Search by name, request ID, or document...'; ?>"
                        id="searchInput" onkeyup="searchData()">
                </div>
            </div>

            <?php if ($activeTab === 'requests'): ?>
                <!-- Document Requests Table -->
                <div class="table-responsive">
                    <table class="residents-table" id="requestsTable">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Requester</th>
                                <th>Document</th>
                                <th>Type</th>
                                <th>Purpose</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <div class="resident-name"><?= htmlspecialchars($request['request_id']); ?></div>
                                        </td>
                                        <td>
                                            <div class="resident-name">
                                                <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                            </div>
                                            <div class="resident-email"><?= htmlspecialchars($request['email'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas <?= htmlspecialchars($request['icon']); ?>"
                                                    style="color: #667eea;"></i>
                                                <span><?= htmlspecialchars($request['document_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge <?= $request['document_type'] === 'certificate' ? 'certificate' : 'permit'; ?>">
                                                <?= ucfirst($request['document_type']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?>
                                        </td>
                                        <td>
                                            <?= $request['fee'] == 0 ? '<span style="color: #28a745; font-weight: 600;">Free</span>' : '₱' . number_format($request['fee'], 2); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $displayStatus = $request['status'];
                                            $statusClass = $request['status'];

                                            // If status is approved, show payment status
                                            if ($request['status'] === 'approved') {
                                                if ($request['fee'] > 0) {
                                                    // Has fee - show payment status
                                                    $displayStatus = $request['payment_status'] === 'paid' ? 'Approved-Paid' : 'Approved-Unpaid';
                                                    $statusClass = $request['payment_status'] === 'paid' ? 'approved-paid' : 'approved-unpaid';
                                                } else {
                                                    // No fee - just show approved
                                                    $displayStatus = 'Approved';
                                                    $statusClass = 'approved';
                                                }
                                            }
                                            ?>
                                            <span class="status-badge <?= htmlspecialchars($statusClass); ?>">
                                                <?= ucfirst(str_replace('_', ' ', $displayStatus)); ?>
                                            </span>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($request['submitted_date'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-view"
                                                    onclick="viewRequestDetails(<?= $request['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-edit"
                                                    onclick="updateRequestStatus(<?= $request['id']; ?>)" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete"
                                                    onclick="deleteRequest(<?= $request['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No requests found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards for Requests -->
                <div class="mobile-cards">
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="resident-card">
                                <div class="card-header">
                                    <div>
                                        <div class="resident-name">
                                            <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                        </div>
                                        <div class="resident-email"><?= htmlspecialchars($request['request_id']); ?></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-field">Document:</div>
                                    <div class="card-value">
                                        <i class="fas <?= htmlspecialchars($request['icon']); ?>"></i>
                                        <?= htmlspecialchars($request['document_name']); ?>
                                    </div>
                                    <div class="card-field">Type:</div>
                                    <div class="card-value">
                                        <span
                                            class="card-status <?= $request['document_type'] === 'certificate' ? 'certificate' : 'permit'; ?>">
                                            <?= ucfirst($request['document_type']); ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Purpose:</div>
                                    <div class="card-value">
                                        <?= htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?>
                                    </div>
                                    <div class="card-field">Fee:</div>
                                    <div class="card-value">
                                        <?= $request['fee'] == 0 ? '<span style="color: #28a745;">Free</span>' : '₱' . number_format($request['fee'], 2); ?>
                                    </div>
                                    <div class="card-field">Status:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= htmlspecialchars($request['status']); ?>">
                                            <?= ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Submitted:</div>
                                    <div class="card-value"><?= date("M d, Y", strtotime($request['submitted_date'])); ?></div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-view" onclick="viewRequestDetails(<?= $request['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-edit" onclick="updateRequestStatus(<?= $request['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete" onclick="deleteRequest(<?= $request['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Document Types Table -->
                <div class="table-responsive">
                    <table class="residents-table" id="typesTable">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Icon</th>
                                <th>Fee</th>
                                <th>Processing Days</th>
                                <th>Total Requests</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($documentTypes) > 0): ?>
                                <?php foreach ($documentTypes as $type): ?>
                                    <tr>
                                        <td>
                                            <div class="resident-name"><?= htmlspecialchars($type['name']); ?></div>
                                            <div class="resident-email">
                                                <?= htmlspecialchars(substr($type['description'] ?? '', 0, 50)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge <?= $type['type'] === 'certificate' ? 'certificate' : 'permit'; ?>">
                                                <?= ucfirst($type['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas <?= htmlspecialchars($type['icon']); ?>"
                                                style="font-size: 1.5rem; color: #667eea;"></i>
                                        </td>
                                        <td>
                                            <?= $type['fee'] == 0 ? '<span style="color: #28a745; font-weight: 600;">Free</span>' : '₱' . number_format($type['fee'], 2); ?>
                                        </td>
                                        <td><?= htmlspecialchars($type['processing_days']); ?></td>
                                        <td><?= $type['request_count']; ?></td>
                                        <td>
                                            <span class="status-badge <?= $type['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?= $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- <button class="btn btn-sm btn-view" onclick="viewDocumentType(<?= $type['id']; ?>)"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button> -->
                                                <button class="btn btn-sm btn-edit" onclick="editDocumentType(<?= $type['id']; ?>)"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete"
                                                    onclick="deleteDocumentType(<?= $type['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No document types found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalItems > 0): ?>
                <?php renderPagination($currentPage, $totalPages, $totalItems, $offset, $requestsPerPage, $activeTab); ?>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
    <script>
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }

        function searchData() {
            // Implement search functionality based on active tab
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.querySelector('.residents-table tbody');
            const rows = table.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent || row.innerText;
                if (text.toUpperCase().indexOf(filter) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
    </script>
    <?php if ($activeTab === 'requests'): ?>
        <script src="./js/document_requests.js"></script>
    <?php endif; ?>
    <?php if ($activeTab === 'types'): ?>
        <script src="./js/document_types.js"></script>
    <?php endif; ?>
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