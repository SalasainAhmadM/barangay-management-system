<nav class="nav">
    <i class="fa-solid fa-bars navOpenBtn"></i>
    <?php
    // Fetch system settings for display
    $settingsQuery = $conn->query("SELECT system_name, system_logo FROM system_settings LIMIT 1");
    $systemSettings = $settingsQuery->fetch_assoc();

    $systemName = $systemSettings['system_name'] ?? 'BMS';
    $systemLogo = !empty($systemSettings['system_logo'])
        ? "../assets/images/settings/" . $systemSettings['system_logo']
        : "../assets/logo/bms.png";
    ?>
    <a href="#" class="logo">
        <img src="<?= htmlspecialchars($systemLogo) ?>" alt="BMS Logo" class="logo-img" />
        <span>BMS</span>
    </a>

    <?php
    // Fetch user image
    $userId = $_SESSION["user_id"];
    $query = "SELECT image FROM user WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    // If user has uploaded an image, use it, else use default
    $userImage = !empty($userData["image"]) ? "../assets/images/user/" . $userData["image"] : "../assets/images/user.png";

    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <ul class="nav-links">
        <i class="fa-solid fa-xmark navCloseBtn"></i>
        <li><a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">Home</a></li>
        <li><a href="certificates.php"
                class="<?= $currentPage == 'certificates.php' ? 'active' : '' ?>">Certificates</a></li>
        <li><a href="waste_management.php" class="<?= $currentPage == 'waste_management.php' ? 'active' : '' ?>">Reports</a></li>
        <li><a href="notifications.php"
                class="<?= $currentPage == 'notifications.php' ? 'active' : '' ?>">Notifications</a></li>
    </ul>


    <div class="nav-right">
        <i class="fa-solid fa-magnifying-glass search-icon" id="searchIcon"></i>

        <div class="profile-container">
            <div class="profile-trigger">
                <img src="<?= htmlspecialchars($userImage); ?>" alt="Profile" />
            </div>

            <div class="profile-dropdown">
                <a href="#" id="editProfileBtn">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
                <a href="#" data-logout>
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div class="search-box">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" placeholder="Search here..." />
    </div>
</nav>

<script src="../js/navbar.js"></script>
<script>
    // Logout functionality
    document.querySelector('[data-logout]').addEventListener('click', function (event) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            didOpen: () => {
                document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                document.body.classList.remove("swal2-shown", "swal2-height-auto");
            },
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../components/logout.php";
            }
        });
    });

    document.getElementById('editProfileBtn').addEventListener('click', function (event) {
        event.preventDefault();
        editProfile();
    });
    // Profile edit functionality
    function editProfile() {
        fetch(`./endpoints/get_profile.php`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const profile = data.profile;
                    Swal.fire({
                        title: 'Edit Profile',
                        html: `
                        <div class="swal-form-wide" style="padding-top: 10px">
                            <!-- Profile Image Section -->
                            <div class="form-group profile-image-section">
                                <div class="profile-image-container">
                                    <img id="editProfilePreview" 
                                         src="${profile.image ? '../assets/images/user/' + profile.image : '../assets/images/user.png'}"  
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

                            <!-- Personal Information Section -->
                            <div class="section-title">Personal Information</div>
                            
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="swal2-input" id="editFirstName" value="${profile.first_name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="swal2-input" id="editMiddleName" value="${profile.middle_name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="swal2-input" id="editLastName" value="${profile.last_name || ''}" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="swal2-input" id="editDateOfBirth" value="${profile.date_of_birth || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select class="swal2-select" id="editGender">
                                    <option value="">Select Gender</option>
                                    <option value="male" ${profile.gender === 'male' ? 'selected' : ''}>Male</option>
                                    <option value="female" ${profile.gender === 'female' ? 'selected' : ''}>Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Civil Status</label>
                                <select class="swal2-select" id="editCivilStatus">
                                    <option value="">Select Civil Status</option>
                                    <option value="single" ${profile.civil_status === 'single' ? 'selected' : ''}>Single</option>
                                    <option value="married" ${profile.civil_status === 'married' ? 'selected' : ''}>Married</option>
                                    <option value="divorced" ${profile.civil_status === 'divorced' ? 'selected' : ''}>Divorced</option>
                                    <option value="widowed" ${profile.civil_status === 'widowed' ? 'selected' : ''}>Widowed</option>
                                </select>
                            </div>

                            <!-- Contact Information Section -->
                            <div class="section-title">Contact Information</div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="swal2-input" id="editEmail" value="${profile.email || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Number *</label>
                                <input type="tel" class="swal2-input" id="editContactNumber" value="${profile.contact_number || ''}" required 
                                       pattern="09[0-9]{9}" maxlength="11">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Occupation</label>
                                <input type="text" class="swal2-input" id="editOccupation" value="${profile.occupation || ''}">
                            </div>

                            <!-- Address Information Section -->
                            <div class="section-title">Address Information</div>
                            
                            <div class="form-group">
                                <label class="form-label">House Number</label>
                                <input type="text" class="swal2-input" id="editHouseNumber" value="${profile.house_number || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Street Name</label>
                                <input type="text" class="swal2-input" id="editStreetName" value="${profile.street_name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Barangay</label>
                                <input type="text" class="swal2-input" id="editBarangay" value="${profile.barangay || 'Baliwasan'}">
                            </div>
                        </div>
                      `,
                        customClass: {
                            popup: 'swal-wide'
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Update Profile',
                        cancelButtonText: 'Cancel',
                        didOpen: () => {
                            document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                            document.body.classList.remove("swal2-shown", "swal2-height-auto");
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

                            // Add input validation for contact number
                            const contactInput = document.getElementById('editContactNumber');
                            contactInput.addEventListener('input', function () {
                                this.value = this.value.replace(/[^0-9]/g, '');
                                if (this.value.length > 11) {
                                    this.value = this.value.slice(0, 11);
                                }
                            });
                        },
                        preConfirm: () => {
                            const firstName = document.getElementById('editFirstName').value.trim();
                            const middleName = document.getElementById('editMiddleName').value.trim();
                            const lastName = document.getElementById('editLastName').value.trim();
                            const email = document.getElementById('editEmail').value.trim();
                            const contactNumber = document.getElementById('editContactNumber').value.trim();
                            const dateOfBirth = document.getElementById('editDateOfBirth').value;
                            const gender = document.getElementById('editGender').value;
                            const civilStatus = document.getElementById('editCivilStatus').value;
                            const occupation = document.getElementById('editOccupation').value.trim();
                            const houseNumber = document.getElementById('editHouseNumber').value.trim();
                            const streetName = document.getElementById('editStreetName').value.trim();
                            const barangay = document.getElementById('editBarangay').value.trim();
                            const image = document.getElementById('editImageInput').files[0];

                            // Validate required fields
                            if (!firstName || !lastName || !email || !contactNumber) {
                                Swal.showValidationMessage('First name, last name, email, and contact number are required');
                                return false;
                            }

                            // Validate email format
                            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailPattern.test(email)) {
                                Swal.showValidationMessage('Please enter a valid email address');
                                return false;
                            }

                            // Validate contact number format
                            const contactPattern = /^09\d{9}$/;
                            if (!contactPattern.test(contactNumber)) {
                                Swal.showValidationMessage('Contact number must start with 09 and be 11 digits long');
                                return false;
                            }

                            const formData = new FormData();
                            formData.append("firstName", firstName);
                            formData.append("middleName", middleName);
                            formData.append("lastName", lastName);
                            formData.append("email", email);
                            formData.append("contactNumber", contactNumber);
                            formData.append("dateOfBirth", dateOfBirth);
                            formData.append("gender", gender);
                            formData.append("civilStatus", civilStatus);
                            formData.append("occupation", occupation);
                            formData.append("houseNumber", houseNumber);
                            formData.append("streetName", streetName);
                            formData.append("barangay", barangay || "Baliwasan");
                            if (image) formData.append("image", image);

                            return formData;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state
                            Swal.fire({
                                title: 'Updating Profile...',
                                text: 'Please wait while we process your request.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                    document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                                    document.body.classList.remove("swal2-shown", "swal2-height-auto");
                                }
                            });

                            fetch('./endpoints/update_profile.php', {
                                method: 'POST',
                                body: result.value
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: 'Profile updated successfully!',
                                            confirmButtonText: 'OK',
                                            didOpen: () => {
                                                document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                                                document.body.classList.remove("swal2-shown", "swal2-height-auto");
                                            }
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: data.message || 'Unable to update profile.',
                                            confirmButtonText: 'OK',
                                            didOpen: () => {
                                                document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                                                document.body.classList.remove("swal2-shown", "swal2-height-auto");
                                            }
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Connection Error!',
                                        text: 'Unable to connect to server. Please try again.',
                                        confirmButtonText: 'OK',
                                        didOpen: () => {
                                            document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                                            document.body.classList.remove("swal2-shown", "swal2-height-auto");
                                        }
                                    });
                                });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Unable to fetch profile details.',
                        confirmButtonText: 'OK',
                        didOpen: () => {
                            document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                            document.body.classList.remove("swal2-shown", "swal2-height-auto");
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error!',
                    text: 'Unable to connect to server.',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    }
                });
            });
    }

    // Image preview function
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById(previewId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>