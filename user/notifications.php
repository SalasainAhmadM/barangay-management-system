<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
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
                        <button class="notification-filter-btn active" onclick="filterNotifications('all')">
                            <i class="fas fa-inbox"></i> All
                        </button>
                        <button class="notification-filter-btn" onclick="filterNotifications('waste')">
                            <i class="fas fa-trash-alt"></i> Waste Collection
                        </button>
                        <button class="notification-filter-btn" onclick="filterNotifications('request')">
                            <i class="fas fa-file-alt"></i> Requests
                        </button>
                        <button class="notification-filter-btn" onclick="filterNotifications('announcement')">
                            <i class="fas fa-bullhorn"></i> Announcements
                        </button>
                        <button class="notification-filter-btn" onclick="filterNotifications('unread')">
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
                <div class="notifications-list">
                    <!-- Waste Collection Reminder - Unread -->
                    <div class="notification-item waste unread" onclick="markAsRead(this)">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-trash-alt"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Waste Collection Reminder</h4>
                                <p>Reminder: Recyclable waste collection is scheduled for tomorrow, October 1, 2025.
                                    Please prepare your bins before 6:00 AM.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>2 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Update - Unread -->
                    <div class="notification-item success unread" onclick="markAsRead(this)">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Certificate Ready for Pickup</h4>
                                <p>Your Certificate of Residency (Request #BR-2025-001198) has been approved and is
                                    ready for pickup at the barangay hall.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>5 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Announcement - Unread -->
                    <div class="notification-item announcement unread" onclick="markAsRead(this)">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Community Clean-up Drive</h4>
                                <p>Join us this Saturday, October 5, for our monthly community clean-up drive. Gathering
                                    at the barangay hall at 6:00 AM. Your participation matters!</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>1 day ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Update - Read -->
                    <div class="notification-item request">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Request Status Update</h4>
                                <p>Your Barangay Clearance request (Request #BR-2025-001234) is now being processed.
                                    Expected completion: October 5, 2025.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>2 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Waste Collection Reminder - Read -->
                    <div class="notification-item waste">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Biodegradable Waste Collection</h4>
                                <p>Biodegradable waste collection completed successfully in your area. Next collection:
                                    October 2, 2025.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>3 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Announcement - Read -->
                    <div class="notification-item announcement">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Office Closure Notice</h4>
                                <p>The barangay office will be closed on October 31 and November 1 in observance of All
                                    Saints' Day. Regular operations resume November 2.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>5 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missed Collection Report Update - Read -->
                    <div class="notification-item alert">
                        <div class="notification-header">
                            <div class="notification-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="notification-content">
                                <h4>Missed Collection Report Update</h4>
                                <p>Your missed collection report has been resolved. Make-up collection was completed on
                                    September 25, 2025. Thank you for your patience.</p>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <span>1 week ago</span>
                                </div>
                            </div>
                        </div>
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
                    <input type="checkbox" checked onchange="toggleNotificationSetting(this, 'waste')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Request Updates</h4>
                    <p>Receive updates on your certificate and permit requests</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked onchange="toggleNotificationSetting(this, 'request')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Barangay Announcements</h4>
                    <p>Stay informed about community events and updates</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked onchange="toggleNotificationSetting(this, 'announcement')">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>SMS Notifications</h4>
                    <p>Receive notifications via SMS</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked onchange="toggleNotificationSetting(this, 'sms')">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </main>

    <?php include '../components/cdn_scripts.php'; ?>
    <?php include '../components/footer.php'; ?>

    <script>
        function filterNotifications(type) {
            // Update active filter button
            document.querySelectorAll('.notification-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Filter logic would go here
            console.log('Filtering notifications by:', type);
        }

        function markAsRead(element) {
            element.classList.remove('unread');
            // Backend call to mark as read would go here
            console.log('Marked notification as read');
        }

        function markAllAsRead() {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            // Backend call to mark all as read would go here
            console.log('Marked all notifications as read');
        }

        function clearAll() {
            if (confirm('Are you sure you want to clear all notifications?')) {
                document.querySelector('.notifications-list').innerHTML = `
                    <div class="notifications-empty">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You're all caught up! Check back later for new updates.</p>
                    </div>
                `;
                // Backend call to clear all would go here
                console.log('Cleared all notifications');
            }
        }

        function toggleNotificationSetting(checkbox, type) {
            const status = checkbox.checked ? 'enabled' : 'disabled';
            console.log(`${type} notifications ${status}`);
            // Backend call to update settings would go here
        }
    </script>
</body>

</html>