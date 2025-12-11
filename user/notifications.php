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
<style>
.create-report-btn {
    background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.create-report-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.create-report-btn i {
    font-size: 16px;
}

.report-modal-content {
    text-align: left;
}

.report-form-group {
    margin-bottom: 20px;
}

.report-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
}

.report-form-group input,
.report-form-group select,
.report-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.report-form-group input:focus,
.report-form-group select:focus,
.report-form-group textarea:focus {
    outline: none;
    border-color: #667eea;
}

.report-form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.report-anonymous-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
}

.report-anonymous-check input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.urgency-indicator {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.urgency-low { background: #d1fae5; color: #065f46; }
.urgency-medium { background: #fef3c7; color: #92400e; }
.urgency-high { background: #fed7aa; color: #9a3412; }
.urgency-emergency { background: #fecaca; color: #991b1b; }

.my-report-item {
    background: #f9fafb;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
    cursor: pointer;
}

.my-report-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.my-report-item h4 {
    margin: 0 0 10px 0;
    color: #1f2937;
    font-size: 18px;
}

.my-report-item p {
    margin: 8px 0;
    color: #6b7280;
    font-size: 14px;
}

.report-status {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending { background: #e5e7eb; color: #374151; }
.status-under_review { background: #dbeafe; color: #1e40af; }
.status-in_progress { background: #fef3c7; color: #92400e; }
.status-resolved { background: #d1fae5; color: #065f46; }
.status-closed { background: #e5e7eb; color: #6b7280; }

.report-meta-info {
    display: flex;
    gap: 20px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.report-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 13px;
}

.report-meta-item i {
    color: #9ca3af;
}

.report-response {
    margin-top: 15px;
    padding: 15px;
    background: #f0fdf4;
    border-left: 3px solid #10b981;
    border-radius: 8px;
}

.report-response-title {
    font-weight: 600;
    color: #065f46;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.report-response-text {
    color: #065f46;
    line-height: 1.6;
}

.reports-empty {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.reports-empty i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.reports-empty h3 {
    margin: 0 0 10px 0;
    color: #374151;
}

div:where(.swal2-container) .swal2-input {
    height: auto !important;
}
.swal2-textarea {
    margin: 0 !important;
    padding: 12px 16px !important;
    box-sizing: border-box !important;
}

.report-form-group .swal2-textarea {
    width: 100% !important;
    margin: 0 !important;
}

/* Ensure all form inputs have consistent styling */
.report-form-group input[type="text"],
.report-form-group select,
.report-form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
    box-sizing: border-box;
    margin: 0;
}
</style>
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
                        <button class="notification-filter-btn" data-filter="safety">
                            <i class="fas fa-shield-alt"></i> Safety Reports
                        </button>
                        <button class="notification-filter-btn" data-filter="unread">
                            <i class="fas fa-envelope"></i> Unread
                        </button>
                    </div>

                    <div class="notifications-actions">
                        <button class="create-report-btn" onclick="openCreateReportModal()">
                            <i class="fas fa-exclamation-triangle"></i>
                        </button>
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
            notificationsList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

            // If safety filter is active, load safety reports instead
            if (currentFilter === 'safety') {
                loadSafetyReports();
                return;
            }

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

        function loadSafetyReports() {
            const notificationsList = document.getElementById('notificationsList');
            
            fetch('./endpoints/safety_reports.php?action=get_my_reports')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySafetyReports(data.reports);
                    } else {
                        showError('Failed to load safety reports');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error loading safety reports');
                });
        }

        function displaySafetyReports(reports) {
            const notificationsList = document.getElementById('notificationsList');

            if (reports.length === 0) {
                notificationsList.innerHTML = `
                    <div class="reports-empty">
                        <i class="fas fa-shield-alt"></i>
                        <h3>No Safety Reports</h3>
                        <p>You haven't submitted any safety reports yet.</p>
                        <button class="create-report-btn" onclick="openCreateReportModal()" style="margin: 20px auto;">
                            <i class="fas fa-plus"></i> Submit Your First Report
                        </button>
                    </div>
                `;
                return;
            }

            let html = '';
            reports.forEach(report => {
                const statusClass = `status-${report.status}`;
                const urgencyClass = `urgency-${report.urgency_level}`;
                const dateFormatted = new Date(report.created_at).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const incidentTypeIcon = {
                    'crime': '🚨',
                    'emergency': '🚑',
                    'infrastructure': '🏗️',
                    'environmental': '🌿',
                    'stray_animals': '🐕',
                    'drugs': '💊',
                    'noise': '🔊',
                    'other': '📝'
                };

                html += `
                    <div class="my-report-item" onclick="viewReportDetails(${report.id})">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <h4>${incidentTypeIcon[report.incident_type] || '📝'} ${escapeHtml(report.title)}</h4>
                            <span class="report-status ${statusClass}">
                                ${report.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                        
                        <p style="line-height: 1.6; color: #374151;">${escapeHtml(report.description)}</p>
                        
                        <div class="report-meta-info">
                            <div class="report-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${escapeHtml(report.location)}</span>
                            </div>
                            <div class="report-meta-item">
                                <i class="fas fa-flag"></i>
                                <span class="urgency-indicator ${urgencyClass}">${report.urgency_level.toUpperCase()}</span>
                            </div>
                            <div class="report-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>${dateFormatted}</span>
                            </div>
                        </div>
                        
                        ${report.response_notes ? `
                            <div class="report-response">
                                <div class="report-response-title">
                                    <i class="fas fa-comment-dots"></i>
                                    <span>Official Response</span>
                                </div>
                                <div class="report-response-text">${escapeHtml(report.response_notes)}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            notificationsList.innerHTML = html;
        }

        function viewReportDetails(reportId) {
            // Fetch detailed report information
            fetch(`./endpoints/safety_reports.php?action=get_report_details&report_id=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showReportDetailsModal(data.report);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function showReportDetailsModal(report) {
            const statusClass = `status-${report.status}`;
            const urgencyClass = `urgency-${report.urgency_level}`;
            const dateFormatted = new Date(report.created_at).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const incidentTypeLabel = {
                'crime': '🚨 Crime / Suspicious Activity',
                'emergency': '🚑 Emergency',
                'infrastructure': '🏗️ Infrastructure Hazard',
                'environmental': '🌿 Environmental Concern',
                'stray_animals': '🐕 Stray Animals',
                'drugs': '💊 Drug-related Activity',
                'noise': '🔊 Noise Complaint',
                'other': '📝 Other'
            };

            Swal.fire({
                title: '<strong>Safety Report Details</strong>',
                html: `
                    <div style="text-align: left; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span class="report-status ${statusClass}">${report.status.replace('_', ' ').toUpperCase()}</span>
                            <span class="urgency-indicator ${urgencyClass}">${report.urgency_level.toUpperCase()}</span>
                        </div>
                        
                        <h3 style="margin: 0 0 20px 0; color: #1f2937;">${escapeHtml(report.title)}</h3>
                        
                        <div style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <p style="margin: 0 0 10px 0;"><strong>Incident Type:</strong> ${incidentTypeLabel[report.incident_type]}</p>
                            <p style="margin: 0 0 10px 0;"><strong>Location:</strong> ${escapeHtml(report.location)}</p>
                            <p style="margin: 0;"><strong>Reported:</strong> ${dateFormatted}</p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong style="display: block; margin-bottom: 8px;">Description:</strong>
                            <p style="line-height: 1.6; color: #374151;">${escapeHtml(report.description)}</p>
                        </div>
                        
                        ${report.witness_info ? `
                            <div style="margin-bottom: 15px;">
                                <strong style="display: block; margin-bottom: 8px;">Witness Information:</strong>
                                <p style="line-height: 1.6; color: #374151;">${escapeHtml(report.witness_info)}</p>
                            </div>
                        ` : ''}
                        
                        ${report.response_notes ? `
                            <div class="report-response">
                                <div class="report-response-title">
                                    <i class="fas fa-comment-dots"></i>
                                    <span>Official Response</span>
                                </div>
                                <div class="report-response-text">${escapeHtml(report.response_notes)}</div>
                            </div>
                        ` : ''}
                    </div>
                `,
                width: '650px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    container: 'report-details-modal'
                }
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

        function openCreateReportModal() {
            Swal.fire({
                title: '<strong>Report Safety Issue</strong>',
                html: `
                    <div class="report-modal-content">
                        <div class="report-form-group">
                            <label for="incidentType">Incident Type <span style="color: red;">*</span></label>
                            <select id="incidentType" class="swal2-input" required>
                                <option value="">Select incident type</option>
                                <option value="crime">🚨 Crime / Suspicious Activity</option>
                                <option value="emergency">🚑 Emergency (Fire, Flood, Accident)</option>
                                <option value="infrastructure">🏗️ Infrastructure Hazard</option>
                                <option value="environmental">🌿 Environmental Concern</option>
                                <option value="stray_animals">🐕 Stray Animals</option>
                                <option value="drugs">💊 Drug-related Activity</option>
                                <option value="noise">🔊 Noise Complaint</option>
                                <option value="other">📝 Other</option>
                            </select>
                        </div>

                        <div class="report-form-group">
                            <label for="reportTitle">Title <span style="color: red;">*</span></label>
                            <input type="text" id="reportTitle" class="swal2-input" 
                                placeholder="Brief description of the issue" required>
                        </div>

                        <div class="report-form-group">
                            <label for="urgencyLevel">Urgency Level <span style="color: red;">*</span></label>
                            <select id="urgencyLevel" class="swal2-input" required>
                                <option value="low">🟢 Low - Can wait</option>
                                <option value="medium" selected>🟡 Medium - Should be addressed soon</option>
                                <option value="high">🟠 High - Needs prompt attention</option>
                                <option value="emergency">🔴 Emergency - Immediate action required</option>
                            </select>
                        </div>

                        <div class="report-form-group">
                            <label for="reportLocation">Location <span style="color: red;">*</span></label>
                            <input type="text" id="reportLocation" class="swal2-input" 
                                placeholder="Exact location or nearest landmark" required>
                        </div>

                        <div class="report-form-group">
                            <label for="reportDescription">Description <span style="color: red;">*</span></label>
                            <textarea id="reportDescription" class="swal2-textarea" 
                                    placeholder="Provide detailed information about the incident..." 
                                    rows="4" required></textarea>
                        </div>

                        <div class="report-form-group">
                            <label for="witnessInfo">Witness Information (Optional)</label>
                            <textarea id="witnessInfo" class="swal2-textarea" 
                                    placeholder="Any witness names or contact information..." 
                                    rows="2"></textarea>
                        </div>

                        <div class="report-anonymous-check">
                            <input type="checkbox" id="isAnonymous">
                            <label for="isAnonymous" style="margin: 0;">Submit anonymously</label>
                        </div>

                        <p style="font-size: 12px; color: #6b7280; margin-top: 15px;">
                            <i class="fas fa-info-circle"></i> Your report will be reviewed by barangay officials. 
                            You'll receive updates on the status.
                        </p>
                    </div>
                `,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-paper-plane"></i> Submit Report',
                cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6b7280',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => {
                    const incidentType = document.getElementById('incidentType').value;
                    const title = document.getElementById('reportTitle').value.trim();
                    const urgency = document.getElementById('urgencyLevel').value;
                    const location = document.getElementById('reportLocation').value.trim();
                    const description = document.getElementById('reportDescription').value.trim();
                    const witnessInfo = document.getElementById('witnessInfo').value.trim();
                    const isAnonymous = document.getElementById('isAnonymous').checked;

                    // Validation
                    if (!incidentType) {
                        Swal.showValidationMessage('Please select an incident type');
                        return false;
                    }
                    if (!title || title.length < 5) {
                        Swal.showValidationMessage('Please provide a title (at least 5 characters)');
                        return false;
                    }
                    if (!location) {
                        Swal.showValidationMessage('Please specify the location');
                        return false;
                    }
                    if (!description || description.length < 20) {
                        Swal.showValidationMessage('Please provide a detailed description (at least 20 characters)');
                        return false;
                    }

                    return submitSafetyReport({
                        incidentType,
                        title,
                        urgency,
                        location,
                        description,
                        witnessInfo,
                        isAnonymous
                    });
                }
            });
        }

        function submitSafetyReport(data) {
            return fetch('./endpoints/safety_reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_report',
                    ...data
                })
            })
            .then(response => response.text())
            .then(text => {
                console.log('Response text:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server returned invalid JSON: ' + text.substring(0, 100));
                }
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Report Submitted!',
                        html: `
                            <p>Your safety report has been successfully submitted.</p>
                            <p><strong>Reference Number:</strong> <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px;">#${result.report_id}</code></p>
                            <p style="font-size: 14px; color: #6b7280;">Barangay officials will review and respond to your report.</p>
                        `,
                        confirmButtonColor: '#667eea',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Switch to safety reports filter
                        document.querySelectorAll('.notification-filter-btn').forEach(b => {
                            b.classList.remove('active');
                        });
                        document.querySelector('[data-filter="safety"]').classList.add('active');
                        currentFilter = 'safety';
                        loadNotifications();
                    });
                } else {
                    throw new Error(result.message || 'Failed to submit report');
                }
            })
            .catch(error => {
                console.error('Full error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: error.message,
                    confirmButtonColor: '#667eea'
                });
                throw error;
            });
        }

        function getIconClass(notification) {
            if (notification.icon) return notification.icon;

            switch (notification.type) {
                case 'waste': return 'fa-trash-alt';
                case 'request': return 'fa-hourglass-half';
                case 'announcement': return 'fa-bullhorn';
                case 'alert': return 'fa-exclamation-circle';
                case 'success': return 'fa-check-circle';
                case 'safety': return 'fa-shield-alt';
                default: return 'fa-bell';
            }
        }

        function getTypeClass(type) {
            const typeMap = {
                'waste': 'waste',
                'request': 'request',
                'announcement': 'announcement',
                'alert': 'alert',
                'success': 'success',
                'safety': 'safety'
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
            if (!text) return '';
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
                confirmButtonColor: '#667eea',
                timer: 2000
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