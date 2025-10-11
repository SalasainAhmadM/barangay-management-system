<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Determine active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'notifications';

// Pagination
$itemsPerPage = 8;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$currentPage = max(1, $currentPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Get filter parameters
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$filterUser = isset($_GET['filter_user']) ? (int) $_GET['filter_user'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Initialize variables
$notifications = [];
$preferences = [];
$totalNotifications = 0;
$totalPreferences = 0;

// Fetch data based on active tab
if ($activeTab === 'notifications') {
    // Build WHERE clause based on filters
    $whereConditions = [];
    $params = [];
    $types = "";

    // Filter by type or read status
    if ($filterType !== 'all') {
        if ($filterType === 'read') {
            $whereConditions[] = "n.is_read = 1";
        } elseif ($filterType === 'unread') {
            $whereConditions[] = "n.is_read = 0";
        } else {
            // Filter by notification type (waste, announcement, success, etc.)
            $whereConditions[] = "n.type = ?";
            $params[] = $filterType;
            $types .= "s";
        }
    }

    // Filter by user
    if ($filterUser > 0) {
        $whereConditions[] = "n.user_id = ?";
        $params[] = $filterUser;
        $types .= "i";
    }

    // Search filter
    if (!empty($searchQuery)) {
        $whereConditions[] = "(n.title LIKE ? OR n.message LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%{$searchQuery}%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "sssss";
    }

    $whereClause = count($whereConditions) > 0 ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Fetch notifications with filters
    try {
        // Count total with filters
        $countSql = "SELECT COUNT(*) as total FROM notifications n 
                     INNER JOIN user u ON n.user_id = u.id 
                     {$whereClause}";
        $countStmt = $conn->prepare($countSql);
        if (count($params) > 0) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalNotifications = $countStmt->get_result()->fetch_assoc()['total'];

        // Fetch notifications with filters
        $sql = "
            SELECT 
                n.*,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                u.contact_number
            FROM notifications n
            INNER JOIN user u ON n.user_id = u.id
            {$whereClause}
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $conn->prepare($sql);
        $params[] = $itemsPerPage;
        $params[] = $offset;
        $types .= "ii";
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    } catch (Exception $e) {
        die("Error fetching notifications: " . $e->getMessage());
    }

    // Get notification statistics (always show total stats, not filtered)
    $stats = [
        'total' => 0,
        'read' => 0,
        'unread' => 0,
        'waste' => 0,
        'request' => 0,
        'announcement' => 0,
        'alert' => 0,
        'success' => 0
    ];
    try {
        $statsStmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                SUM(CASE WHEN type = 'waste' THEN 1 ELSE 0 END) as waste,
                SUM(CASE WHEN type = 'request' THEN 1 ELSE 0 END) as request,
                SUM(CASE WHEN type = 'announcement' THEN 1 ELSE 0 END) as announcement,
                SUM(CASE WHEN type = 'alert' THEN 1 ELSE 0 END) as alert,
                SUM(CASE WHEN type = 'success' THEN 1 ELSE 0 END) as success
            FROM notifications
        ");
        $statsData = $statsStmt->fetch_assoc();
        $stats = [
            'total' => $statsData['total'],
            'read' => $statsData['read_count'],
            'unread' => $statsData['unread_count'],
            'waste' => $statsData['waste'],
            'request' => $statsData['request'],
            'announcement' => $statsData['announcement'],
            'alert' => $statsData['alert'],
            'success' => $statsData['success']
        ];
    } catch (Exception $e) {
        // Keep default stats
    }
} else {
    // Fetch notification preferences (existing code)
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notification_preferences");
        $countStmt->execute();
        $totalPreferences = $countStmt->get_result()->fetch_assoc()['total'];

        $stmt = $conn->prepare("
            SELECT 
                np.*,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                u.contact_number,
                u.image as user_image
            FROM notification_preferences np
            INNER JOIN user u ON np.user_id = u.id
            ORDER BY u.first_name ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $itemsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $preferences[] = $row;
        }
    } catch (Exception $e) {
        die("Error fetching preferences: " . $e->getMessage());
    }

    // Get preferences statistics
    $prefStats = [
        'total_users' => 0,
        'waste_enabled' => 0,
        'request_enabled' => 0,
        'announcement_enabled' => 0,
        'sms_enabled' => 0
    ];
    try {
        $prefStatsStmt = $conn->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN waste_reminders = 1 THEN 1 ELSE 0 END) as waste_enabled,
                SUM(CASE WHEN request_updates = 1 THEN 1 ELSE 0 END) as request_enabled,
                SUM(CASE WHEN announcements = 1 THEN 1 ELSE 0 END) as announcement_enabled,
                SUM(CASE WHEN sms_notifications = 1 THEN 1 ELSE 0 END) as sms_enabled
            FROM notification_preferences
        ");
        $prefStats = $prefStatsStmt->fetch_assoc();
    } catch (Exception $e) {
        // Keep default stats
    }
}

// Calculate pagination
$totalPages = $activeTab === 'notifications' ? ceil($totalNotifications / $itemsPerPage) : ceil($totalPreferences / $itemsPerPage);
$totalItems = $activeTab === 'notifications' ? $totalNotifications : $totalPreferences;

// Build URL parameters for pagination
function buildUrlParams($excludeParams = [])
{
    global $activeTab, $filterType, $filterUser, $searchQuery;
    $params = [];

    $params['tab'] = $activeTab;

    if (!in_array('filter_type', $excludeParams) && $filterType !== 'all') {
        $params['filter_type'] = $filterType;
    }

    if (!in_array('filter_user', $excludeParams) && $filterUser > 0) {
        $params['filter_user'] = $filterUser;
    }

    if (!in_array('search', $excludeParams) && !empty($searchQuery)) {
        $params['search'] = $searchQuery;
    }

    return http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <?php include '../components/header_links.php'; ?>
    <?php include '../components/admin_side_header.php'; ?>
</head>
<style>
    /* Your existing styles */
    .search-filter-wrapper {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        flex-wrap: wrap;
    }

    .search-container {
        padding: 0;
        border-bottom: none;
        flex: 0 0 auto;
    }

    .search-box-wrapper {
        position: relative;
        width: 300px;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px 12px 45px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        background: #f9fafb;
    }

    .search-input:focus {
        outline: none;
        border-color: #00247c;
        box-shadow: 0 0 0 3px rgba(0, 36, 124, 0.1);
        background: #fff;
    }

    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 16px;
    }

    .filters-group {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        flex: 1;
    }

    .filter-container {
        flex: 0 0 auto;
    }

    .filter-box-wrapper {
        position: relative;
        min-width: 180px;
    }

    .filter-select {
        width: 100%;
        padding: 12px 16px 12px 40px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        background: #f9fafb;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 35px;
    }

    .filter-select:focus {
        outline: none;
        border-color: #00247c;
        box-shadow: 0 0 0 3px rgba(0, 36, 124, 0.1);
        background-color: #fff;
    }

    .filter-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 16px;
        pointer-events: none;
        z-index: 1;
    }

    /* Active stat card styling */
    .clickable-stat {
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .clickable-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .clickable-stat.active {
        border: 2px solid #00247c;
        box-shadow: 0 0 0 3px rgba(0, 36, 124, 0.1);
    }

    @media (max-width: 1024px) {
        .search-filter-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box-wrapper {
            width: 100%;
        }

        .filters-group {
            width: 100%;
        }

        .filter-box-wrapper {
            flex: 1;
            min-width: 150px;
        }
    }

    @media (max-width: 768px) {
        .filters-group {
            flex-direction: column;
            gap: 10px;
        }

        .filter-container {
            width: 100%;
        }

        .filter-box-wrapper {
            width: 100%;
        }
    }
</style>

<body>
    <?php include '../components/sidebar.php'; ?>

    <section class="home-section">
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">Notification Management</h2>
                <div class="table-actions">
                    <?php if ($activeTab === 'notifications'): ?>
                        <button class="btn btn-primary" onclick="createNotification()">
                            <i class="fas fa-plus"></i> Create Notification
                        </button>
                        <button class="btn btn-secondary" onclick="exportNotification()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" onclick="exportPreferences()">
                            <i class="fas fa-download"></i> Export Preferences
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn <?= $activeTab === 'notifications' ? 'active' : ''; ?>"
                    onclick="switchTab('notifications')">
                    <i class="fas fa-bell"></i> Notifications
                </button>
                <button class="tab-btn <?= $activeTab === 'preferences' ? 'active' : ''; ?>"
                    onclick="switchTab('preferences')">
                    <i class="fas fa-cog"></i> User Preferences
                </button>
            </div>

            <?php if ($activeTab === 'notifications'): ?>
                <!-- Statistics Cards for Notifications (Clickable) -->
                <div class="stats-grid">
                    <div class="stat-card total clickable-stat <?= $filterType === 'all' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('all')" data-filter="all">
                        <div class="stat-icon"><i class="fas fa-bell"></i></div>
                        <div class="stat-value"><?= $stats['total']; ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-card unread clickable-stat <?= $filterType === 'unread' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('unread')" data-filter="unread">
                        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-value"><?= $stats['unread']; ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                    <div class="stat-card read clickable-stat <?= $filterType === 'read' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('read')" data-filter="read">
                        <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                        <div class="stat-value"><?= $stats['read']; ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                    <div class="stat-card waste clickable-stat <?= $filterType === 'waste' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('waste')" data-filter="waste">
                        <div class="stat-icon"><i class="fas fa-trash-alt"></i></div>
                        <div class="stat-value"><?= $stats['waste']; ?></div>
                        <div class="stat-label">Waste</div>
                    </div>
                    <div class="stat-card announcement clickable-stat <?= $filterType === 'announcement' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('announcement')" data-filter="announcement">
                        <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        <div class="stat-value"><?= $stats['announcement']; ?></div>
                        <div class="stat-label">Announcements</div>
                    </div>
                    <div class="stat-card success clickable-stat <?= $filterType === 'success' ? 'active' : ''; ?>"
                        onclick="filterByStatCard('success')" data-filter="success">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?= $stats['success']; ?></div>
                        <div class="stat-label">Success</div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistics Cards for Preferences -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?= $prefStats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card waste">
                        <div class="stat-icon"><i class="fas fa-trash-alt"></i></div>
                        <div class="stat-value"><?= $prefStats['waste_enabled']; ?></div>
                        <div class="stat-label">Waste Reminders</div>
                    </div>
                    <div class="stat-card request">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-value"><?= $prefStats['request_enabled']; ?></div>
                        <div class="stat-label">Request Updates</div>
                    </div>
                    <div class="stat-card announcement">
                        <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        <div class="stat-value"><?= $prefStats['announcement_enabled']; ?></div>
                        <div class="stat-label">Announcements</div>
                    </div>
                    <div class="stat-card sms">
                        <div class="stat-icon"><i class="fas fa-sms"></i></div>
                        <div class="stat-value"><?= $prefStats['sms_enabled']; ?></div>
                        <div class="stat-label">SMS Enabled</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'notifications'): ?>
                <div class="search-filter-wrapper">
                    <div class="search-container">
                        <form method="GET" action="" id="searchForm">
                            <input type="hidden" name="tab" value="notifications">
                            <input type="hidden" name="filter_type" value="<?= htmlspecialchars($filterType); ?>">
                            <input type="hidden" name="filter_user" value="<?= $filterUser; ?>">
                            <div class="search-box-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" placeholder="Search notifications..." name="search"
                                    id="searchInput" value="<?= htmlspecialchars($searchQuery); ?>">
                            </div>
                        </form>
                    </div>

                    <div class="filters-group">
                        <div class="filter-container">
                            <div class="filter-box-wrapper">
                                <i class="fas fa-filter filter-icon"></i>
                                <select class="filter-select" id="userFilter" onchange="filterByUser()">
                                    <option value="">All Users</option>
                                    <?php
                                    try {
                                        $userStmt = $conn->query("
                                            SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
                                            FROM user u
                                            INNER JOIN notifications n ON u.id = n.user_id
                                            ORDER BY u.first_name ASC, u.last_name ASC
                                        ");
                                        while ($user = $userStmt->fetch_assoc()) {
                                            $selected = ($filterUser == $user['id']) ? 'selected' : '';
                                            echo '<option value="' . $user['id'] . '" ' . $selected . '>' .
                                                htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) .
                                                '</option>';
                                        }
                                    } catch (Exception $e) {
                                        // Silently fail if error
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'notifications'): ?>
                <!-- Notifications Table -->
                <div class="table-responsive">
                    <table class="residents-table" id="notificationsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <tr>
                                        <td>
                                            <div class="resident-name">#<?= htmlspecialchars($notification['id']); ?></div>
                                        </td>
                                        <td>
                                            <div class="resident-name">
                                                <?= htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                            </div>
                                            <div class="resident-email"><?= htmlspecialchars($notification['email'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= htmlspecialchars($notification['type']); ?>">
                                                <i class="fas <?= htmlspecialchars($notification['icon']); ?>"></i>
                                                <?= ucfirst($notification['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="resident-name"><?= htmlspecialchars($notification['title']); ?></div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(substr($notification['message'], 0, 60)) . (strlen($notification['message']) > 60 ? '...' : ''); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $notification['is_read'] ? 'read' : 'unread'; ?>">
                                                <?= $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td><?= date("M d, Y h:i A", strtotime($notification['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-edit"
                                                    onclick="editNotification(<?= $notification['id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete"
                                                    onclick="deleteNotification(<?= $notification['id']; ?>)" title="Delete">
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
                                        <p>No notifications found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards for Notifications -->
                <div class="mobile-cards">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="resident-card">
                                <div class="card-header">
                                    <div>
                                        <div class="resident-name">
                                            <?= htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                        </div>
                                        <div class="resident-email">#<?= htmlspecialchars($notification['id']); ?></div>
                                    </div>
                                    <span class="status-badge <?= $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <?= $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="card-field">Type:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= htmlspecialchars($notification['type']); ?>">
                                            <i class="fas <?= htmlspecialchars($notification['icon']); ?>"></i>
                                            <?= ucfirst($notification['type']); ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Title:</div>
                                    <div class="card-value"><?= htmlspecialchars($notification['title']); ?></div>
                                    <div class="card-field">Message:</div>
                                    <div class="card-value"><?= htmlspecialchars(substr($notification['message'], 0, 80)); ?></div>
                                    <div class="card-field">Created:</div>
                                    <div class="card-value"><?= date("M d, Y h:i A", strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-edit" onclick="editNotification(<?= $notification['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-delete" onclick="deleteNotification(<?= $notification['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- User Preferences Table (existing code unchanged) -->
                <div class="table-responsive">
                    <table class="residents-table" id="preferencesTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Waste Reminders</th>
                                <th>Request Updates</th>
                                <th>Announcements</th>
                                <th>SMS Notifications</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($preferences) > 0): ?>
                                <?php foreach ($preferences as $pref): ?>
                                    <tr>
                                        <td>
                                            <div class="resident-name">
                                                <?= htmlspecialchars($pref['first_name'] . ' ' . $pref['last_name']); ?>
                                            </div>
                                            <div class="resident-email"><?= htmlspecialchars($pref['email'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $pref['waste_reminders'] ? 'enabled' : 'disabled'; ?>">
                                                <i class="fas <?= $pref['waste_reminders'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <?= $pref['waste_reminders'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $pref['request_updates'] ? 'enabled' : 'disabled'; ?>">
                                                <i class="fas <?= $pref['request_updates'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <?= $pref['request_updates'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $pref['announcements'] ? 'enabled' : 'disabled'; ?>">
                                                <i class="fas <?= $pref['announcements'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <?= $pref['announcements'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $pref['sms_notifications'] ? 'enabled' : 'disabled'; ?>">
                                                <i class="fas <?= $pref['sms_notifications'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                                <?= $pref['sms_notifications'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td><?= date("M d, Y h:i A", strtotime($pref['updated_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-edit"
                                                    onclick="editPreferences(<?= $pref['user_id']; ?>)" title="Edit Preferences">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No user preferences found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards for Preferences -->
                <div class="mobile-cards">
                    <?php if (count($preferences) > 0): ?>
                        <?php foreach ($preferences as $pref): ?>
                            <div class="resident-card">
                                <div class="card-header">
                                    <div>
                                        <div class="resident-name">
                                            <?= htmlspecialchars($pref['first_name'] . ' ' . $pref['last_name']); ?>
                                        </div>
                                        <div class="resident-email"><?= htmlspecialchars($pref['email']); ?></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card-field">Waste Reminders:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= $pref['waste_reminders'] ? 'enabled' : 'disabled'; ?>">
                                            <?= $pref['waste_reminders'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Request Updates:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= $pref['request_updates'] ? 'enabled' : 'disabled'; ?>">
                                            <?= $pref['request_updates'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Announcements:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= $pref['announcements'] ? 'enabled' : 'disabled'; ?>">
                                            <?= $pref['announcements'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    <div class="card-field">SMS Notifications:</div>
                                    <div class="card-value">
                                        <span class="card-status <?= $pref['sms_notifications'] ? 'enabled' : 'disabled'; ?>">
                                            <?= $pref['sms_notifications'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                    <div class="card-field">Last Updated:</div>
                                    <div class="card-value"><?= date("M d, Y h:i A", strtotime($pref['updated_at'])); ?></div>
                                </div>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-edit" onclick="editPreferences(<?= $pref['user_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalItems > 0): ?>
                <?php renderPagination($currentPage, $totalPages, $totalItems, $offset, $itemsPerPage, $activeTab); ?>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
    <script>
        // Tab switching function
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }

        // Filter by stat card (clickable statistics)
        function filterByStatCard(filterType) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('filter_type', filterType);
            currentUrl.searchParams.set('page', '1'); // Reset to first page
            currentUrl.searchParams.delete('search'); // Clear search when filtering by stat
            window.location.href = currentUrl.toString();
        }

        // Filter by user dropdown
        function filterByUser() {
            const userId = document.getElementById('userFilter').value;
            const currentUrl = new URL(window.location.href);

            if (userId) {
                currentUrl.searchParams.set('filter_user', userId);
            } else {
                currentUrl.searchParams.delete('filter_user');
            }
            currentUrl.searchParams.set('page', '1'); // Reset to first page
            window.location.href = currentUrl.toString();
        }

        // Search functionality with debounce
        let searchTimeout;

        // Initialize search functionality
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');

            // Only add listeners if elements exist
            if (searchInput && searchForm) {
                // Input event with debounce
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        searchForm.submit();
                    }, 500); // 500ms delay
                });

                // Handle Enter key in search
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchTimeout); // Clear any pending timeout
                        searchForm.submit();
                    }
                });
            }

            // Update active state on stat cards based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentFilter = urlParams.get('filter_type') || 'all';

            // Remove active class from all cards
            document.querySelectorAll('.clickable-stat').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class to current filter
            const activeCard = document.querySelector(`.clickable-stat[data-filter="${currentFilter}"]`);
            if (activeCard) {
                activeCard.classList.add('active');
            }
        });

    </script>
    <?php if ($activeTab === 'notifications'): ?>
        <script src="./js/notifications.js"></script>
    <?php endif; ?>
    <?php if ($activeTab === 'preferences'): ?>
        <script src="./js/notification_preferences.js"></script>
    <?php endif; ?>
</body>

</html>

<?php
function renderPagination($currentPage, $totalPages, $totalItems, $offset, $itemsPerPage, $tab)
{
    global $filterType, $filterUser, $searchQuery;

    $startRecord = $totalItems > 0 ? $offset + 1 : 0;
    $endRecord = min($offset + $itemsPerPage, $totalItems);

    // Build query string for pagination links
    $queryParams = ['tab' => $tab];
    if ($filterType !== 'all') {
        $queryParams['filter_type'] = $filterType;
    }
    if ($filterUser > 0) {
        $queryParams['filter_user'] = $filterUser;
    }
    if (!empty($searchQuery)) {
        $queryParams['search'] = $searchQuery;
    }

    function buildPaginationUrl($page, $params)
    {
        $params['page'] = $page;
        return '?' . http_build_query($params);
    }
    ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <?= $startRecord; ?>-<?= $endRecord; ?> of <?= $totalItems; ?> items
            <?php if ($filterType !== 'all' || $filterUser > 0 || !empty($searchQuery)): ?>
                (filtered)
            <?php endif; ?>
        </div>
        <div class="pagination-controls">
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= buildPaginationUrl($currentPage - 1, $queryParams); ?>">
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
                    <a href="<?= buildPaginationUrl(1, $queryParams); ?>">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="current"><?= $i; ?></span>
                    <?php else: ?>
                        <a href="<?= buildPaginationUrl($i, $queryParams); ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="disabled">...</span>
                    <?php endif; ?>
                    <a href="<?= buildPaginationUrl($totalPages, $queryParams); ?>"><?= $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= buildPaginationUrl($currentPage + 1, $queryParams); ?>">
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