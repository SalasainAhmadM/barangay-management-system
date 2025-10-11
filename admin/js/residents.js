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
        //const gender = row.cells[5]?.textContent.toLowerCase() || "";
        //const civilStatus = row.cells[6]?.textContent.toLowerCase() || "";
        //const occupation = row.cells[7]?.textContent.toLowerCase() || "";
        const address = row.cells[8]?.textContent.toLowerCase() || "";
        const status = row.cells[9]?.textContent.toLowerCase() || "";

        // Check if any field matches the search filter
        const matches = !filter ||
            name.includes(filter) ||
            email.includes(filter) ||
            contact.includes(filter) ||
            //gender.includes(filter) ||
            //civilStatus.includes(filter) ||
            //occupation.includes(filter) ||
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
    // Show loading state
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait while we export all residents',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch all residents data
    fetch('./endpoints/get_all_residents.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.residents.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No data to export",
                    text: "There are no residents in the database.",
                    confirmButtonColor: '#00247c'
                });
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Title
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text("Residents Report", 14, 20);

            // Date and time
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 14, 28);

            // Prepare rows from fetched data
            const rows = data.residents.map(resident => {
                const fullName = `${resident.first_name || ''} ${resident.middle_name || ''} ${resident.last_name || ''}`.trim();
                const email = resident.email || 'N/A';
                const contact = resident.contact_number || 'N/A';
                const address = [resident.house_number, resident.street_name, resident.barangay]
                    .filter(Boolean)
                    .join(', ') || 'N/A';
                const status = resident.status ? resident.status.charAt(0).toUpperCase() + resident.status.slice(1) : 'Inactive';
                const createdDate = new Date(resident.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                return [fullName, email, contact, address, status, createdDate];
            });

            // Create table
            doc.autoTable({
                head: [["Name", "Email", "Contact", "Address", "Status", "Created Date"]],
                body: rows,
                startY: 34,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [0, 36, 124],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                columnStyles: {
                    0: { cellWidth: 35 },  // Name
                    1: { cellWidth: 45 },  // Email
                    2: { cellWidth: 28 },  // Contact
                    3: { cellWidth: 40 },  // Address
                    4: { cellWidth: 20 },  // Status
                    5: { cellWidth: 25 }   // Date
                }
            });

            // Footer with total count
            const finalY = doc.lastAutoTable.finalY || 34;
            doc.setFontSize(9);
            doc.text(`Total Residents: ${rows.length}`, 14, finalY + 10);

            // Save the PDF
            const filename = `residents_report_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);

            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Exported Successfully!',
                text: `${rows.length} resident(s) exported to PDF`,
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Export Failed',
                text: 'There was an error exporting the data. Please try again.',
                confirmButtonColor: '#00247c'
            });
        });
}

function addResident() {
    Swal.fire({
        title: '<i class="fas fa-user-plus"></i> Add New Resident',
        html: `
      <div class="swal-form-wide" style="padding-top: 10px">
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

          <div class="section-title">Personal Information</div>
          
          <div class="form-group">
              <label class="form-label">First Name *</label>
              <input type="text" class="swal2-input" id="firstName" placeholder="Enter first name" required>
          </div>
          <div class="form-group">
              <label class="form-label">Middle Name</label>
              <input type="text" class="swal2-input" id="middleName" placeholder="Enter middle name">
          </div>
          <div class="form-group">
              <label class="form-label">Last Name *</label>
              <input type="text" class="swal2-input" id="lastName" placeholder="Enter last name" required>
          </div>
          <div class="form-group">
              <label class="form-label">Date of Birth</label>
              <input type="date" class="swal2-input" id="dateOfBirth">
          </div>
          <div class="form-group">
              <label class="form-label">Gender</label>
              <select class="swal2-select" id="gender">
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
              </select>
          </div>
          <div class="form-group">
              <label class="form-label">Civil Status</label>
              <select class="swal2-select" id="civilStatus">
                  <option value="">Select Civil Status</option>
                  <option value="single">Single</option>
                  <option value="married">Married</option>
                  <option value="divorced">Divorced</option>
                  <option value="widowed">Widowed</option>
              </select>
          </div>

          <!-- Contact Information Section -->
          <div class="section-title">Contact Information</div>
          
          <div class="form-group">
              <label class="form-label">Email Address *</label>
              <input type="email" class="swal2-input" id="email" placeholder="Enter email address" required>
          </div>
          <div class="form-group">
              <label class="form-label">Contact Number *</label>
              <input type="tel" class="swal2-input" id="contactNumber" placeholder="09XXXXXXXXX" required 
                     pattern="09[0-9]{9}" maxlength="11">
          </div>
          <div class="form-group">
              <label class="form-label">Occupation</label>
              <input type="text" class="swal2-input" id="occupation" placeholder="Enter occupation">
          </div>

          <!-- Password Section -->
          <div class="section-title">Account Credentials</div>
          
          <div class="form-group">
              <label class="form-label">Password *</label>
              <div style="position: relative;">
                  <input type="password" class="swal2-input" id="password" placeholder="Enter password" required style="padding-right: 40px;">
                  <i class="fas fa-eye password-toggle" onclick="togglePasswordResident('password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
              </div>
              <div id="password-strength-add" style="margin-top: 5px; font-size: 12px;"></div>
          </div>
          
          <div class="form-group">
              <label class="form-label">Confirm Password *</label>
              <div style="position: relative;">
                  <input type="password" class="swal2-input" id="confirmPassword" placeholder="Confirm password" required style="padding-right: 40px;">
                  <i class="fas fa-eye password-toggle" onclick="togglePasswordResident('confirmPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
              </div>
              <div id="password-match-add" style="margin-top: 5px; font-size: 12px;"></div>
          </div>
          
          <div class="form-group">
              <small style="color: #666; font-size: 12px;">
                  Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long
              </small>
          </div>

          <!-- Address Information Section -->
          <div class="section-title">Address Information</div>
          
          <div class="form-group">
              <label class="form-label">House Number</label>
              <input type="text" class="swal2-input" id="houseNumber" placeholder="Enter house number">
          </div>
          <div class="form-group">
              <label class="form-label">Street Name</label>
              <input type="text" class="swal2-input" id="streetName" placeholder="Enter street name">
          </div>
          <div class="form-group">
              <label class="form-label">Barangay</label>
              <input type="text" class="swal2-input" id="barangay" placeholder="Baliwasan" value="Baliwasan">
          </div>
          
          <div class="form-group">
              <label class="form-label">Status</label>
              <select class="swal2-select" id="status">
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="moved">Moved</option>
              </select>
          </div>
      </div>
    `,
        customClass: {
            popup: 'swal-wide'
        },
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Resident',
        cancelButtonText: 'Cancel',
        didOpen: () => {
            // Add hover effect to profile image
            const profileImg = document.getElementById('profilePreview');
            profileImg.addEventListener('mouseenter', function () {
                this.style.transform = 'scale(1.05)';
                this.style.borderColor = '#3b82f6';
            });
            profileImg.addEventListener('mouseleave', function () {
                this.style.transform = 'scale(1)';
                this.style.borderColor = '#e5e7eb';
            });

            const dateInput = document.getElementById('dateOfBirth');
            if (dateInput) {
                dateInput.value = "2000-01-01"; // default January 1, 2000
            }
            // Add input validation for contact number
            const contactInput = document.getElementById('contactNumber');
            contactInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 11) {
                    this.value = this.value.slice(0, 11);
                }
            });

            // Password validation setup
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirmPassword');
            const passwordStrengthDiv = document.getElementById('password-strength-add');
            const passwordMatchDiv = document.getElementById('password-match-add');

            // Password strength check
            passwordField.addEventListener('input', function () {
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
                        strengthColor = '#28a745'; // Green
                        strengthText = '✓ Strong password';
                        this.setCustomValidity('');
                        break;
                    case 'Moderate':
                        strengthColor = '#ffc107'; // Yellow
                        strengthText = '⚠ Moderate password - add more complexity';
                        this.setCustomValidity('Password is not strong enough');
                        break;
                    case 'Weak':
                        strengthColor = '#dc3545'; // Red
                        strengthText = '✗ Weak password - needs improvement';
                        this.setCustomValidity('Password is too weak');
                        break;
                }

                passwordStrengthDiv.innerHTML = `<span style="color: ${strengthColor};">${strengthText}</span>`;

                // Re-check confirm password when new password changes
                if (confirmPasswordField.value) {
                    confirmPasswordField.dispatchEvent(new Event('input'));
                }
            });

            // Confirm password match check
            confirmPasswordField.addEventListener('input', function () {
                const newPassword = passwordField.value;
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
            const firstName = document.getElementById('firstName').value.trim();
            const middleName = document.getElementById('middleName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const contactNumber = document.getElementById('contactNumber').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const dateOfBirth = document.getElementById('dateOfBirth').value;
            const gender = document.getElementById('gender').value;
            const civilStatus = document.getElementById('civilStatus').value;
            const occupation = document.getElementById('occupation').value.trim();
            const houseNumber = document.getElementById('houseNumber').value.trim();
            const streetName = document.getElementById('streetName').value.trim();
            const barangay = document.getElementById('barangay').value.trim();
            const status = document.getElementById('status').value;
            const image = document.getElementById('imageInput').files[0];

            // Validate required fields
            if (!firstName || !lastName || !email || !contactNumber || !password || !confirmPassword) {
                Swal.showValidationMessage('First name, last name, email, contact number, and password are required');
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

            // Validate password strength
            const passwordStrength = checkPasswordStrength(password);
            if (passwordStrength !== 'Strong') {
                Swal.showValidationMessage('Password must be strong (at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and 8 characters long)');
                return false;
            }

            if (password !== confirmPassword) {
                Swal.showValidationMessage('Passwords do not match');
                return false;
            }

            const formData = new FormData();
            formData.append("firstName", firstName);
            formData.append("middleName", middleName);
            formData.append("lastName", lastName);
            formData.append("email", email);
            formData.append("contactNumber", contactNumber);
            formData.append("password", password);
            formData.append("dateOfBirth", dateOfBirth);
            formData.append("gender", gender);
            formData.append("civilStatus", civilStatus);
            formData.append("occupation", occupation);
            formData.append("houseNumber", houseNumber);
            formData.append("streetName", streetName);
            formData.append("barangay", barangay || "Baliwasan");
            formData.append("status", status);
            if (image) formData.append("image", image);

            return formData;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Adding Resident...',
                text: 'Please wait while we process your request.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('./endpoints/add_resident.php', {
                method: 'POST',
                body: result.value
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Resident has been added successfully!',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Something went wrong while adding the resident.',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error!',
                        text: 'Unable to add resident. Please check your connection and try again.',
                        confirmButtonText: 'OK'
                    });
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
    // Show loading state
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching resident information.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`./endpoints/get_resident.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const resident = data.resident;

                // Format dates
                const createdDate = resident.created_at ? new Date(resident.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';

                const updatedDate = resident.updated_at ? new Date(resident.updated_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';

                const dateOfBirth = resident.date_of_birth ? new Date(resident.date_of_birth).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';

                // Calculate age if date of birth is available
                let age = 'N/A';
                if (resident.date_of_birth) {
                    const birthDate = new Date(resident.date_of_birth);
                    const today = new Date();
                    age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    age = age + ' years old';
                }

                // Get profile image path
                const profileImage = resident.image ?
                    `../assets/images/user/${resident.image}` :
                    '../assets/images/user.png';

                // Format address
                const addressParts = [];
                if (resident.house_number) addressParts.push(resident.house_number);
                if (resident.street_name) addressParts.push(resident.street_name);
                if (resident.barangay) addressParts.push(resident.barangay);
                const fullAddress = addressParts.length > 0 ? addressParts.join(', ') : 'N/A';

                // Format status with color
                const getStatusClass = (status) => {
                    switch (status) {
                        case 'active': return 'status-active';
                        case 'inactive': return 'status-inactive';
                        case 'moved': return 'status-moved';
                        default: return 'status-inactive';
                    }
                };

                Swal.fire({
                    title: 'Resident Details',
                    html: `
            <div class="resident-details-container-simple">
              <!-- Profile Header -->
              <div class="profile-header-simple">
                <img src="${profileImage}" alt="Profile" class="resident-profile-image-simple">
                <div class="profile-info-simple">
                  <div class="resident-name-simple">
                    ${resident.first_name} ${resident.middle_name ? resident.middle_name + ' ' : ''}${resident.last_name}
                  </div>
                  <div class="resident-email-simple">${resident.email || 'N/A'}</div>
                  <div class="resident-status-badge-simple ${getStatusClass(resident.status)}">
                    ${(resident.status || 'inactive').toUpperCase()}
                  </div>
                </div>
              </div>
              
              <!-- Personal Information Section -->
              <div class="section-header-simple">Personal Information</div>
              <div class="resident-info-grid-simple">
                <div class="info-item-simple">
                  <div class="info-label-simple">First Name</div>
                  <div class="info-value-simple">${resident.first_name || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Middle Name</div>
                  <div class="info-value-simple">${resident.middle_name || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Last Name</div>
                  <div class="info-value-simple">${resident.last_name || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Date of Birth</div>
                  <div class="info-value-simple">${dateOfBirth}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Age</div>
                  <div class="info-value-simple">${age}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Gender</div>
                  <div class="info-value-simple">${resident.gender ? resident.gender.charAt(0).toUpperCase() + resident.gender.slice(1) : 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Civil Status</div>
                  <div class="info-value-simple">${resident.civil_status ? resident.civil_status.charAt(0).toUpperCase() + resident.civil_status.slice(1) : 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Occupation</div>
                  <div class="info-value-simple">${resident.occupation || 'N/A'}</div>
                </div>
              </div>
              
              <!-- Contact Information Section -->
              <div class="section-header-simple">Contact Information</div>
              <div class="resident-info-grid-simple">
                <div class="info-item-simple">
                  <div class="info-label-simple">Email Address</div>
                  <div class="info-value-simple">${resident.email || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Contact Number</div>
                  <div class="info-value-simple">${resident.contact_number || 'N/A'}</div>
                </div>
              </div>
              
              <!-- Address Information Section -->
              <div class="section-header-simple">Address Information</div>
              <div class="resident-info-grid-simple">
                <div class="info-item-simple">
                  <div class="info-label-simple">House Number</div>
                  <div class="info-value-simple">${resident.house_number || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Street Name</div>
                  <div class="info-value-simple">${resident.street_name || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Barangay</div>
                  <div class="info-value-simple">${resident.barangay || 'N/A'}</div>
                </div>
                
                <div class="info-item-simple full-width">
                  <div class="info-label-simple">Complete Address</div>
                  <div class="info-value-simple">${fullAddress}</div>
                </div>
              </div>
              
              <!-- System Information Section -->
              <div class="section-header-simple">System Information</div>
              <div class="resident-info-grid-simple">
                <div class="info-item-simple">
                  <div class="info-label-simple">Date Created</div>
                  <div class="info-value-simple">${createdDate}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Last Updated</div>
                  <div class="info-value-simple">${updatedDate}</div>
                </div>
                
                <div class="info-item-simple">
                  <div class="info-label-simple">Current Status</div>
                  <div class="info-value-simple">
                    <span class="status-badge-simple ${getStatusClass(resident.status)}">
                      ${(resident.status || 'inactive').charAt(0).toUpperCase() + (resident.status || 'inactive').slice(1)}
                    </span>
                  </div>
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
                    text: data.message || 'Unable to fetch resident details.',
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

function editResident(id) {
    // Show loading state while fetching data
    Swal.fire({
        title: 'Loading...',
        text: 'Fetching resident information.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(`./endpoints/get_resident.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const resident = data.resident;
                Swal.fire({
                    title: '<i class="fas fa-edit"></i> Edit Resident',
                    html: `
        <div class="swal-form-wide" style="padding-top: 10px">
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

            <!-- Personal Information Section -->
            <div class="section-title">Personal Information</div>
            
            <div class="form-group">
                <label class="form-label">First Name *</label>
                <input type="text" class="swal2-input" id="editFirstName" value="${resident.first_name}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input type="text" class="swal2-input" id="editMiddleName" value="${resident.middle_name || ''}">
            </div>
            <div class="form-group">
                <label class="form-label">Last Name *</label>
                <input type="text" class="swal2-input" id="editLastName" value="${resident.last_name}" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="swal2-input" id="editDateOfBirth" value="${resident.date_of_birth || ''}">
            </div>
            <div class="form-group">
                <label class="form-label">Gender</label>
                <select class="swal2-select" id="editGender">
                    <option value="">Select Gender</option>
                    <option value="male" ${resident.gender === 'male' ? 'selected' : ''}>Male</option>
                    <option value="female" ${resident.gender === 'female' ? 'selected' : ''}>Female</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Civil Status</label>
                <select class="swal2-select" id="editCivilStatus">
                    <option value="">Select Civil Status</option>
                    <option value="single" ${resident.civil_status === 'single' ? 'selected' : ''}>Single</option>
                    <option value="married" ${resident.civil_status === 'married' ? 'selected' : ''}>Married</option>
                    <option value="divorced" ${resident.civil_status === 'divorced' ? 'selected' : ''}>Divorced</option>
                    <option value="widowed" ${resident.civil_status === 'widowed' ? 'selected' : ''}>Widowed</option>
                </select>
            </div>

            <!-- Contact Information Section -->
            <div class="section-title">Contact Information</div>
            
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" class="swal2-input" id="editEmail" value="${resident.email}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Number *</label>
                <input type="tel" class="swal2-input" id="editContactNumber" value="${resident.contact_number}" required 
                       pattern="09[0-9]{9}" maxlength="11">
            </div>
            <div class="form-group">
                <label class="form-label">Occupation</label>
                <input type="text" class="swal2-input" id="editOccupation" value="${resident.occupation || ''}">
            </div>

            <!-- Password Section -->
            <div class="section-title">Change Password</div>
            
            <div class="form-group">
                <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="changePasswordToggle" style="width: auto; margin: 0;">
                    <span>Change Password</span>
                </label>
            </div>
            
                <div class="form-group" id="passwordFields" style="display: none;">
                    <label class="form-label">New Password *</label>
                    <div style="position: relative;">
                        <input type="password" class="swal2-input" id="editPassword" placeholder="Enter new password" style="padding-right: 40px;">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordResident('editPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                    </div>
                    <div id="password-strength-edit" style="margin-top: 5px; font-size: 12px;"></div>
                </div>
                
                <div class="form-group" id="password2Fields" style="display: none;">
                    <label class="form-label">Confirm Password *</label>
                    <div style="position: relative;">
                        <input type="password" class="swal2-input" id="editConfirmPassword" placeholder="Confirm new password" style="padding-right: 40px;">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordResident('editConfirmPassword', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                    </div>
                    <div id="password-match-edit" style="margin-top: 5px; font-size: 12px;"></div>
                </div>
                
                <div class="form-group" id="password3Fields" style="display: none;">
                    <small style="color: #666; font-size: 12px;">
                        Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long
                    </small>
                </div>

            <!-- Address Information Section -->
            <div class="section-title">Address Information</div>
            
            <div class="form-group">
                <label class="form-label">House Number</label>
                <input type="text" class="swal2-input" id="editHouseNumber" value="${resident.house_number || ''}">
            </div>
            <div class="form-group">
                <label class="form-label">Street Name</label>
                <input type="text" class="swal2-input" id="editStreetName" value="${resident.street_name || ''}">
            </div>
            <div class="form-group">
                <label class="form-label">Barangay</label>
                <input type="text" class="swal2-input" id="editBarangay" value="${resident.barangay || 'Baliwasan'}">
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="swal2-select" id="editStatus">
                    <option value="active" ${resident.status === 'active' ? 'selected' : ''}>Active</option>
                    <option value="inactive" ${resident.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    <option value="moved" ${resident.status === 'moved' ? 'selected' : ''}>Moved</option>
                </select>
            </div>
        </div>
      `,
                    customClass: {
                        popup: 'swal-wide'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Update Resident',
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

                        // Add input validation for contact number
                        const contactInput = document.getElementById('editContactNumber');
                        contactInput.addEventListener('input', function () {
                            this.value = this.value.replace(/[^0-9]/g, '');
                            if (this.value.length > 11) {
                                this.value = this.value.slice(0, 11);
                            }
                        });

                        // Password change toggle
                        const changePasswordToggle = document.getElementById('changePasswordToggle');
                        const passwordFields = document.getElementById('passwordFields');
                        const password2Fields = document.getElementById('password2Fields');
                        // const password3Fields = document.getElementById('password3Fields');
                        const passwordField = document.getElementById('editPassword');
                        const confirmPasswordField = document.getElementById('editConfirmPassword');
                        const passwordStrengthDiv = document.getElementById('password-strength-edit');
                        const passwordMatchDiv = document.getElementById('password-match-edit');

                        changePasswordToggle.addEventListener('change', function () {
                            if (this.checked) {
                                passwordFields.style.display = 'block';
                                password2Fields.style.display = 'block';
                                // password3Fields.style.display = 'block';
                            } else {
                                passwordFields.style.display = 'none';
                                password2Fields.style.display = 'none';
                                // password3Fields.style.display = 'none';
                                passwordField.value = '';
                                confirmPasswordField.value = '';
                                passwordStrengthDiv.innerHTML = '';
                                passwordMatchDiv.innerHTML = '';
                                passwordField.setCustomValidity('');
                                confirmPasswordField.setCustomValidity('');
                            }
                        });

                        // Password strength check
                        passwordField.addEventListener('input', function () {
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
                                    strengthColor = '#28a745'; // Green
                                    strengthText = '✓ Strong password';
                                    this.setCustomValidity('');
                                    break;
                                case 'Moderate':
                                    strengthColor = '#ffc107'; // Yellow
                                    strengthText = '⚠ Moderate password - add more complexity';
                                    this.setCustomValidity('Password is not strong enough');
                                    break;
                                case 'Weak':
                                    strengthColor = '#dc3545'; // Red
                                    strengthText = '✗ Weak password - needs improvement';
                                    this.setCustomValidity('Password is too weak');
                                    break;
                            }

                            passwordStrengthDiv.innerHTML = `<span style="color: ${strengthColor};">${strengthText}</span>`;

                            // Re-check confirm password when new password changes
                            if (confirmPasswordField.value) {
                                confirmPasswordField.dispatchEvent(new Event('input'));
                            }
                        });

                        // Confirm password match check
                        confirmPasswordField.addEventListener('input', function () {
                            const newPassword = passwordField.value;
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
                        const firstName = document.getElementById('editFirstName').value.trim();
                        const middleName = document.getElementById('editMiddleName').value.trim();
                        const lastName = document.getElementById('editLastName').value.trim();
                        const email = document.getElementById('editEmail').value.trim();
                        const contactNumber = document.getElementById('editContactNumber').value.trim();
                        const changePassword = document.getElementById('changePasswordToggle').checked;
                        const password = document.getElementById('editPassword').value;
                        const confirmPassword = document.getElementById('editConfirmPassword').value;
                        const dateOfBirth = document.getElementById('editDateOfBirth').value;
                        const gender = document.getElementById('editGender').value;
                        const civilStatus = document.getElementById('editCivilStatus').value;
                        const occupation = document.getElementById('editOccupation').value.trim();
                        const houseNumber = document.getElementById('editHouseNumber').value.trim();
                        const streetName = document.getElementById('editStreetName').value.trim();
                        const barangay = document.getElementById('editBarangay').value.trim();
                        const status = document.getElementById('editStatus').value;
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

                        // Validate password if changing
                        if (changePassword) {
                            if (!password || !confirmPassword) {
                                Swal.showValidationMessage('Both password fields are required when changing password');
                                return false;
                            }

                            const passwordStrength = checkPasswordStrength(password);
                            if (passwordStrength !== 'Strong') {
                                Swal.showValidationMessage('Password must be strong (at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and 8 characters long)');
                                return false;
                            }

                            if (password !== confirmPassword) {
                                Swal.showValidationMessage('Passwords do not match');
                                return false;
                            }
                        }

                        const formData = new FormData();
                        formData.append("id", id);
                        formData.append("firstName", firstName);
                        formData.append("middleName", middleName);
                        formData.append("lastName", lastName);
                        formData.append("email", email);
                        formData.append("contactNumber", contactNumber);
                        if (changePassword) {
                            formData.append("changePassword", "1");
                            formData.append("password", password);
                        }
                        formData.append("dateOfBirth", dateOfBirth);
                        formData.append("gender", gender);
                        formData.append("civilStatus", civilStatus);
                        formData.append("occupation", occupation);
                        formData.append("houseNumber", houseNumber);
                        formData.append("streetName", streetName);
                        formData.append("barangay", barangay || "Baliwasan");
                        formData.append("status", status);
                        if (image) formData.append("image", image);

                        return formData;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Updating Resident...',
                            text: 'Please wait while we process your request.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('./endpoints/edit_resident.php', {
                            method: 'POST',
                            body: result.value
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Resident updated successfully!',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message || 'Unable to update resident.',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Connection Error!',
                                    text: 'Unable to connect to server. Please try again.',
                                    confirmButtonText: 'OK'
                                });
                            });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Unable to fetch resident details.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error!',
                text: 'Unable to connect to server.',
                confirmButtonText: 'OK'
            });
        });
}
// Function to toggle password visibility for resident forms
function togglePasswordResident(inputId, toggleIcon) {
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

// Function to check password strength (if not already defined)
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
