<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Determine active report type
$reportType = isset($_GET['type']) ? $_GET['type'] : 'residents';

// Date filters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch Resident Reports Data
$residentStats = [];
if ($reportType === 'residents') {
    try {
        // Total population
        $stmt = $conn->query("SELECT COUNT(*) as total FROM user WHERE status = 'active'");
        $residentStats['total'] = $stmt->fetch_assoc()['total'];

        // Gender distribution
        $stmt = $conn->query("SELECT gender, COUNT(*) as count FROM user WHERE status = 'active' GROUP BY gender");
        $genderData = [];
        while ($row = $stmt->fetch_assoc()) {
            $genderData[$row['gender'] ?? 'unspecified'] = $row['count'];
        }
        $residentStats['gender'] = $genderData;

        // Civil status distribution
        $stmt = $conn->query("SELECT civil_status, COUNT(*) as count FROM user WHERE status = 'active' GROUP BY civil_status");
        $civilData = [];
        while ($row = $stmt->fetch_assoc()) {
            $civilData[$row['civil_status'] ?? 'unspecified'] = $row['count'];
        }
        $residentStats['civil_status'] = $civilData;

        // Age distribution
        $stmt = $conn->query("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN '0-17'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35 THEN '18-35'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 60 THEN '36-60'
                    ELSE '60+'
                END as age_group,
                COUNT(*) as count
            FROM user 
            WHERE status = 'active' AND date_of_birth IS NOT NULL
            GROUP BY age_group
        ");
        $ageData = [];
        while ($row = $stmt->fetch_assoc()) {
            $ageData[$row['age_group']] = $row['count'];
        }
        $residentStats['age'] = $ageData;

    } catch (Exception $e) {
        $residentStats = ['total' => 0, 'gender' => [], 'civil_status' => [], 'age' => []];
    }
}

// Fetch Request Reports Data
$requestStats = [];
if ($reportType === 'requests') {
    try {
        // Overall statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM document_requests
            WHERE submitted_date BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $requestStats['overall'] = $stmt->get_result()->fetch_assoc();

        // Request by type
        $stmt = $conn->prepare("
            SELECT dt.name, dt.type, COUNT(*) as count
            FROM document_requests dr
            JOIN document_types dt ON dr.document_type_id = dt.id
            WHERE dr.submitted_date BETWEEN ? AND ?
            GROUP BY dt.id
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $requestStats['by_type'] = [];
        while ($row = $result->fetch_assoc()) {
            $requestStats['by_type'][] = $row;
        }

        // Monthly trend
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(submitted_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM document_requests
            WHERE submitted_date BETWEEN ? AND ?
            GROUP BY month
            ORDER BY month
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $requestStats['trend'] = [];
        while ($row = $result->fetch_assoc()) {
            $requestStats['trend'][] = $row;
        }

    } catch (Exception $e) {
        $requestStats = ['overall' => [], 'by_type' => [], 'trend' => []];
    }
}

// Fetch Waste Reports Data
$wasteStats = [];
if ($reportType === 'waste') {
    try {
        // Overall statistics
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM missed_collections
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $wasteStats['overall'] = $stmt->get_result()->fetch_assoc();

        // By waste type
        $stmt = $conn->prepare("
            SELECT waste_type, COUNT(*) as count
            FROM missed_collections
            WHERE created_at BETWEEN ? AND ?
            GROUP BY waste_type
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $wasteStats['by_type'] = [];
        while ($row = $result->fetch_assoc()) {
            $wasteStats['by_type'][] = $row;
        }

        // Monthly trend
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM missed_collections
            WHERE created_at BETWEEN ? AND ?
            GROUP BY month
            ORDER BY month
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $wasteStats['trend'] = [];
        while ($row = $result->fetch_assoc()) {
            $wasteStats['trend'][] = $row;
        }

    } catch (Exception $e) {
        $wasteStats = ['overall' => [], 'by_type' => [], 'trend' => []];
    }
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
                <h2 class="table-title">Reports & Analytics</h2>
                <div class="table-actions">
                    <!-- <button class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                    <button class="btn btn-secondary" onclick="printReport()">
                        <i class="fas fa-print"></i> Print
                    </button> -->
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn <?= $reportType === 'residents' ? 'active' : ''; ?>"
                    onclick="switchReport('residents')">
                    <i class="fas fa-users"></i> Resident Reports
                </button>
                <button class="tab-btn <?= $reportType === 'requests' ? 'active' : ''; ?>"
                    onclick="switchReport('requests')">
                    <i class="fas fa-file-alt"></i> Request Reports
                </button>
                <button class="tab-btn <?= $reportType === 'waste' ? 'active' : ''; ?>" onclick="switchReport('waste')">
                    <i class="fas fa-trash-alt"></i> Waste Reports
                </button>
            </div>

            <!-- Filters -->
            <div class="report-filters">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($reportType); ?>">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="filter-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Resident Reports -->
            <?php if ($reportType === 'residents'): ?>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?= $residentStats['total']; ?></div>
                        <div class="stat-label">Total Residents</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-icon"><i class="fas fa-male"></i></div>
                        <div class="stat-value"><?= $residentStats['gender']['male'] ?? 0; ?></div>
                        <div class="stat-label">Male</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon"><i class="fas fa-female"></i></div>
                        <div class="stat-value"><?= $residentStats['gender']['female'] ?? 0; ?></div>
                        <div class="stat-label">Female</div>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Gender Distribution</h3>
                        <div class="chart-container">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Civil Status</h3>
                        <div class="chart-container">
                            <canvas id="civilChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Age Distribution</h3>
                        <div class="chart-container">
                            <canvas id="ageChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Request Reports -->
            <?php if ($reportType === 'requests'): ?>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-value"><?= $requestStats['overall']['total'] ?? 0; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?= $requestStats['overall']['approved'] ?? 0; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?= $requestStats['overall']['pending'] ?? 0; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value"><?= $requestStats['overall']['rejected'] ?? 0; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Request Status Distribution</h3>
                        <div class="chart-container">
                            <canvas id="requestStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Top Document Types</h3>
                        <div class="chart-container">
                            <canvas id="documentTypeChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h3>Monthly Request Trend</h3>
                        <div class="chart-container">
                            <canvas id="requestTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Waste Reports -->
            <?php if ($reportType === 'waste'): ?>
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-value"><?= $wasteStats['overall']['total'] ?? 0; ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value"><?= $wasteStats['overall']['resolved'] ?? 0; ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?= $wasteStats['overall']['pending'] ?? 0; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value"><?= $wasteStats['overall']['rejected'] ?? 0; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Report Status</h3>
                        <div class="chart-container">
                            <canvas id="wasteStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Reports by Waste Type</h3>
                        <div class="chart-container">
                            <canvas id="wasteTypeChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h3>Monthly Report Trend</h3>
                        <div class="chart-container">
                            <canvas id="wasteTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function switchReport(type) {
            window.location.href = '?type=' + type;
        }

        function exportReport() {
        }

        // Chart configurations
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#666';

        <?php if ($reportType === 'residents'): ?>
            // Gender Chart
            new Chart(document.getElementById('genderChart'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_map('ucfirst', array_keys($residentStats['gender']))); ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($residentStats['gender'])); ?>,
                        backgroundColor: ['#667eea', '#f093fb', '#4facfe']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Civil Status Chart
            new Chart(document.getElementById('civilChart'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_map('ucfirst', array_keys($residentStats['civil_status']))); ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($residentStats['civil_status'])); ?>,
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Age Chart
            new Chart(document.getElementById('ageChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_keys($residentStats['age'])); ?>,
                    datasets: [{
                        label: 'Residents',
                        data: <?= json_encode(array_values($residentStats['age'])); ?>,
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        <?php endif; ?>

        <?php if ($reportType === 'requests'): ?>
            // Request Status Chart
            new Chart(document.getElementById('requestStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?= $requestStats['overall']['approved'] ?? 0; ?>,
                            <?= $requestStats['overall']['pending'] ?? 0; ?>,
                            <?= $requestStats['overall']['rejected'] ?? 0; ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Document Type Chart
            new Chart(document.getElementById('documentTypeChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($requestStats['by_type'], 'name')); ?>,
                    datasets: [{
                        label: 'Requests',
                        data: <?= json_encode(array_column($requestStats['by_type'], 'count')); ?>,
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });

            // Request Trend Chart
            new Chart(document.getElementById('requestTrendChart'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($requestStats['trend'], 'month')); ?>,
                    datasets: [{
                        label: 'Requests',
                        data: <?= json_encode(array_column($requestStats['trend'], 'count')); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        <?php endif; ?>

        <?php if ($reportType === 'waste'): ?>
            // Waste Status Chart
            new Chart(document.getElementById('wasteStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Resolved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?= $wasteStats['overall']['resolved'] ?? 0; ?>,
                            <?= $wasteStats['overall']['pending'] ?? 0; ?>,
                            <?= $wasteStats['overall']['rejected'] ?? 0; ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            // Waste Type Chart
            new Chart(document.getElementById('wasteTypeChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($wasteStats['by_type'], 'waste_type')); ?>,
                    datasets: [{
                        label: 'Reports',
                        data: <?= json_encode(array_column($wasteStats['by_type'], 'count')); ?>,
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            // Waste Trend Chart
            new Chart(document.getElementById('wasteTrendChart'), {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($wasteStats['trend'], 'month')); ?>,
                    datasets: [{
                        label: 'Reports',
                        data: <?= json_encode(array_column($wasteStats['trend'], 'count')); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>