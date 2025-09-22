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
                  <!-- <td>
                    <div class="date-of-birth">
                      <?= !empty($resident['date_of_birth']) ? date("M d, Y", strtotime($resident['date_of_birth'])) : "N/A"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="gender"><?= !empty($resident['gender']) ? ucfirst($resident['gender']) : "N/A"; ?></div>
                  </td>
                  <td>
                    <div class="civil-status">
                      <?= !empty($resident['civil_status']) ? ucfirst($resident['civil_status']) : "N/A"; ?>
                    </div>
                  </td>
                  <td>
                    <div class="occupation">
                      <?= !empty($resident['occupation']) ? htmlspecialchars($resident['occupation']) : "N/A"; ?>
                    </div>
                  </td> -->
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

  <script>
    function searchResidents() {
      const filter = document.getElementById('searchInput').value.toLowerCase();
      let found = false;

      // Search in table rows
      const rows = document.querySelectorAll("#residentsTable tbody tr");
      rows.forEach(row => {
        if (row.querySelector('.no-data')) return; // Skip "no data" row

        // Get all searchable text content from the row
        const name = row.querySelector(".resident-name")?.textContent.toLowerCase() || "";
        const email = row.querySelector(".resident-email")?.textContent.toLowerCase() || "";
        const contact = row.querySelector(".contact-number")?.textContent.toLowerCase() || "";
        const gender = row.cells[5]?.textContent.toLowerCase() || "";
        const civilStatus = row.cells[6]?.textContent.toLowerCase() || "";
        const occupation = row.cells[7]?.textContent.toLowerCase() || "";
        const address = row.cells[8]?.textContent.toLowerCase() || "";
        const status = row.cells[9]?.textContent.toLowerCase() || "";

        // Check if any field matches the search filter
        const matches = !filter ||
          name.includes(filter) ||
          email.includes(filter) ||
          contact.includes(filter) ||
          gender.includes(filter) ||
          civilStatus.includes(filter) ||
          occupation.includes(filter) ||
          address.includes(filter) ||
          status.includes(filter);

        if (matches) {
          row.style.display = "";
          if (filter) found = true;
        } else {
          row.style.display = "none";
        }
      });

      // Search in mobile cards
      const cards = document.querySelectorAll('.resident-card');
      cards.forEach(card => {
        // Get all searchable text content from the card
        const name = card.querySelector(".resident-name")?.textContent.toLowerCase() || "";
        const email = card.querySelector(".resident-email")?.textContent.toLowerCase() || "";

        // Get all card values for comprehensive search
        const cardValues = Array.from(card.querySelectorAll(".card-value"))
          .map(el => el.textContent.toLowerCase())
          .join(" ");

        // Check if any field matches the search filter
        const matches = !filter ||
          name.includes(filter) ||
          email.includes(filter) ||
          cardValues.includes(filter);

        if (matches) {
          card.style.display = "block";
          if (filter) found = true;
        } else {
          card.style.display = "none";
        }
      });

      // Handle "no results" message and pagination visibility
      const noDataMsg = document.getElementById("noDataMsg");
      const pagination = document.querySelector(".pagination-container");

      if (!filter) {
        // No filter applied - show all results and pagination
        if (noDataMsg) noDataMsg.style.display = "none";
        if (pagination) pagination.style.display = "flex";
      } else {
        // Filter applied
        if (!found) {
          // No results found - show no data message, hide pagination
          if (!noDataMsg) {
            const msg = document.createElement("div");
            msg.id = "noDataMsg";
            msg.className = "no-data";
            msg.innerHTML = `<i class="fas fa-search"></i><p>No residents found matching "${document.getElementById('searchInput').value}"</p>`;
            document.querySelector(".table-container").appendChild(msg);
          } else {
            noDataMsg.innerHTML = `<i class="fas fa-search"></i><p>No residents found matching "${document.getElementById('searchInput').value}"</p>`;
            noDataMsg.style.display = "block";
          }
          if (pagination) pagination.style.display = "none";
        } else {
          // Results found - hide no data message, show pagination
          if (noDataMsg) noDataMsg.style.display = "none";
          if (pagination) pagination.style.display = "flex";
        }
      }
    }

    // Enhanced jump to page function
    function jumpToPage(page) {
      if (page && page !== '<?= $currentPage; ?>') {
        window.location.href = '?page=' + page;
      }
    }

    function exportData() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      doc.setFontSize(14);
      doc.text("Residents Report", 14, 20);

      // Collect visible rows
      const rows = [];
      document.querySelectorAll("#residentsTable tbody tr").forEach(row => {
        if (row.style.display !== "none") {
          const name = row.querySelector(".resident-name")?.textContent.trim() || "";
          const email = row.querySelector(".resident-email")?.textContent.trim() || "";
          const contact = row.querySelector(".contact-number")?.textContent.trim() || "";
          const date = row.querySelector(".date-created")?.textContent.trim() || "";
          rows.push([name, email, contact, date]);
        }
      });

      if (rows.length === 0) {
        Swal.fire({
          icon: "warning",
          title: "No data to export",
          text: "There are no residents matching your current filters.",
        });
        return;
      }

      // ✅ AutoTable is available directly on doc
      doc.autoTable({
        head: [["Name", "Email", "Contact", "Created Date"]],
        body: rows,
        startY: 30,
        theme: "grid"
      });

      doc.save("residents.pdf");
    }

    function addResident() {
      Swal.fire({
        title: 'Add New Resident',
        html: `
      <div class="swal-form">
          <!-- Profile Image Section -->
          <div class="form-group profile-image-section">
              <div class="profile-image-container">
                  <img id="profilePreview" 
                       src="../assets/images/user.png" 
                       alt="Profile Preview" 
                       class="profile-preview"
                       onclick="document.getElementById('imageInput').click();">
                  <div class="camera-overlay"
                       onclick="document.getElementById('imageInput').click();">
                      <i class="fas fa-camera"></i>
                  </div>
              </div>
              <input type="file" 
                     id="imageInput" 
                     accept="image/*" 
                     class="image-input-hidden"
                     onchange="previewImage(this)">
              <div class="upload-instruction">Click to upload profile image</div>
          </div>

          <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" class="swal2-input" id="firstName" placeholder="Enter first name" required>
          </div>
          <div class="form-group">
              <label class="form-label">Middle Name</label>
              <input type="text" class="swal2-input" id="middleName" placeholder="Enter middle name">
          </div>
          <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" class="swal2-input" id="lastName" placeholder="Enter last name" required>
          </div>
          <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" class="swal2-input" id="email" placeholder="Enter email address" required>
          </div>
          <div class="form-group">
              <label class="form-label">Contact Number</label>
              <input type="number" class="swal2-input" id="contactNumber" placeholder="Enter contact number" required>
          </div>
      </div>
    `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Resident',
        didOpen: () => {
          // Add hover effect to profile image
          const profileImg = document.getElementById('profilePreview');
          profileImg.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.05)';
            this.style.borderColor = '#007bff';
          });
          profileImg.addEventListener('mouseleave', function () {
            this.style.transform = 'scale(1)';
            this.style.borderColor = '#ddd';
          });
        },
        preConfirm: () => {
          const firstName = document.getElementById('firstName').value.trim();
          const middleName = document.getElementById('middleName').value.trim();
          const lastName = document.getElementById('lastName').value.trim();
          const email = document.getElementById('email').value.trim();
          const contactNumber = document.getElementById('contactNumber').value.trim();
          const image = document.getElementById('imageInput').files[0];

          if (!firstName || !lastName || !email || !contactNumber) {
            Swal.showValidationMessage('First name, last name, email, and contact number are required');
            return false;
          }

          const formData = new FormData();
          formData.append("firstName", firstName);
          formData.append("middleName", middleName);
          formData.append("lastName", lastName);
          formData.append("email", email);
          formData.append("contactNumber", contactNumber);
          if (image) formData.append("image", image);

          return formData;
        }
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('./endpoints/add_resident.php', {
            method: 'POST',
            body: result.value
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Success', 'Resident has been added!', 'success').then(() => {
                  location.reload();
                });
              } else {
                Swal.fire('Error', data.message || 'Something went wrong', 'error');
              }
            })
            .catch(() => {
              Swal.fire('Error', 'Unable to add resident. Please try again later.', 'error');
            });
        }
      });
    }

    function previewImage(input, previewId = 'profilePreview') {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.getElementById(previewId);
          preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function viewResident(id) {
      fetch(`./endpoints/get_resident.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const resident = data.resident;

            // Format the created date
            const createdDate = new Date(resident.created_at).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            });

            // Get profile image path
            const profileImage = resident.image ?
              `../assets/images/user/${resident.image}` :
              '../assets/images/user.png';

            Swal.fire({
              title: 'Resident Details',
              html: `
            <div class="resident-details-container">
              <img src="${profileImage}" alt="Profile" class="resident-profile-image">
              
              <div class="resident-name-swal">
                ${resident.first_name} ${resident.middle_name ? resident.middle_name + ' ' : ''}${resident.last_name}
              </div>
              
              <div class="resident-email-swal">${resident.email}</div>
              
              <div class="resident-info-grid">
                <div class="info-item">
                  <div class="info-label">First Name</div>
                  <div class="info-value">${resident.first_name}</div>
                </div>
                
                ${resident.middle_name ? `
                <div class="info-item">
                  <div class="info-label">Middle Name</div>
                  <div class="info-value">${resident.middle_name}</div>
                </div>
                ` : ''}
                
                <div class="info-item">
                  <div class="info-label">Last Name</div>
                  <div class="info-value">${resident.last_name}</div>
                </div>
                
                <div class="info-item ${!resident.middle_name ? 'full-width' : ''}">
                  <div class="info-label">Contact Number</div>
                  <div class="info-value">${resident.contact_number}</div>
                </div>
                
                <div class="info-item full-width">
                  <div class="info-label">Member Since</div>
                  <div class="info-value">${createdDate}</div>
                </div>
              </div>
            </div>
          `,
              confirmButtonText: 'Close',
              confirmButtonColor: '#007bff',
              width: '500px',
              showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
              },
              hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
              }
            });
          } else {
            Swal.fire('Error', data.message || 'Unable to fetch resident details.', 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Unable to connect to server.', 'error');
        });
    }

    function editResident(id) {
      fetch(`./endpoints/get_resident.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const resident = data.resident;
            Swal.fire({
              title: 'Edit Resident',
              html: `
            <div class="swal-form">
                <!-- Profile Image Section -->
                <div class="form-group profile-image-section">
                    <div class="profile-image-container">
                        <img id="editProfilePreview" 
                             src="${resident.image ? '../assets/images/user/' + resident.image : '../assets/images/user.png'}"  
                             alt="Profile Preview" 
                             class="profile-preview"
                             onclick="document.getElementById('editImageInput').click();">
                        <div class="camera-overlay"
                             onclick="document.getElementById('editImageInput').click();">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <input type="file" 
                           id="editImageInput" 
                           accept="image/*" 
                           class="image-input-hidden"
                           onchange="previewImage(this, 'editProfilePreview')">
                    <div class="upload-instruction">Click to change profile image</div>
                </div>

                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" class="swal2-input" id="editFirstName" value="${resident.first_name}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Middle Name</label>
                    <input type="text" class="swal2-input" id="editMiddleName" value="${resident.middle_name || ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="swal2-input" id="editLastName" value="${resident.last_name}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="swal2-input" id="editEmail" value="${resident.email}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="number" class="swal2-input" id="editContactNumber" value="${resident.contact_number}" required>
                </div>
            </div>
          `,
              showCancelButton: true,
              confirmButtonText: 'Update',
              cancelButtonText: 'Cancel',
              didOpen: () => {
                // Add hover effect to profile image
                const profileImg = document.getElementById('editProfilePreview');
                profileImg.addEventListener('mouseenter', function () {
                  this.style.transform = 'scale(1.05)';
                  this.style.borderColor = '#007bff';
                });
                profileImg.addEventListener('mouseleave', function () {
                  this.style.transform = 'scale(1)';
                  this.style.borderColor = '#ddd';
                });
              },
              preConfirm: () => {
                const firstName = document.getElementById('editFirstName').value.trim();
                const middleName = document.getElementById('editMiddleName').value.trim();
                const lastName = document.getElementById('editLastName').value.trim();
                const email = document.getElementById('editEmail').value.trim();
                const contactNumber = document.getElementById('editContactNumber').value.trim();
                const image = document.getElementById('editImageInput').files[0];

                if (!firstName || !lastName || !email || !contactNumber) {
                  Swal.showValidationMessage('First name, last name, email, and contact number are required');
                  return false;
                }

                const formData = new FormData();
                formData.append("id", id);
                formData.append("firstName", firstName);
                formData.append("middleName", middleName);
                formData.append("lastName", lastName);
                formData.append("email", email);
                formData.append("contactNumber", contactNumber);
                if (image) formData.append("image", image);

                return formData;
              }
            }).then((result) => {
              if (result.isConfirmed) {
                fetch('./endpoints/edit_resident.php', {
                  method: 'POST',
                  body: result.value
                })
                  .then(res => res.json())
                  .then(data => {
                    if (data.success) {
                      Swal.fire('Success', 'Resident updated successfully!', 'success').then(() => {
                        location.reload();
                      });
                    } else {
                      Swal.fire('Error', data.message || 'Unable to update resident.', 'error');
                    }
                  })
                  .catch(() => {
                    Swal.fire('Error', 'Unable to connect to server.', 'error');
                  });
              }
            });
          } else {
            Swal.fire('Error', data.message || 'Unable to fetch resident details.', 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Unable to connect to server.', 'error');
        });
    }

    function deleteResident(id) {
      Swal.fire({
        title: 'Delete Resident',
        text: 'Are you sure you want to delete this resident? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('./endpoints/delete_resident.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Deleted!', 'Resident has been deleted.', 'success').then(() => {
                  location.reload();
                });
              } else {
                Swal.fire('Error', data.message || 'Unable to delete resident.', 'error');
              }
            })
            .catch(() => {
              Swal.fire('Error', 'Unable to connect to server. Please try again.', 'error');
            });
        }
      });
    }

  </script>

</body>

</html>