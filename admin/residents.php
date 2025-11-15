<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}

// Pagination settings
$residentsPerPage = 8;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$currentPage = max(1, $currentPage);

// Approval status filter - default to 'approved'
$approvalFilter = isset($_GET['approval']) ? $_GET['approval'] : 'approved';

// Calculate offset
$offset = ($currentPage - 1) * $residentsPerPage;

$residents = [];
$totalResidents = 0;

try {
  // Get total count for pagination with approval filter
  $countQuery = "SELECT COUNT(*) as total FROM user WHERE is_approved = ?";
  $countStmt = $conn->prepare($countQuery);
  $countStmt->bind_param("s", $approvalFilter);
  $countStmt->execute();
  $countResult = $countStmt->get_result();
  $totalResidents = $countResult->fetch_assoc()['total'];
  $countStmt->close();

  // Fetch residents with approval filter
  $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, image, created_at, updated_at,
                                  date_of_birth, gender, civil_status, occupation, house_number, street_name, barangay, status, 
                                  is_approved, selfie_image, gov_id_type, gov_id_image
                           FROM user 
                           WHERE is_approved = ?
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
  $stmt->bind_param("sii", $approvalFilter, $residentsPerPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
  }
  $stmt->close();
} catch (Exception $e) {
  die("Error fetching residents: " . $e->getMessage());
}

// Calculate pagination info
$totalPages = ceil($totalResidents / $residentsPerPage);
$startRecord = $totalResidents > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $residentsPerPage, $totalResidents);

// Get counts for each status
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;

$countStmt = $conn->query("SELECT is_approved, COUNT(*) as count FROM user GROUP BY is_approved");
while ($row = $countStmt->fetch_assoc()) {
  if ($row['is_approved'] === 'pending')
    $pendingCount = $row['count'];
  if ($row['is_approved'] === 'approved')
    $approvedCount = $row['count'];
  if ($row['is_approved'] === 'rejected')
    $rejectedCount = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <?php include '../components/header_links.php'; ?>
  <?php include '../components/admin_side_header.php'; ?>
  <style>
    .filter-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 0;
    }

    .filter-tab {
      padding: 12px 24px;
      background: none;
      border: none;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      color: #6b7280;
      transition: all 0.3s ease;
      position: relative;
    }

    .filter-tab:hover {
      color: #00247c;
      background: #f3f4f6;
    }

    .filter-tab.active {
      color: #00247c;
      border-bottom-color: #00247c;
    }

    .filter-tab .count-badge {
      display: inline-block;
      margin-left: 8px;
      padding: 2px 8px;
      background: #e5e7eb;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .filter-tab.active .count-badge {
      background: #00247c;
      color: white;
    }

    .filter-tab.pending .count-badge {
      background: #fef3c7;
      color: #92400e;
    }

    .filter-tab.rejected .count-badge {
      background: #fee2e2;
      color: #991b1b;
    }

    .approval-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .approval-badge.pending {
      background: #fef3c7;
      color: #92400e;
    }

    .approval-badge.approved {
      background: #d1fae5;
      color: #065f46;
    }

    .approval-badge.rejected {
      background: #fee2e2;
      color: #991b1b;
    }
  </style>
</head>

<body>
  <?php include '../components/sidebar.php'; ?>

  <section class="home-section">
    <div class="table-container">
      <div class="table-header">
        <h2 class="table-title">Residents Management</h2>
        <div class="table-actions">
          <button class="btn btn-primary" onclick="addResident()">
            <i class="fas fa-plus"></i> Add Resident
          </button>
          <button class="btn btn-secondary" onclick="exportData()">
            <i class="fas fa-download"></i> Export
          </button>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <button class="filter-tab <?= $approvalFilter === 'approved' ? 'active' : ''; ?>"
          onclick="filterByApproval('approved')">
          Approved
          <span class="count-badge"><?= $approvedCount; ?></span>
        </button>
        <button class="filter-tab pending <?= $approvalFilter === 'pending' ? 'active' : ''; ?>"
          onclick="filterByApproval('pending')">
          Pending Approval
          <span class="count-badge"><?= $pendingCount; ?></span>
        </button>
        <button class="filter-tab rejected <?= $approvalFilter === 'rejected' ? 'active' : ''; ?>"
          onclick="filterByApproval('rejected')">
          Rejected
          <span class="count-badge"><?= $rejectedCount; ?></span>
        </button>
      </div>

      <div class="search-container">
        <div class="search-box-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-input" placeholder="Search residents..." id="searchInput"
            onkeyup="searchResidents()">
        </div>
      </div>

      <!-- Desktop Table -->
      <div class="table-responsive">
        <table class="residents-table" id="residentsTable">
          <thead>
            <tr>
              <th>Profile</th>
              <th>Name</th>
              <th>Email</th>
              <th>Contact</th>
              <th>Address</th>
              <th>Approval Status</th>
              <th>Status</th>
              <th>Created Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($residents) > 0): ?>
              <?php foreach ($residents as $resident): ?>
                <tr>
                  <td>
                    <img
                      src="<?= !empty($resident['image']) ? '../assets/images/user/' . htmlspecialchars($resident['image']) : '../assets/images/user.png'; ?>"
                      alt="Profile" class="profile-img">
                  </td>
                  <td>
                    <div class="resident-name">
                      <?= htmlspecialchars(trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'])); ?>
                    </div>
                  </td>
                  <td>
                    <div class="resident-email">
                      <?= !empty($resident['email']) ? htmlspecialchars($resident['email']) : "N/A"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="contact-number">
                      <?= !empty($resident['contact_number']) ? htmlspecialchars($resident['contact_number']) : "N/A"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="address">
                      <?php
                      $addressParts = [];
                      if (!empty($resident['house_number']))
                        $addressParts[] = htmlspecialchars($resident['house_number']);
                      if (!empty($resident['street_name']))
                        $addressParts[] = htmlspecialchars($resident['street_name']);
                      if (!empty($resident['barangay']))
                        $addressParts[] = htmlspecialchars($resident['barangay']);
                      echo !empty($addressParts) ? implode(", ", $addressParts) : "N/A";
                      ?>
                    </div>
                  </td>
                  <td>
                    <span class="approval-badge <?= strtolower($resident['is_approved']); ?>">
                      <?= ucfirst($resident['is_approved']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="resident-status <?= strtolower($resident['status'] ?? 'inactive'); ?>">
                      <?= !empty($resident['status']) ? ucfirst($resident['status']) : "Inactive"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="date-created"><?= date("M d, Y", strtotime($resident['created_at'])); ?></div>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-view" onclick="viewResident(<?= $resident['id']; ?>)" title="View">
                        <i class="fas fa-eye"></i>
                      </button>
                      <?php if ($resident['is_approved'] === 'approved'): ?>
                        <button class="btn btn-sm btn-edit" onclick="editResident(<?= $resident['id']; ?>)" title="Edit">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)"
                          title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      <?php elseif ($resident['is_approved'] === 'rejected'): ?>
                        <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)"
                          title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="no-data">
                  <i class="fas fa-users"></i>
                  <p>No <?= $approvalFilter; ?> residents found</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="mobile-cards">
        <?php if (count($residents) > 0): ?>
          <?php foreach ($residents as $resident): ?>
            <div class="resident-card">
              <div class="card-header">
                <img
                  src="<?= !empty($resident['image']) ? '../assets/images/user/' . htmlspecialchars($resident['image']) : '../assets/images/user.png'; ?>"
                  alt="Profile" class="profile-img">
                <div>
                  <div class="resident-name">
                    <?= htmlspecialchars(trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'])); ?>
                  </div>
                  <div class="resident-email">
                    <?= !empty($resident['email']) ? htmlspecialchars($resident['email']) : "N/A"; ?>
                  </div>
                  <span class="approval-badge <?= strtolower($resident['is_approved']); ?>" style="margin-top: 5px;">
                    <?= ucfirst($resident['is_approved']); ?>
                  </span>
                </div>
              </div>

              <div class="card-body">
                <div class="card-field">Contact:</div>
                <div class="card-value">
                  <?= !empty($resident['contact_number']) ? htmlspecialchars($resident['contact_number']) : "N/A"; ?>
                </div>

                <div class="card-field">Address:</div>
                <div class="card-value">
                  <?php
                  $addressParts = [];
                  if (!empty($resident['house_number']))
                    $addressParts[] = htmlspecialchars($resident['house_number']);
                  if (!empty($resident['street_name']))
                    $addressParts[] = htmlspecialchars($resident['street_name']);
                  if (!empty($resident['barangay']))
                    $addressParts[] = htmlspecialchars($resident['barangay']);
                  echo !empty($addressParts) ? implode(", ", $addressParts) : "N/A";
                  ?>
                </div>

                <div class="card-field">Status:</div>
                <div class="card-value">
                  <span class="card-status <?= strtolower($resident['status'] ?? 'inactive'); ?>">
                    <?= !empty($resident['status']) ? ucfirst($resident['status']) : "Inactive"; ?>
                  </span>
                </div>

                <div class="card-field">Created:</div>
                <div class="card-value"><?= date("M d, Y", strtotime($resident['created_at'])); ?></div>
              </div>

              <div class="card-actions">
                <button class="btn btn-sm btn-view" onclick="viewResident(<?= $resident['id']; ?>)" title="View">
                  <i class="fas fa-eye"></i>
                </button>
                <?php if ($resident['is_approved'] === 'approved'): ?>
                  <button class="btn btn-sm btn-edit" onclick="editResident(<?= $resident['id']; ?>)" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                <?php elseif ($resident['is_approved'] === 'rejected'): ?>
                  <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-data">
            <i class="fas fa-users"></i>
            <p>No <?= $approvalFilter; ?> residents found</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalResidents > 0): ?>
        <div class="pagination-container">
          <div class="pagination-info">
            Showing <?= $startRecord; ?>-<?= $endRecord; ?> of <?= $totalResidents; ?> residents
          </div>

          <div class="pagination-controls">
            <div class="pagination">
              <?php if ($currentPage > 1): ?>
                <a href="?page=<?= $currentPage - 1; ?>&approval=<?= $approvalFilter; ?>" title="Previous Page">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php else: ?>
                <span class="disabled">
                  <i class="fas fa-chevron-left"></i>
                </span>
              <?php endif; ?>

              <?php
              $startPage = max(1, $currentPage - 2);
              $endPage = min($totalPages, $currentPage + 2);

              if ($startPage > 1): ?>
                <a href="?page=1&approval=<?= $approvalFilter; ?>">1</a>
                <?php if ($startPage > 2): ?>
                  <span class="disabled">...</span>
                <?php endif; ?>
              <?php endif; ?>

              <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                  <span class="current"><?= $i; ?></span>
                <?php else: ?>
                  <a href="?page=<?= $i; ?>&approval=<?= $approvalFilter; ?>"><?= $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>

              <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                  <span class="disabled">...</span>
                <?php endif; ?>
                <a href="?page=<?= $totalPages; ?>&approval=<?= $approvalFilter; ?>"><?= $totalPages; ?></a>
              <?php endif; ?>

              <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= $currentPage + 1; ?>&approval=<?= $approvalFilter; ?>" title="Next Page">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php else: ?>
                <span class="disabled">
                  <i class="fas fa-chevron-right"></i>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php include '../components/cdn_scripts.php'; ?>
  <script src="./js/residents.js"></script>
  <script>
    function filterByApproval(status) {
      window.location.href = '?approval=' + status + '&page=1';
    }
  </script>
</body>

</html>