<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get user's notification preferences
$preferences = null;
$stmt = $conn->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc();
$stmt->close();

if (!$preferences) {
    // Create default preferences if they don't exist
    $stmt = $conn->prepare("INSERT INTO notification_preferences (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $preferences = [
        'waste_reminders' => 1,
        'request_updates' => 1,
        'announcements' => 1,
        'sms_notifications' => 1
    ];
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
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Notifications</h1>
            <p>Stay updated with waste collection reminders, request updates, and announcements</p>
        </div>

        <!-- Notifications Header Actions -->
        <div class="section-card">
            <div class="section-card-body">
                <div class="notifications-header">
                    <div class="notification-filters">
                        <button class="notification-filter-btn active" data-filter="all">
                            <i class="fas fa-inbox"></i> All
                        </button>
                        <button class="notification-filter-btn" data-filter="waste">
                            <i class="fas fa-trash-alt"></i> Waste Collection
                        </button>
                        <button class="notification-filter-btn" data-filter="request">
                            <i class="fas fa-file-alt"></i> Requests
                        </button>
                        <button class="notification-filter-btn" data-filter="announcement">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </button>
                        <button class="notification-filter-btn" data-filter="unread">
                            <i class="fas fa-envelope"></i> Unread
                        </button>
                    </div>

                    <div class="notifications-actions">
                        <button class="mark-read-btn" onclick="markAllAsRead()">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                        <button class="clear-btn" onclick="clearAll()">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="notifications-list" id="notificationsList">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Loading notifications...
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="notification-settings">
            <h3>
                <i class="fas fa-cog"></i> Notification Preferences
            </h3>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Waste Collection Reminders</h4>
                    <p>Get notified before scheduled waste collection</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo $preferences['waste_reminders'] ? 'checked' : ''; ?>
                        onchange="toggleNotificationSetting(this, 'waste_reminders')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Request Updates</h4>
                    <p>Receive updates on your certificate and permit requests</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo $preferences['request_updates'] ? 'checked' : ''; ?>
                        onchange="toggleNotificationSetting(this, 'request_updates')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Barangay Announcements</h4>
                    <p>Stay informed about community events and updates</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo $preferences['announcements'] ? 'checked' : ''; ?>
                        onchange="toggleNotificationSetting(this, 'announcements')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>SMS Notifications</h4>
                    <p>Receive notifications via SMS</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" <?php echo $preferences['sms_notifications'] ? 'checked' : ''; ?>
                        onchange="toggleNotificationSetting(this, 'sms_notifications')">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </main>

    <?php include '../components/cdn_scripts.php'; ?>
    <?php include '../components/footer.php'; ?>

    <script>
        let currentFilter = 'all';

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadNotifications();

            // Add click event listeners to filter buttons
            document.querySelectorAll('.notification-filter-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.notification-filter-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    loadNotifications();
                });
            });
        });

        function loadNotifications() {
            const notificationsList = document.getElementById('notificationsList');
            notificationsList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading notifications...</div>';

            fetch(`./endpoints/notifications.php?action=get_all&filter=${currentFilter}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayNotifications(data.notifications);
                    } else {
                        showError('Failed to load notifications');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error loading notifications');
                });
        }

        function displayNotifications(notifications) {
            const notificationsList = document.getElementById('notificationsList');

            if (notifications.length === 0) {
                notificationsList.innerHTML = `
                    <div class="notifications-empty">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You're all caught up! Check back later for new updates.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                const iconClass = getIconClass(notification);
                const typeClass = getTypeClass(notification.type);
                const unreadClass = notification.is_read == 0 ? 'unread' : '';
                const timeAgo = formatTimeAgo(notification.created_at);

                html += `
                    <div class="notification-item ${typeClass} ${unreadClass}" 
                         onclick="markAsRead(${notification.id}, this)"
                         data-id="${notification.id}">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas ${iconClass}"></i>
                            </div>
                            <div class="notification-content">
                                <h4>${escapeHtml(notification.title)}</h4>
                                <p>${escapeHtml(notification.message)}</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>${timeAgo}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            notificationsList.innerHTML = html;
        }

        function getIconClass(notification) {
            if (notification.icon) return notification.icon;

            switch (notification.type) {
                case 'waste': return 'fa-trash-alt';
                case 'request': return 'fa-hourglass-half';
                case 'announcement': return 'fa-bullhorn';
                case 'alert': return 'fa-exclamation-circle';
                case 'success': return 'fa-check-circle';
                default: return 'fa-bell';
            }
        }

        function getTypeClass(type) {
            const typeMap = {
                'waste': 'waste',
                'request': 'request',
                'announcement': 'announcement',
                'alert': 'alert',
                'success': 'success'
            };
            return typeMap[type] || '';
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'Just now';
            if (seconds < 3600) {
                const mins = Math.floor(seconds / 60);
                return mins + (mins === 1 ? ' minute ago' : ' minutes ago');
            }
            if (seconds < 86400) {
                const hours = Math.floor(seconds / 3600);
                return hours + (hours === 1 ? ' hour ago' : ' hours ago');
            }
            if (seconds < 604800) {
                const days = Math.floor(seconds / 86400);
                return days + (days === 1 ? ' day ago' : ' days ago');
            }
            if (seconds < 2592000) {
                const weeks = Math.floor(seconds / 604800);
                return weeks + (weeks === 1 ? ' week ago' : ' weeks ago');
            }
            const months = Math.floor(seconds / 2592000);
            return months + (months === 1 ? ' month ago' : ' months ago');
        }

        function markAsRead(notificationId, element) {
            if (!element.classList.contains('unread')) return;

            fetch('./endpoints/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        element.classList.remove('unread');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function markAllAsRead() {
            fetch('./endpoints/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        showSuccess('All notifications marked as read');
                    } else {
                        showError('Failed to mark notifications as read');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error marking notifications as read');
                });
        }

        function clearAll() {
            Swal.fire({
                icon: 'warning',
                title: 'Clear All Notifications?',
                text: 'Are you sure you want to clear all notifications? This action cannot be undone.',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, clear all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('./endpoints/notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=clear_all'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('notificationsList').innerHTML = `
                        <div class="notifications-empty">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You're all caught up! Check back later for new updates.</p>
                        </div>
                    `;
                                showSuccess('All notifications cleared');
                            } else {
                                showError('Failed to clear notifications');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showError('Error clearing notifications');
                        });
                }
            });
        }

        function toggleNotificationSetting(checkbox, type) {
            const value = checkbox.checked ? 1 : 0;
            const typeName = type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

            fetch('./endpoints/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_preference&preference_type=${type}&value=${value}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess(`${typeName} ${value ? 'enabled' : 'disabled'}`);
                    } else {
                        showError('Failed to update preference');
                        checkbox.checked = !checkbox.checked;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error updating preference');
                    checkbox.checked = !checkbox.checked;
                });
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                confirmButtonColor: '#667eea'
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                confirmButtonColor: '#667eea'
            });
        }
    </script>
</body>

</html>