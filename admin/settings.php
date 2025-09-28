<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php?auth=error");
    exit();
}

// Fetch admins
$admins = [];
try {
    $sql = "SELECT id, first_name, middle_name, last_name, email, image, logo, updated_at 
            FROM admin 
            ORDER BY id DESC"; // fallback to ID sorting since created_at doesn't exist
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $admins[] = $row;
        }
    }
} catch (Exception $e) {
    die("Error fetching admins: " . $e->getMessage());
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
                <h2 class="table-title">Admin Management</h2>
            </div>

            <div class="table-responsive">
                <table class="residents-table" id="residentsTable">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Updated Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($admins) > 0): ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td>
                                        <img src="<?= !empty($admin['image']) ? '../assets/images/user/' . htmlspecialchars($admin['image']) : '../assets/images/user.png'; ?>"
                                            alt="Profile" class="profile-img">
                                    </td>
                                    <td>
                                        <div class="resident-name">
                                            <?= htmlspecialchars(trim($admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="resident-email">
                                            <?= htmlspecialchars($admin['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-created">
                                            <?= !empty($admin['updated_at']) ? date("M d, Y", strtotime($admin['updated_at'])) : "Never"; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-view" onclick="viewAdmin(<?= $admin['id']; ?>)"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-edit" onclick="editAdmin(<?= $admin['id']; ?>)"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">
                                    <i class="fas fa-users"></i>
                                    <p>No admins found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="mobile-cards">
                <?php if (count($admins) > 0): ?>
                    <?php foreach ($admins as $admin): ?>
                        <div class="resident-card">
                            <div class="card-header">
                                <img src="<?= !empty($admin['image']) ? '../assets/images/user/' . htmlspecialchars($admin['image']) : '../assets/images/user.png'; ?>"
                                    alt="Profile" class="profile-img">
                                <div>
                                    <div class="resident-name">
                                        <?= htmlspecialchars(trim($admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name'])); ?>
                                    </div>
                                    <div class="resident-email"><?= htmlspecialchars($admin['email']); ?></div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="card-field">Logo:</div>
                                <div class="card-value">
                                    <?php if (!empty($admin['logo'])): ?>
                                        <img src="../assets/images/logo/<?= htmlspecialchars($admin['logo']); ?>" alt="Logo"
                                            class="profile-img">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>

                                <div class="card-field">Updated:</div>
                                <div class="card-value">
                                    <?= !empty($admin['updated_at']) ? date("M d, Y", strtotime($admin['updated_at'])) : "Never"; ?>
                                </div>
                            </div>

                            <div class="card-actions">
                                <button class="btn btn-sm btn-view" onclick="viewAdmin(<?= $admin['id']; ?>)" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-edit" onclick="editAdmin(<?= $admin['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <p>No admins found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include '../components/cdn_scripts.php'; ?>

    <script>

        function viewAdmin(id) {
            // Show loading state
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching admin information.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`./endpoints/get_admin.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const admin = data.admin;

                        // Format dates
                        const updatedDate = admin.updated_at ? new Date(admin.updated_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        }) : 'Never';

                        const createdDate = admin.created_at ? new Date(admin.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        }) : 'N/A';

                        // Get profile image path
                        const profileImage = admin.image ?
                            `../assets/images/user/${admin.image}` :
                            '../assets/images/user.png';

                        // Get logo image path
                        const logoImage = admin.logo ?
                            `../assets/images/logo/${admin.logo}` :
                            null;

                        Swal.fire({
                            title: 'Admin Details',
                            html: `
                            <div class="resident-details-container-simple">
                              <!-- Profile Header -->
                              <div class="profile-header-simple">
                                <img src="${profileImage}" alt="Profile" class="resident-profile-image-simple">
                                <div class="profile-info-simple">
                                  <div class="resident-name-simple">
                                    ${admin.first_name} ${admin.middle_name ? admin.middle_name + ' ' : ''}${admin.last_name}
                                  </div>
                                  <div class="resident-email-simple">${admin.email || 'N/A'}</div>
                                </div>
                              </div>
                              
                              <!-- Personal Information Section -->
                              <div class="section-header-simple">Personal Information</div>
                              <div class="resident-info-grid-simple">
                                <div class="info-item-simple">
                                  <div class="info-label-simple">First Name</div>
                                  <div class="info-value-simple">${admin.first_name || 'N/A'}</div>
                                </div>
                                
                                <div class="info-item-simple">
                                  <div class="info-label-simple">Middle Name</div>
                                  <div class="info-value-simple">${admin.middle_name || 'N/A'}</div>
                                </div>
                                
                                <div class="info-item-simple">
                                  <div class="info-label-simple">Last Name</div>
                                  <div class="info-value-simple">${admin.last_name || 'N/A'}</div>
                                </div>
                                
                                <div class="info-item-simple">
                                  <div class="info-label-simple">Email Address</div>
                                  <div class="info-value-simple">${admin.email || 'N/A'}</div>
                                </div>
                              </div>
                              
                              <!-- System Information Section -->
                              <div class="section-header-simple">System Information</div>
                              <div class="resident-info-grid-simple">
                                ${logoImage ? `
                                <div class="info-item-simple">
                                  <div class="info-label-simple">Logo</div>
                                  <div class="info-value-simple">
                                    <img src="${logoImage}" alt="Logo" class="logo-preview-simple" style="max-width: 50px; max-height: 50px; object-fit: contain;">
                                  </div>
                                </div>
                                ` : ''}
                            

                                <div class="info-item-simple">
                                  <div class="info-label-simple">Last Updated</div>
                                  <div class="info-value-simple">${updatedDate}</div>
                                </div>
                              </div>
                            </div>
                          `,
                            customClass: {
                                popup: 'swal-view-simple'
                            },
                            confirmButtonText: 'Close',
                            confirmButtonColor: '#00247c'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Unable to fetch admin details.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Unable to connect to server.',
                        confirmButtonText: 'OK'
                    });
                });
        }

        function editAdmin(adminId) {
            // Show loading state while fetching data
            Swal.fire({
                title: 'Loading...',
                text: 'Fetching admin information.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('./endpoints/get_admin.php?id=' + adminId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const admin = data.admin;
                        Swal.fire({
                            title: 'Edit Admin',
                            html: `
                            <div class="swal-form" style="padding-top: 10px">
                                <!-- Profile Image Section -->
                                <div class="form-group profile-image-section">
                                    <div class="profile-image-container">
                                        <img id="editProfilePreview" 
                                             src="${admin.image ? '../assets/images/user/' + admin.image : '../assets/images/user.png'}"  
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
                                    <label for="first-name" class="form-label">First Name *</label>
                                    <input type="text" id="first-name" class="swal2-input" value="${admin.first_name}" placeholder="Enter First Name" required>
                                </div>
                                <div class="form-group">
                                    <label for="middle-name" class="form-label">Middle Name</label>
                                    <input type="text" id="middle-name" class="swal2-input" value="${admin.middle_name || ''}" placeholder="Enter Middle Name">
                                </div>
                                <div class="form-group">
                                    <label for="last-name" class="form-label">Last Name *</label>
                                    <input type="text" id="last-name" class="swal2-input" value="${admin.last_name}" placeholder="Enter Last Name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" id="email" class="swal2-input" value="${admin.email}" placeholder="Enter Email" required>
                                </div>
                                
                            </div>
                            `,
                            focusConfirm: false,
                            showCancelButton: true,
                            confirmButtonText: 'Update Admin',
                            cancelButtonText: 'Cancel',
                            didOpen: () => {
                                // Add hover effect to profile image
                                const profileImg = document.getElementById('editProfilePreview');
                                profileImg.addEventListener('mouseenter', function () {
                                    this.style.transform = 'scale(1.05)';
                                    this.style.borderColor = '#3b82f6';
                                });
                                profileImg.addEventListener('mouseleave', function () {
                                    this.style.transform = 'scale(1)';
                                    this.style.borderColor = '#e5e7eb';
                                });
                            },
                            preConfirm: () => {
                                const firstName = document.getElementById('first-name').value.trim();
                                const middleName = document.getElementById('middle-name').value.trim();
                                const lastName = document.getElementById('last-name').value.trim();
                                const email = document.getElementById('email').value.trim();
                                const image = document.getElementById('editImageInput').files[0];

                                if (!firstName || !lastName || !email) {
                                    Swal.showValidationMessage('First Name, Last Name, and Email are required');
                                    return false;
                                }

                                // Validate email format
                                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (!emailPattern.test(email)) {
                                    Swal.showValidationMessage('Please enter a valid email address');
                                    return false;
                                }

                                const formData = new FormData();
                                formData.append('id', adminId);
                                formData.append('first_name', firstName);
                                formData.append('middle_name', middleName);
                                formData.append('last_name', lastName);
                                formData.append('email', email);
                                if (image) formData.append('image', image);

                                return formData;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Show loading state
                                Swal.fire({
                                    title: 'Updating Admin...',
                                    text: 'Please wait while we process your request.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                fetch('./endpoints/update_admin.php', {
                                    method: 'POST',
                                    body: result.value
                                })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Updated!',
                                                text: 'Admin details have been updated.',
                                                icon: 'success',
                                                confirmButtonText: 'OK'
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Error',
                                                text: data.message || 'Could not save admin details.',
                                                icon: 'error'
                                            });
                                        }
                                    })
                                    .catch(() => {
                                        Swal.fire({
                                            title: 'Error',
                                            text: 'Unable to save details.',
                                            icon: 'error'
                                        });
                                    });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Unable to load admin details',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error!',
                        text: 'Error fetching admin details',
                        confirmButtonText: 'OK'
                    });
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
    </script>
</body>

</html>