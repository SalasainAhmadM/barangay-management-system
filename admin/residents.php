<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}

$residents = [];
try {
  $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, email, contact_number, image, created_at 
                          FROM user 
                          ORDER BY created_at DESC");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
  }
} catch (Exception $e) {
  die("Error fetching residents: " . $e->getMessage());
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
                      <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']); ?>
                    </div>
                  </td>
                  <td>
                    <div class="resident-email"><?= htmlspecialchars($resident['email']); ?></div>
                  </td>
                  <td>
                    <div class="contact-number"><?= htmlspecialchars($resident['contact_number']); ?></div>
                  </td>
                  <td>
                    <div class="date-created"><?= date("M d, Y", strtotime($resident['created_at'])); ?></div>
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
                <td colspan="6" class="no-data">
                  <i class="fas fa-users"></i>
                  <p>No residents found</p>
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
                    <?= htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']); ?>
                  </div>
                  <div class="resident-email"><?= htmlspecialchars($resident['email']); ?></div>
                </div>
              </div>
              <div class="card-body">
                <div class="card-field">Contact:</div>
                <div class="card-value"><?= htmlspecialchars($resident['contact_number']); ?></div>
                <div class="card-field">Created:</div>
                <div class="card-value"><?= date("M d, Y", strtotime($resident['created_at'])); ?></div>
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
        const name = row.querySelector(".resident-name")?.textContent.toLowerCase() || "";
        const email = row.querySelector(".resident-email")?.textContent.toLowerCase() || "";
        const contact = row.querySelector(".contact-number")?.textContent.toLowerCase() || "";

        if (!filter || name.includes(filter) || email.includes(filter) || contact.includes(filter)) {
          row.style.display = "";
          if (filter) found = true;
        } else {
          row.style.display = "none";
        }
      });

      // Search in mobile cards
      const cards = document.querySelectorAll('.resident-card');
      cards.forEach(card => {
        const name = card.querySelector(".resident-name")?.textContent.toLowerCase() || "";
        const email = card.querySelector(".resident-email")?.textContent.toLowerCase() || "";
        const contact = card.querySelector(".card-value")?.textContent.toLowerCase() || "";

        if (!filter || name.includes(filter) || email.includes(filter) || contact.includes(filter)) {
          card.style.display = "block";
          if (filter) found = true;
        } else {
          card.style.display = "none";
        }
      });

      // Show message when no results
      const noDataMsg = document.getElementById("noDataMsg");
      if (!filter) {
        if (noDataMsg) noDataMsg.style.display = "none";
      } else {
        if (!found) {
          if (!noDataMsg) {
            const msg = document.createElement("div");
            msg.id = "noDataMsg";
            msg.className = "no-data";
            msg.innerHTML = `<i class="fas fa-exclamation-circle"></i><p>No data found</p>`;
            document.querySelector(".table-container").appendChild(msg);
          } else {
            noDataMsg.style.display = "block";
          }
        } else {
          if (noDataMsg) noDataMsg.style.display = "none";
        }
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
                <input type="tel" class="swal2-input" id="contactNumber" placeholder="Enter contact number" required>
            </div>
        </div>
    `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Resident',
        cancelButtonText: 'Cancel',
        didOpen: () => {
          document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
          document.body.classList.remove("swal2-shown", "swal2-height-auto");
        },
        preConfirm: () => {
          const firstName = document.getElementById('firstName').value.trim();
          const middleName = document.getElementById('middleName').value.trim();
          const lastName = document.getElementById('lastName').value.trim();
          const email = document.getElementById('email').value.trim();
          const contactNumber = document.getElementById('contactNumber').value.trim();

          if (!firstName || !lastName || !email || !contactNumber) {
            Swal.showValidationMessage('First name, last name, email, and contact number are required');
            return false;
          }

          return { firstName, middleName, lastName, email, contactNumber };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('./endpoints/add_resident.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(result.value)
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Success', 'Resident has been added!', 'success').then(() => {
                  location.reload(); // Refresh to show new resident
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


    function viewResident(id) {
      // Implement view resident functionality
      window.location.href = `view_resident.php?id=${id}`;
    }

    function editResident(id) {
      // Implement edit resident functionality
      window.location.href = `edit_resident.php?id=${id}`;
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


    // function exportData() {

    // }

  </script>

</body>

</html>