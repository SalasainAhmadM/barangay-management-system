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
            <h1>Waste Management</h1>
            <p>View collection schedules and report missed collections</p>
        </div>

        <!-- Waste Collection Schedule Section -->
        <div class="section-card">
            <div class="section-card-header">
                <i class="fas fa-calendar-alt"></i>
                <h2>Waste Collection Schedule</h2>
            </div>
            <div class="section-card-body">
                <div class="info-banner">
                    <i class="fas fa-info-circle"></i>
                    <div class="info-banner-content">
                        <h4>Collection Reminder</h4>
                        <p>Please prepare your waste bins before 6:00 AM on collection days. Separate waste properly
                            according to type.</p>
                    </div>
                </div>

                <div class="schedule-item">
                    <div class="schedule-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <div class="schedule-info">
                        <h4>Recyclable Waste</h4>
                        <p>Every Friday - Next: October 4, 2025</p>
                    </div>
                    <span class="schedule-badge">In 4 days</span>
                </div>

                <div class="schedule-item">
                    <div class="schedule-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="schedule-info">
                        <h4>Biodegradable Waste</h4>
                        <p>Monday, Wednesday, Friday - Next: October 2, 2025</p>
                    </div>
                    <span class="schedule-badge">In 2 days</span>
                </div>

                <div class="schedule-item">
                    <div class="schedule-icon">
                        <i class="fas fa-trash"></i>
                    </div>
                    <div class="schedule-info">
                        <h4>Non-Biodegradable Waste</h4>
                        <p>Tuesday, Thursday, Saturday - Next: October 1, 2025</p>
                    </div>
                    <span class="schedule-badge">Tomorrow</span>
                </div>

                <div class="schedule-item">
                    <div class="schedule-icon">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="schedule-info">
                        <h4>Special/Hazardous Waste</h4>
                        <p>First Saturday of the month - Next: October 5, 2025</p>
                    </div>
                    <span class="schedule-badge">In 5 days</span>
                </div>
            </div>
        </div>

        <!-- Report Missed Collection Section -->
        <div class="section-card">
            <div class="section-card-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Report Missed Collection</h2>
            </div>
            <div class="section-card-body">
                <div class="report-form-card">
                    <form id="missedCollectionForm">
                        <div class="form-group">
                            <label for="collection-date">Missed Collection Date *</label>
                            <input type="date" id="collection-date" name="collection-date" required>
                        </div>

                        <div class="form-group">
                            <label>Waste Type *</label>
                            <div class="waste-type-tags">
                                <div class="waste-tag biodegradable" onclick="toggleWasteType(this, 'biodegradable')">
                                    <i class="fas fa-leaf"></i> Biodegradable
                                </div>
                                <div class="waste-tag non-biodegradable"
                                    onclick="toggleWasteType(this, 'non-biodegradable')">
                                    <i class="fas fa-trash"></i> Non-Biodegradable
                                </div>
                                <div class="waste-tag recyclable" onclick="toggleWasteType(this, 'recyclable')">
                                    <i class="fas fa-recycle"></i> Recyclable
                                </div>
                            </div>
                            <input type="hidden" id="waste-type" name="waste-type" required>
                        </div>

                        <div class="form-group">
                            <label for="location">Collection Location *</label>
                            <input type="text" id="location" name="location" placeholder="e.g., Block 5, Lot 10"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"
                                placeholder="Please provide additional details about the missed collection..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Upload Photo (Optional)</label>
                            <div class="file-upload-area" onclick="document.getElementById('photo-upload').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <span>PNG, JPG up to 5MB</span>
                            </div>
                            <input type="file" id="photo-upload" name="photo" accept="image/*">
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
                    <!-- Report Item - Pending -->
                    <div class="report-item pending">
                        <div class="report-item-content">
                            <div class="report-item-header">
                                <div>
                                    <h4>Missed Biodegradable Collection</h4>
                                    <span class="report-date">Reported on Sept 28, 2025</span>
                                </div>
                                <span class="report-status-badge pending">Pending</span>
                            </div>
                            <p><strong>Location:</strong> Block 5, Lot 10</p>
                            <p><strong>Date:</strong> September 27, 2025</p>
                            <p>Collection truck did not pass by our area during scheduled time.</p>
                        </div>
                    </div>

                    <!-- Report Item - Investigating -->
                    <div class="report-item investigating">
                        <div class="report-item-content">
                            <div class="report-item-header">
                                <div>
                                    <h4>Missed Recyclable Collection</h4>
                                    <span class="report-date">Reported on Sept 20, 2025</span>
                                </div>
                                <span class="report-status-badge investigating">Under Investigation</span>
                            </div>
                            <p><strong>Location:</strong> Block 5, Lot 10</p>
                            <p><strong>Date:</strong> September 19, 2025</p>
                            <p>Recyclable waste was not collected as scheduled.</p>
                        </div>
                    </div>

                    <!-- Report Item - Resolved -->
                    <div class="report-item resolved">
                        <div class="report-item-content">
                            <div class="report-item-header">
                                <div>
                                    <h4>Missed Non-Biodegradable Collection</h4>
                                    <span class="report-date">Reported on Sept 10, 2025</span>
                                </div>
                                <span class="report-status-badge resolved">Resolved</span>
                            </div>
                            <p><strong>Location:</strong> Block 5, Lot 10</p>
                            <p><strong>Date:</strong> September 9, 2025</p>
                            <p><strong>Resolution:</strong> Make-up collection scheduled and completed on September 12,
                                2025.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        let selectedWasteType = null;

        function toggleWasteType(element, type) {
            // Remove active class from all waste tags
            document.querySelectorAll('.waste-tag').forEach(tag => {
                tag.classList.remove('active');
            });

            // Add active class to selected tag
            element.classList.add('active');
            selectedWasteType = type;

            // Update hidden input
            document.getElementById('waste-type').value = type;
        }

        // Handle file upload display
        document.getElementById('photo-upload').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const uploadArea = document.querySelector('.file-upload-area p');
                uploadArea.textContent = `Selected: ${fileName}`;
            }
        });

        // Handle form submission
        document.getElementById('missedCollectionForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate waste type selection
            if (!selectedWasteType) {
                alert('Please select a waste type');
                return;
            }

            // Here you would send the form data to your PHP backend
            const formData = new FormData(this);

            console.log('Form submitted with data:', {
                date: formData.get('collection-date'),
                wasteType: formData.get('waste-type'),
                location: formData.get('location'),
                description: formData.get('description')
            });

            // Show success message (you can use SweetAlert2 here)
            alert('Report submitted successfully!');
            this.reset();
            document.querySelectorAll('.waste-tag').forEach(tag => tag.classList.remove('active'));
            selectedWasteType = null;
        });
    </script>

    <?php include '../components/cdn_scripts.php'; ?>
    <?php include '../components/footer.php'; ?>

</body>

</html>