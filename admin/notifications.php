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

// Initialize variables
$notifications = [];
$preferences = [];
$totalNotifications = 0;
$totalPreferences = 0;

// Fetch data based on active tab
if ($activeTab === 'notifications') {
    // Fetch notifications
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications");
        $countStmt->execute();
        $totalNotifications = $countStmt->get_result()->fetch_assoc()['total'];

        $stmt = $conn->prepare("
            SELECT 
                n.*,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                u.contact_number
            FROM notifications n
            INNER JOIN user u ON n.user_id = u.id
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $itemsPerPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    } catch (Exception $e) {
        die("Error fetching notifications: " . $e->getMessage());
    }

    // Get notification statistics
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
    // Fetch notification preferences
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
                <h2 class="table-title">Notification Management</h2>
                <div class="table-actions">
                    <?php if ($activeTab === 'notifications'): ?>
                        <button class="btn btn-primary" onclick="createNotification()">
                            <i class="fas fa-plus"></i> Create Notification
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
                <!-- Statistics Cards for Notifications -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-bell"></i></div>
                        <div class="stat-value"><?= $stats['total']; ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                    <div class="stat-card unread">
                        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-value"><?= $stats['unread']; ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                    <div class="stat-card read">
                        <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                        <div class="stat-value"><?= $stats['read']; ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                    <div class="stat-card waste">
                        <div class="stat-icon"><i class="fas fa-trash-alt"></i></div>
                        <div class="stat-value"><?= $stats['waste']; ?></div>
                        <div class="stat-label">Waste</div>
                    </div>
                    <div class="stat-card announcement">
                        <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                        <div class="stat-value"><?= $stats['announcement']; ?></div>
                        <div class="stat-label">Announcements</div>
                    </div>
                    <div class="stat-card success">
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

            <div class="search-container">
                <div class="search-box-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input"
                        placeholder="<?= $activeTab === 'notifications' ? 'Search notifications...' : 'Search users...'; ?>"
                        id="searchInput" onkeyup="searchData()">
                </div>
            </div>

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
                                                <button class="btn btn-sm btn-view"
                                                    onclick="viewNotification(<?= $notification['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
                                    <button class="btn btn-sm btn-view" onclick="viewNotification(<?= $notification['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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
                <!-- User Preferences Table -->
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
        function switchTab(tab) {
            window.location.href = '?tab=' + tab;
        }

        function searchData() {
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

        // Notification Management Functions
        function createNotification() {
            // Implement create notification modal/form
            alert('Create notification functionality to be implemented');
        }

        function viewNotification(id) {
            // Implement view notification details
            alert('View notification ' + id);
        }

        function editNotification(id) {
            // Implement edit notification
            alert('Edit notification ' + id);
        }

        function deleteNotification(id) {
            if (confirm('Are you sure you want to delete this notification?')) {
                // Implement delete functionality
                alert('Delete notification ' + id);
            }
        }

        // Preferences Management Functions
        function editPreferences(userId) {
            // Implement edit preferences modal/form
            alert('Edit preferences for user ' + userId);
        }

        function exportPreferences() {
            // Implement export functionality
            alert('Export preferences functionality to be implemented');
        }
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