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
$currentPage = max(1, $currentPage); // Ensure page is at least 1

// Calculate offset
$offset = ($currentPage - 1) * $residentsPerPage;

$residents = [];
$totalResidents = 0;

try {
  // Get total count for pagination
  $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM user");
  $countStmt->execute();
  $countResult = $countStmt->get_result();
  $totalResidents = $countResult->fetch_assoc()['total'];

  $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, image, created_at, updated_at,
                                  date_of_birth, gender, civil_status, occupation, house_number, street_name, barangay, status
                           FROM user 
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
  $stmt->bind_param("ii", $residentsPerPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
  }
} catch (Exception $e) {
  die("Error fetching residents: " . $e->getMessage());
}


// Calculate pagination info
$totalPages = ceil($totalResidents / $residentsPerPage);
$startRecord = $totalResidents > 0 ? $offset + 1 : 0;
$endRecord = min($offset + $residentsPerPage, $totalResidents);
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
    <!-- <div class="text">Residents</div> -->

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
              <!-- <th>Date of Birth</th>
              <th>Gender</th>
              <th>Civil Status</th>
              <th>Occupation</th> -->
              <th>Address</th>
              <th>Status</th>
              <th>Created Date</th>
              <th>Updated Date</th>
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
                  <td style="display: none;">
                    <div class="date-of-birth">
                      <?= !empty($resident['date_of_birth']) ? date("M d, Y", strtotime($resident['date_of_birth'])) : "N/A"; ?>
                    </div>
                  </td>
                  <td style="display: none;">
                    <div class="gender"><?= !empty($resident['gender']) ? ucfirst($resident['gender']) : "N/A"; ?></div>
                  </td>
                  <td style="display: none;">
                    <div class="civil-status">
                      <?= !empty($resident['civil_status']) ? ucfirst($resident['civil_status']) : "N/A"; ?>
                    </div>
                  </td>
                  <td style="display: none;">
                    <div class="occupation">
                      <?= !empty($resident['occupation']) ? htmlspecialchars($resident['occupation']) : "N/A"; ?>
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
                    <div class="resident-status <?= strtolower($resident['status'] ?? 'inactive'); ?>">
                      <?= !empty($resident['status']) ? ucfirst($resident['status']) : "Inactive"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="date-created"><?= date("M d, Y", strtotime($resident['created_at'])); ?></div>
                  </td>
                  <td>
                    <div class="date-created"><?= date("M d, Y", strtotime($resident['updated_at'])); ?></div>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-view" onclick="viewResident(<?= $resident['id']; ?>)" title="View">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-edit" onclick="editResident(<?= $resident['id']; ?>)" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)"
                        title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="13" class="no-data">
                  <i class="fas fa-users"></i>
                  <p>No residents found</p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <!-- Enhanced Mobile Cards Section -->
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
                </div>
              </div>

              <div class="card-body">
                <div class="card-field">Contact:</div>
                <div class="card-value">
                  <?= !empty($resident['contact_number']) ? htmlspecialchars($resident['contact_number']) : "N/A"; ?>
                </div>

                <div class="card-field">Date of Birth:</div>
                <div class="card-value">
                  <?= !empty($resident['date_of_birth']) ? date("M d, Y", strtotime($resident['date_of_birth'])) : "N/A"; ?>
                </div>

                <div class="card-field">Gender:</div>
                <div class="card-value">
                  <?= !empty($resident['gender']) ? ucfirst($resident['gender']) : "N/A"; ?>
                </div>

                <div class="card-field">Civil Status:</div>
                <div class="card-value">
                  <?= !empty($resident['civil_status']) ? ucfirst($resident['civil_status']) : "N/A"; ?>
                </div>

                <div class="card-field">Occupation:</div>
                <div class="card-value">
                  <?= !empty($resident['occupation']) ? htmlspecialchars($resident['occupation']) : "N/A"; ?>
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

                <div class="card-field">Updated:</div>
                <div class="card-value"><?= date("M d, Y", strtotime($resident['updated_at'])); ?></div>
              </div>

              <div class="card-actions">
                <button class="btn btn-sm btn-view" onclick="viewResident(<?= $resident['id']; ?>)" title="View">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-edit" onclick="editResident(<?= $resident['id']; ?>)" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-delete" onclick="deleteResident(<?= $resident['id']; ?>)" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-data">
            <i class="fas fa-users"></i>
            <p>No residents found</p>
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
              <!-- Previous Button -->
              <?php if ($currentPage > 1): ?>
                <a href="?page=<?= $currentPage - 1; ?>" title="Previous Page">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php else: ?>
                <span class="disabled">
                  <i class="fas fa-chevron-left"></i>
                </span>
              <?php endif; ?>

              <!-- Page Numbers -->
              <?php
              $startPage = max(1, $currentPage - 2);
              $endPage = min($totalPages, $currentPage + 2);

              // Show first page if not in range
              if ($startPage > 1): ?>
                <a href="?page=1">1</a>
                <?php if ($startPage > 2): ?>
                  <span class="disabled">...</span>
                <?php endif; ?>
              <?php endif; ?>

              <!-- Page range -->
              <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                  <span class="current"><?= $i; ?></span>
                <?php else: ?>
                  <a href="?page=<?= $i; ?>"><?= $i; ?></a>
                <?php endif; ?>
              <?php endfor; ?>

              <!-- Show last page if not in range -->
              <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                  <span class="disabled">...</span>
                <?php endif; ?>
                <a href="?page=<?= $totalPages; ?>"><?= $totalPages; ?></a>
              <?php endif; ?>

              <!-- Next Button -->
              <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= $currentPage + 1; ?>" title="Next Page">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php else: ?>
                <span class="disabled">
                  <i class="fas fa-chevron-right"></i>
                </span>
              <?php endif; ?>
            </div>

            <!-- Quick Jump -->
            <?php if ($totalPages > 5): ?>
              <div class="pagination-jump">
                <label for="pageJump">Go to:</label>
                <select id="pageJump" onchange="jumpToPage(this.value)">
                  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <option value="<?= $i; ?>" <?= $i == $currentPage ? 'selected' : ''; ?>><?= $i; ?></option>
                  <?php endfor; ?>
                </select>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php include '../components/cdn_scripts.php'; ?>
  <script src="./js/residents.js"></script>

</body>

</html>