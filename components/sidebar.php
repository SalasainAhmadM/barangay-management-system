<div class="sidebar">
    <div class="logo-details" style="cursor: pointer;" title="Click to edit system settings">
        <?php
        // Fetch system settings for display
        $settingsQuery = $conn->query("SELECT system_name, system_logo FROM system_settings LIMIT 1");
        $systemSettings = $settingsQuery->fetch_assoc();

        $systemName = $systemSettings['system_name'] ?? 'BMS';
        $systemLogo = !empty($systemSettings['system_logo'])
            ? "../assets/images/settings/" . $systemSettings['system_logo']
            : "../assets/logo/bms.png";
        ?>
        <img src="<?= htmlspecialchars($systemLogo) ?>" alt="System Logo" class="logo-img">
        <div class="logo_name"><?= htmlspecialchars($systemName) ?></div>
        <i class="fa-solid fa-bars" id="btn"></i>
    </div>


    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <ul class="nav-list">
        <!-- <li>
          <i class="fa-solid fa-magnifying-glass"></i>
         <input type="text" placeholder="Search...">
         <span class="tooltip">Search</span>
      </li> -->
        <li>
            <a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-table-columns"></i>
                <span class="links_name">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>

        <li>
            <a href="residents.php" class="<?= ($current_page == 'residents.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                <span class="links_name">Residents</span>
            </a>
            <span class="tooltip">Residents</span>
        </li>

        <li>
            <a href="request_certificates.php"
                class="<?= ($current_page == 'request_certificates.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-file-lines"></i>
                <span class="links_name">Documents</span>
            </a>
            <span class="tooltip">Documents</span>
        </li>

        <li>
            <a href="waste_management.php" class="<?= ($current_page == 'waste_management.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-recycle"></i>
                <span class="links_name">Waste Mgmt</span>
            </a>
            <span class="tooltip">Waste Management</span>
        </li>

        <li>
            <a href="notifications.php" class="<?= ($current_page == 'notifications.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-message"></i>
                <span class="links_name">Notifications</span>
            </a>
            <span class="tooltip">Notifications</span>
        </li>

        <li>
            <a href="reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span class="links_name">Reports</span>
            </a>
            <span class="tooltip">Reports</span>
        </li>

        <!-- <li>
            <a href="settings.php" class="<?= ($current_page == 'settings.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i>
                <span class="links_name">Settings</span>
            </a>
            <span class="tooltip">Settings</span>
        </li> -->

        <li class="profile">
            <div class="profile-details">
                <?php
                // Get logged-in admin info
                $adminId = $_SESSION["admin_id"] ?? 0;
                $adminData = null;

                if ($adminId > 0) {
                    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, image FROM admin WHERE id = ?");
                    $stmt->bind_param("i", $adminId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $adminData = $result->fetch_assoc();
                    $stmt->close();
                }

                // Fallbacks
                if ($adminData) {
                    $middleInitial = !empty($adminData["middle_name"])
                        ? strtoupper(substr($adminData["middle_name"], 0, 1)) . "."
                        : "";
                    $adminName = trim($adminData["first_name"] . " " . $middleInitial . " " . $adminData["last_name"]);
                    $email = $adminData["email"];
                } else {
                    $adminName = "Administrator";
                    $email = "Admin";
                }

                $adminImage = (!empty($adminData["image"]))
                    ? "../assets/images/user/" . $adminData["image"]
                    : "../assets/images/admin.png";
                ?>
                <!-- Profile image clickable -->
                <img src="<?= htmlspecialchars($adminImage) ?>" alt="profileImg" style="cursor:pointer;"
                    onclick="editAdmin(<?= (int) $adminId ?>)">

                <div class="name_job">
                    <div class="name"><?= htmlspecialchars($adminName) ?></div>
                    <div class="job"><?= htmlspecialchars($email) ?></div>
                </div>
            </div>
            <a href="#" data-logout>
                <i class="fa-solid fa-right-from-bracket" id="log_out"></i>
            </a>
        </li>
    </ul>
</div>

<script src="../js/sidebar.js"></script>
<script>
    document.querySelector('[data-logout]').addEventListener('click', function (event) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../components/logout.php";
            }
        });
    });

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

                        <!-- GCash QR Section -->
                        <div class="form-group profile-image-section">
                            <label class="form-label">GCash QR Code</label>
                            <div class="profile-image-container">
                                <img style="border-radius: 0%" id="editGcashQrPreview" 
                                     src="${admin.gcash_qr ? '../assets/images/gcash/' + admin.gcash_qr : '../assets/images/gcash/qr-code.png'}"  
                                     alt="GCash QR Preview" 
                                     class="profile-preview"
                                     onclick="document.getElementById('editGcashQrInput').click();">
                                <div class="camera-overlay"
                                     onclick="document.getElementById('editGcashQrInput').click();">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                            <input type="file" 
                                   id="editGcashQrInput" 
                                   accept="image/*" 
                                   class="image-input-hidden"
                                   onchange="previewImage(this, 'editGcashQrPreview')">
                            <div class="upload-instruction">Click to change GCash QR code</div>
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
                        
                        <!-- Change Password Section -->
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="changePasswordToggle" style="margin-right: 8px;">
                                Change Password
                            </label>
                        </div>
                        
                        <div id="passwordFields" style="display: none;">
                            <div class="form-group">
                                <label for="new-password" class="form-label">New Password *</label>
                                <div style="position: relative;">
                                    <input type="password" id="new-password" class="swal2-input" placeholder="Enter New Password" style="padding-right: 40px;">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('new-password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                                </div>
                                <div id="password-strength" style="margin-top: 5px; font-size: 12px;"></div>
                            </div>
                            <div class="form-group">
                                <label for="confirm-password" class="form-label">Confirm Password *</label>
                                <div style="position: relative;">
                                    <input type="password" id="confirm-password" class="swal2-input" placeholder="Confirm New Password" style="padding-right: 40px;">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm-password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                                </div>
                                <div id="password-match" style="margin-top: 5px; font-size: 12px;"></div>
                            </div>
                            <div class="form-group">
                                <small style="color: #666; font-size: 12px;">
                                    Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long
                                </small>
                            </div>
                        </div>
                    </div>
                    `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Update Admin',
                        cancelButtonText: 'Cancel',
                        width: '600px',
                        didOpen: () => {
                            // Add hover effect to profile image and GCash QR
                            const profileImg = document.getElementById('editProfilePreview');
                            const gcashQrImg = document.getElementById('editGcashQrPreview');

                            [profileImg, gcashQrImg].forEach(img => {
                                img.addEventListener('mouseenter', function () {
                                    this.style.transform = 'scale(1.05)';
                                    this.style.borderColor = '#3b82f6';
                                });
                                img.addEventListener('mouseleave', function () {
                                    this.style.transform = 'scale(1)';
                                    this.style.borderColor = '#e5e7eb';
                                });
                            });

                            // Handle password toggle checkbox
                            const changePasswordToggle = document.getElementById('changePasswordToggle');
                            const passwordFields = document.getElementById('passwordFields');
                            const newPasswordField = document.getElementById('new-password');
                            const confirmPasswordField = document.getElementById('confirm-password');
                            const passwordStrengthDiv = document.getElementById('password-strength');
                            const passwordMatchDiv = document.getElementById('password-match');

                            changePasswordToggle.addEventListener('change', function () {
                                if (this.checked) {
                                    passwordFields.style.display = 'block';
                                } else {
                                    passwordFields.style.display = 'none';
                                    newPasswordField.value = '';
                                    confirmPasswordField.value = '';
                                    passwordStrengthDiv.innerHTML = '';
                                    passwordMatchDiv.innerHTML = '';
                                    newPasswordField.setCustomValidity('');
                                    confirmPasswordField.setCustomValidity('');
                                }
                            });

                            // Password strength check
                            newPasswordField.addEventListener('input', function () {
                                const password = this.value;
                                const strength = checkPasswordStrength(password);

                                if (password.length === 0) {
                                    passwordStrengthDiv.innerHTML = '';
                                    this.setCustomValidity('');
                                    return;
                                }

                                let strengthColor = '';
                                let strengthText = '';

                                switch (strength) {
                                    case 'Strong':
                                        strengthColor = '#28a745';
                                        strengthText = '✓ Strong password';
                                        this.setCustomValidity('');
                                        break;
                                    case 'Moderate':
                                        strengthColor = '#ffc107';
                                        strengthText = '⚠ Moderate password - add more complexity';
                                        this.setCustomValidity('Password is not strong enough');
                                        break;
                                    case 'Weak':
                                        strengthColor = '#dc3545';
                                        strengthText = '✗ Weak password - needs improvement';
                                        this.setCustomValidity('Password is too weak');
                                        break;
                                }

                                passwordStrengthDiv.innerHTML = `<span style="color: ${strengthColor};">${strengthText}</span>`;

                                if (confirmPasswordField.value) {
                                    confirmPasswordField.dispatchEvent(new Event('input'));
                                }
                            });

                            // Confirm password match check
                            confirmPasswordField.addEventListener('input', function () {
                                const newPassword = newPasswordField.value;
                                const confirmPassword = this.value;

                                if (confirmPassword.length === 0) {
                                    passwordMatchDiv.innerHTML = '';
                                    this.setCustomValidity('');
                                    return;
                                }

                                if (confirmPassword !== newPassword) {
                                    this.setCustomValidity('Passwords do not match');
                                    passwordMatchDiv.innerHTML = '<span style="color: #dc3545;">✗ Passwords do not match</span>';
                                } else {
                                    this.setCustomValidity('');
                                    passwordMatchDiv.innerHTML = '<span style="color: #28a745;">✓ Passwords match</span>';
                                }
                            });
                        },
                        preConfirm: () => {
                            const firstName = document.getElementById('first-name').value.trim();
                            const middleName = document.getElementById('middle-name').value.trim();
                            const lastName = document.getElementById('last-name').value.trim();
                            const email = document.getElementById('email').value.trim();
                            const image = document.getElementById('editImageInput').files[0];
                            const gcashQr = document.getElementById('editGcashQrInput').files[0];
                            const changePassword = document.getElementById('changePasswordToggle').checked;
                            const newPassword = document.getElementById('new-password').value;
                            const confirmPassword = document.getElementById('confirm-password').value;

                            if (!firstName || !lastName || !email) {
                                Swal.showValidationMessage('First Name, Last Name, and Email are required');
                                return false;
                            }

                            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailPattern.test(email)) {
                                Swal.showValidationMessage('Please enter a valid email address');
                                return false;
                            }

                            if (changePassword) {
                                if (!newPassword || !confirmPassword) {
                                    Swal.showValidationMessage('Both password fields are required when changing password');
                                    return false;
                                }

                                const passwordStrength = checkPasswordStrength(newPassword);
                                if (passwordStrength !== 'Strong') {
                                    Swal.showValidationMessage('Password must be strong (at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and 8 characters long)');
                                    return false;
                                }

                                if (newPassword !== confirmPassword) {
                                    Swal.showValidationMessage('Passwords do not match');
                                    return false;
                                }
                            }

                            const formData = new FormData();
                            formData.append('id', adminId);
                            formData.append('first_name', firstName);
                            formData.append('middle_name', middleName);
                            formData.append('last_name', lastName);
                            formData.append('email', email);
                            if (image) formData.append('image', image);
                            if (gcashQr) formData.append('gcash_qr', gcashQr);
                            if (changePassword) {
                                formData.append('change_password', '1');
                                formData.append('new_password', newPassword);
                            }

                            return formData;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
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

    // Function to check password strength
    function checkPasswordStrength(password) {
        const regexStrong = /(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/;
        if (password.length >= 8 && regexStrong.test(password)) {
            return 'Strong';
        } else if (password.length >= 6) {
            return 'Moderate';
        } else {
            return 'Weak';
        }
    }

    // Function to toggle password visibility
    function togglePassword(inputId, toggleIcon) {
        const passwordInput = document.getElementById(inputId);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
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

    function editSystemSettings() {
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching system settings.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('./endpoints/get_system_settings.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const settings = data.settings;

                    // Determine the correct paths for display
                    const logoSrc = settings.system_logo
                        ? '../assets/images/settings/' + settings.system_logo
                        : '../assets/logo/bms.png';

                    const bgSrc = settings.login_bg
                        ? '../assets/images/settings/' + settings.login_bg
                        : '../assets/images/settings/bg.jpg'; // Fixed path to default bg

                    Swal.fire({
                        title: 'Edit System Logos and Background',
                        html: `
                <div class="swal-form" style="padding-top: 10px">
                    <!-- System Logo Section -->
                    <div class="form-group profile-image-section">
                        <label class="form-label">System Logo</label>
                        <div class="profile-image-container">
                            <img id="editLogoPreview" 
                                 src="${logoSrc}"  
                                 alt="Logo Preview" 
                                 class="profile-preview"
                                 onclick="document.getElementById('editLogoInput').click();">
                            <div class="camera-overlay"
                                 onclick="document.getElementById('editLogoInput').click();">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <input type="file" 
                               id="editLogoInput" 
                               accept="image/*" 
                               class="image-input-hidden"
                               onchange="previewSettingsImage(this, 'editLogoPreview')">
                        <div class="upload-instruction">Click to change system logo</div>
                    </div>

                    <!-- Login Background Section -->
                    <div class="form-group profile-image-section" style="margin-top: 20px;">
                        <label class="form-label">Login Background</label>
                        <div class="profile-image-container" style="width: 100%; height: 200px;">
                            <img id="editBgPreview" 
                                 src="${bgSrc}"  
                                 alt="Background Preview" 
                                 class="profile-preview"
                                 style="width: 100%; height: 100%; border-radius: 8px; object-fit: cover;"
                                 onclick="document.getElementById('editBgInput').click();">
                            <div class="camera-overlay"
                                 onclick="document.getElementById('editBgInput').click();">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <input type="file" 
                               id="editBgInput" 
                               accept="image/*" 
                               class="image-input-hidden"
                               onchange="previewSettingsImage(this, 'editBgPreview')">
                        <div class="upload-instruction">Click to change login background</div>
                    </div>

                    <div class="form-group" style="display: none">
                        <label for="system-name" class="form-label">System Name *</label>
                        <input type="text" id="system-name" class="swal2-input" value="${settings.system_name}" placeholder="Enter System Name" required>
                    </div>
                </div>
                `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Update Settings',
                        cancelButtonText: 'Cancel',
                        width: '600px',
                        didOpen: () => {
                            const logoImg = document.getElementById('editLogoPreview');
                            const bgImg = document.getElementById('editBgPreview');

                            [logoImg, bgImg].forEach(img => {
                                img.addEventListener('mouseenter', function () {
                                    this.style.transform = 'scale(1.05)';
                                    this.style.borderColor = '#3b82f6';
                                });
                                img.addEventListener('mouseleave', function () {
                                    this.style.transform = 'scale(1)';
                                    this.style.borderColor = '#e5e7eb';
                                });
                            });
                        },
                        preConfirm: () => {
                            const systemName = document.getElementById('system-name').value.trim();
                            const logoFile = document.getElementById('editLogoInput').files[0];
                            const bgFile = document.getElementById('editBgInput').files[0];

                            if (!systemName) {
                                Swal.showValidationMessage('System Name is required');
                                return false;
                            }

                            const formData = new FormData();
                            formData.append('id', settings.id);
                            formData.append('system_name', systemName);
                            if (logoFile) formData.append('system_logo', logoFile);
                            if (bgFile) formData.append('login_bg', bgFile);

                            return formData;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Updating Settings...',
                                text: 'Please wait while we process your request.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            fetch('./endpoints/update_system_settings.php', {
                                method: 'POST',
                                body: result.value
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Updated!',
                                            text: 'System settings have been updated.',
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: data.message || 'Could not save settings.',
                                            icon: 'error'
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Unable to save settings.',
                                        icon: 'error'
                                    });
                                });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Unable to load system settings',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error!',
                    text: 'Error fetching system settings',
                    confirmButtonText: 'OK'
                });
            });
    }

    function previewSettingsImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById(previewId);
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Make logo section clickable (add this event listener)
    document.addEventListener('DOMContentLoaded', function () {
        const logoDetails = document.querySelector('.logo-details');
        if (logoDetails) {
            logoDetails.style.cursor = 'pointer';
            logoDetails.addEventListener('click', function (e) {
                // Don't trigger on menu button click
                if (!e.target.closest('#btn')) {
                    editSystemSettings();
                }
            });
        }
    });
</script>