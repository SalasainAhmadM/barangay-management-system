<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch waste schedules
$schedules_query = "SELECT * FROM waste_schedules WHERE is_active = 1 ORDER BY schedule_id";
$schedules_result = $conn->query($schedules_query);

// Fetch user's reports
$reports_query = "SELECT * FROM community_reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$reports_stmt = $conn->prepare($reports_query);
$reports_stmt->bind_param("i", $user_id);
$reports_stmt->execute();
$reports_result = $reports_stmt->get_result();

// Function to calculate next collection date
function getNextCollectionDate($collection_days)
{
    $today = new DateTime();
    $daysOfWeek = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];

    if (strpos($collection_days, 'First') !== false) {
        $month = $today->format('n');
        $year = $today->format('Y');
        $firstDay = new DateTime("$year-$month-01");
        $dayOfWeek = $firstDay->format('w');
        $daysUntilSaturday = (6 - $dayOfWeek + 7) % 7;
        $firstSaturday = clone $firstDay;
        $firstSaturday->modify("+$daysUntilSaturday days");

        if ($today > $firstSaturday) {
            $firstDay->modify('+1 month');
            $month = $firstDay->format('n');
            $year = $firstDay->format('Y');
            $firstDay = new DateTime("$year-$month-01");
            $dayOfWeek = $firstDay->format('w');
            $daysUntilSaturday = (6 - $dayOfWeek + 7) % 7;
            $firstSaturday = clone $firstDay;
            $firstSaturday->modify("+$daysUntilSaturday days");
        }

        return $firstSaturday;
    }

    $days = array_map('trim', explode(',', $collection_days));
    $nextDate = null;
    $minDiff = PHP_INT_MAX;

    foreach ($days as $day) {
        if (isset($daysOfWeek[$day])) {
            $targetDay = $daysOfWeek[$day];
            $currentDay = (int) $today->format('w');
            $diff = ($targetDay - $currentDay + 7) % 7;

            if ($diff === 0) {
                $diff = 7;
            }

            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nextDate = clone $today;
                $nextDate->modify("+$diff days");
            }
        }
    }

    return $nextDate;
}

function getDaysUntil($nextDate)
{
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $nextDate->setTime(0, 0, 0);
    $diff = $today->diff($nextDate);
    $days = (int) $diff->format('%a');

    if ($days === 0) {
        return 'Today';
    } elseif ($days === 1) {
        return 'Tomorrow';
    } else {
        return "In $days days";
    }
}

function getStatusBadgeClass($status)
{
    $classes = [
        'pending' => 'pending',
        'investigating' => 'investigating',
        'resolved' => 'resolved',
        'rejected' => 'rejected'
    ];
    return $classes[$status] ?? 'pending';
}

function getStatusText($status)
{
    $texts = [
        'pending' => 'Pending',
        'investigating' => 'Under Investigation',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected'
    ];
    return $texts[$status] ?? 'Unknown';
}

function getReportTypeIcon($type)
{
    $icons = [
        'Missed Collection' => 'fa-trash-alt',
        'Road/Infrastructure' => 'fa-road',
        'Street Lighting' => 'fa-lightbulb',
        'Public Safety' => 'fa-shield-alt',
        'Noise Complaint' => 'fa-volume-up',
        'Illegal Dumping' => 'fa-dumpster',
        'Other' => 'fa-flag'
    ];
    return $icons[$type] ?? 'fa-flag';
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
            <h1>Community Reports</h1>
            <p>Report issues and view waste collection schedules</p>
        </div>

        <!-- Waste Collection Schedule Section (Collapsible) -->
        <div class="section-card">
            <div class="section-card-header" style="cursor: pointer;" onclick="toggleSection('schedule-section')">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-calendar-alt"></i>
                    <h2>Waste Collection Schedule</h2>
                </div>
                <i class="fas fa-chevron-down" id="schedule-chevron"></i>
            </div>
            <div class="section-card-body" id="schedule-section">
                <div class="info-banner">
                    <i class="fas fa-info-circle"></i>
                    <div class="info-banner-content">
                        <h4>Collection Reminder</h4>
                        <p>Please prepare your waste bins before 6:00 AM on collection days. Separate waste properly
                            according to type.</p>
                    </div>
                </div>

                <?php while ($schedule = $schedules_result->fetch_assoc()):
                    $nextDate = getNextCollectionDate($schedule['collection_days']);
                    $daysUntil = getDaysUntil($nextDate);
                    ?>
                    <div class="schedule-item">
                        <div class="schedule-icon">
                            <i class="fas <?php echo htmlspecialchars($schedule['icon']); ?>"></i>
                        </div>
                        <div class="schedule-info">
                            <h4><?php echo htmlspecialchars($schedule['waste_type']); ?></h4>
                            <p><?php echo htmlspecialchars($schedule['collection_days']); ?> - Next:
                                <?php echo $nextDate->format('F j, Y'); ?>
                            </p>
                        </div>
                        <span class="schedule-badge"><?php echo $daysUntil; ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- File a Report Section -->
        <div class="section-card">
            <div class="section-card-header">
                <i class="fas fa-file-alt"></i>
                <h2>File a Report</h2>
            </div>
            <div class="section-card-body">
                <div class="report-form-card">
                    <form id="communityReportForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="report-type">Report Type *</label>
                            <select id="report-type" name="report-type" required>
                                <option value="">Select report type...</option>
                                <option value="Missed Collection">Missed Waste Collection</option>
                                <option value="Road/Infrastructure">Road/Infrastructure Issue</option>
                                <option value="Street Lighting">Street Lighting Problem</option>
                                <option value="Public Safety">Public Safety Concern</option>
                                <option value="Noise Complaint">Noise Complaint</option>
                                <option value="Illegal Dumping">Illegal Dumping</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Conditional fields for Missed Collection -->
                        <div id="missed-collection-fields" style="display: none;">
                            <div class="form-group">
                                <label for="collection-date">Missed Collection Date *</label>
                                <input type="date" id="collection-date" name="collection-date"
                                    max="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label>Waste Type *</label>
                                <div class="waste-type-tags">
                                    <div class="waste-tag biodegradable"
                                        onclick="toggleWasteType(this, 'Biodegradable Waste')">
                                        <i class="fas fa-leaf"></i> Biodegradable
                                    </div>
                                    <div class="waste-tag non-biodegradable"
                                        onclick="toggleWasteType(this, 'Non-Biodegradable Waste')">
                                        <i class="fas fa-trash"></i> Non-Biodegradable
                                    </div>
                                    <div class="waste-tag recyclable" onclick="toggleWasteType(this, 'Recyclable Waste')">
                                        <i class="fas fa-recycle"></i> Recyclable
                                    </div>
                                    <div class="waste-tag hazardous"
                                        onclick="toggleWasteType(this, 'Special/Hazardous Waste')">
                                        <i class="fas fa-hospital"></i> Special/Hazardous
                                    </div>
                                </div>
                                <input type="hidden" id="waste-type" name="waste-type">
                            </div>
                        </div>

                        <!-- Common fields for all report types -->
                        <div class="form-group">
                            <label for="incident-date">Incident Date *</label>
                            <input type="date" id="incident-date" name="incident-date"
                                max="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="location">Location *</label>
                            <input type="text" id="location" name="location" placeholder="e.g., Block 5, Lot 10, Main Street"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4"
                                placeholder="Please provide details about the issue..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Upload Photo (Optional)</label>
                            <div class="file-upload-area" onclick="document.getElementById('photo-upload').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <span>PNG, JPG up to 5MB</span>
                            </div>
                            <input type="file" id="photo-upload" name="photo" accept="image/*" style="display: none;">
                        </div>

                        <button type="submit" class="submit-report-btn">
                            <i class="fas fa-paper-plane"></i> Submit Report
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reports History Section -->
        <div class="section-card">
            <div class="section-card-header">
                <i class="fas fa-history"></i>
                <h2>My Reports</h2>
            </div>
            <div class="section-card-body">
                <div class="reports-history">
                    <?php if ($reports_result->num_rows > 0): ?>
                        <?php while ($report = $reports_result->fetch_assoc()): ?>
                            <div class="report-item <?php echo getStatusBadgeClass($report['status']); ?>">
                                <div class="report-item-content">
                                    <div class="report-item-header">
                                        <div>
                                            <h4>
                                                <i class="fas <?php echo getReportTypeIcon($report['report_type']); ?>"></i>
                                                <?php echo htmlspecialchars($report['report_type']); ?>
                                            </h4>
                                            <span class="report-date">Reported on
                                                <?php echo date('M j, Y', strtotime($report['created_at'])); ?></span>
                                        </div>
                                        <span class="report-status-badge <?php echo getStatusBadgeClass($report['status']); ?>">
                                            <?php echo getStatusText($report['status']); ?>
                                        </span>
                                    </div>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($report['location']); ?></p>
                                    <p><strong>Incident Date:</strong>
                                        <?php echo date('F j, Y', strtotime($report['incident_date'])); ?></p>
                                    <?php if ($report['report_type'] === 'Missed Collection' && !empty($report['waste_type'])): ?>
                                        <p><strong>Waste Type:</strong> <?php echo htmlspecialchars($report['waste_type']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($report['description'])): ?>
                                        <p><?php echo htmlspecialchars($report['description']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($report['status'] === 'resolved' && !empty($report['resolution_notes'])): ?>
                                        <div style="background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;">
                                            <strong>Resolution:</strong> <?php echo htmlspecialchars($report['resolution_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($report['photo_path'])): ?>
                                        <div class="report-photo">
                                            <img src="../assets/community_reports/<?php echo htmlspecialchars($report['photo_path']); ?>"
                                                alt="Report photo" style="max-width: 200px; margin-top: 10px; border-radius: 8px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reports">
                            <i class="fas fa-inbox"></i>
                            <p>No reports yet. Submit a report above if you need to report an issue.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let selectedWasteType = null;

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const chevron = document.getElementById('schedule-chevron');
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            } else {
                section.style.display = 'none';
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        }

        function toggleWasteType(element, type) {
            document.querySelectorAll('.waste-tag').forEach(tag => {
                tag.classList.remove('active');
            });

            element.classList.add('active');
            selectedWasteType = type;
            document.getElementById('waste-type').value = type;
        }

        // Handle report type change
        document.getElementById('report-type').addEventListener('change', function() {
            const missedCollectionFields = document.getElementById('missed-collection-fields');
            const collectionDateInput = document.getElementById('collection-date');
            const wasteTypeInput = document.getElementById('waste-type');
            
            if (this.value === 'Missed Collection') {
                missedCollectionFields.style.display = 'block';
                collectionDateInput.required = true;
                wasteTypeInput.required = true;
            } else {
                missedCollectionFields.style.display = 'none';
                collectionDateInput.required = false;
                wasteTypeInput.required = false;
                selectedWasteType = null;
                
                // Clear waste type selection
                document.querySelectorAll('.waste-tag').forEach(tag => {
                    tag.classList.remove('active');
                });
                wasteTypeInput.value = '';
            }
        });

        document.getElementById('photo-upload').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const uploadArea = document.querySelector('.file-upload-area p');
                uploadArea.textContent = `Selected: ${fileName}`;
            }
        });

        document.getElementById('communityReportForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const reportType = document.getElementById('report-type').value;
            
            // Validate waste type for missed collection
            if (reportType === 'Missed Collection' && !selectedWasteType) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Waste Type Required',
                    text: 'Please select a waste type for missed collection reports'
                });
                return;
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            fetch('./endpoints/submit_community_report.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Submitted!',
                            text: data.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: data.message
                        });
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    </script>

    <?php include '../components/cdn_scripts.php'; ?>
    <?php include '../components/footer.php'; ?>

</body>

</html>
<?php
$conn->close();
?>