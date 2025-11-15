<?php session_start();

require_once("./conn/conn.php");
// Fetch system settings for display
$settingsQuery = $conn->query("SELECT system_name, system_logo, login_bg FROM system_settings LIMIT 1");
$systemSettings = $settingsQuery->fetch_assoc();

$systemName = $systemSettings['system_name'] ?? 'BMS';
$systemLoginLogo = !empty($systemSettings['system_logo'])
    ? "./assets/images/settings/" . $systemSettings['system_logo']
    : "./assets/logo/bms.png";

$loginBg = !empty($systemSettings['login_bg'])
    ? "./assets/images/settings/" . $systemSettings['login_bg']
    : "./assets/images/settings/bg.jpg";
?>
<!DOCTYPE html>
<html lang="en">
<style>
    body {
        background-image: url('<?= htmlspecialchars($loginBg) ?>');
    }

    .swal-verification-form {
        text-align: left;
    }

    .swal-verification-form .form-group {
        margin-bottom: 20px;
    }

    .swal-verification-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }

    .swal-verification-form select,
    .swal-verification-form input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }

    .swal-verification-form .required {
        color: red;
    }

    .file-preview {
        margin-top: 10px;
        max-width: 200px;
        max-height: 200px;
    }

    .file-preview img {
        max-width: 100%;
        max-height: 100%;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
</style>

<head>
    <?php include './components/header_links.php'; ?>
    <link rel="icon" href="<?= htmlspecialchars($systemLoginLogo) ?>" type="image/icon type">
    <link rel="stylesheet" href="./css/login.css">
</head>

<body>
    <div class="container">
        <div class="forms-container">
            <!-- SignIn Form -->
            <div class="form-control signin-form">
                <form action="./conn/endpoint/login.php" method="POST">
                    <h2>Sign in</h2>
                    <input type="email" name="email" placeholder="Email" required />
                    <div class="input-wrapper">
                        <input type="password" id="signin-password" name="password" placeholder="Password" required />
                        <span class="toggle-password">
                            <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="forgot-password-link">
                        <a href="javascript:void(0);" onclick="forgotPassword()">Forgot Password?</a>
                    </div>
                    <button type="submit">Sign in</button>
                </form>
            </div>

            <!-- SignUp Form -->
            <div class="form-control signup-form">
                <form action="./conn/endpoint/register.php" method="POST">
                    <h2>Create an Account</h2>
                    <div class="input-wrapper flex-wrapper">
                        <input type="text" name="student_firstname" placeholder="First Name" required />
                        <input type="text" name="student_middle" placeholder="M.I." />
                    </div>
                    <input type="text" name="student_lastname" placeholder="Last Name" required />
                    <input type="email" id="signup-email" name="email" placeholder="Email" required />
                    <input type="number" id="signup-contact" name="contact" placeholder="Contact Number" required />
                    <div class="validation-text" id="signup_contact_validation"></div>

                    <!-- Address Fields -->
                    <div class="input-wrapper flex-wrapper-address">
                        <input type="text" name="house_number" id="house-number" placeholder="House Number" required />
                        <input type="text" name="street_name" id="street-name" placeholder="Street Name" required />
                    </div>
                    <input style="display: none;" type="text" name="barangay" id="barangay" value="Baliwasan" />

                    <div class="input-wrapper">
                        <input type="password" id="signup-password" name="password" placeholder="Password" required />
                        <span class="toggle-password">
                            <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" id="signup-confirm-password" name="confirm-password"
                            placeholder="Confirm password" required />
                        <span class="toggle-password">
                            <i style="margin-bottom: 15px;" class="fas fa-eye"></i>
                        </span>
                    </div>
                    <button type="submit" id="signup-button" disabled>Sign up</button>
                    <div class="password-strength" id="signup_password_strength"></div>
                    <div class="password-strength" id="signup_instruction_text"></div>
                </form>
            </div>
        </div>
        <div class="intros-container">
            <div class="intro-control signin-intro">
                <div class="intro-control__inner">
                    <img src="<?= htmlspecialchars($systemLoginLogo) ?>">
                    <button style="font-weight: bold;" id="signup-btn">No account yet? Sign up.</button>
                </div>
            </div>
            <div class="intro-control signup-intro">
                <div class="intro-control__inner">
                    <img src="<?= htmlspecialchars($systemLoginLogo) ?>">
                    <button style="font-weight: bold;" id="signin-btn">Already have an account? Sign in.</button>
                </div>
            </div>
        </div>
    </div>

    <div id="loader" style="display:none;"></div>

    <script src="./js/script.js"></script>
    <?php include './components/cdn_scripts.php'; ?>

    <script>
        <?php if (isset($_GET['register']) && $_GET['register'] === 'verify'): ?>
            window.onload = function () {
                showVerificationModal();
            };
        <?php elseif (isset($_GET['login']) && $_GET['login'] === 'pending'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Pending Approval',
                    text: '<?php echo isset($_SESSION['login_error']) ? $_SESSION['login_error'] : "Your account is pending approval. Please wait for an administrator to review your registration."; ?>',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
            <?php unset($_SESSION['login_error']);
            unset($_SESSION['login_error_type']); ?>
        <?php elseif (isset($_GET['login']) && $_GET['login'] === 'rejected'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Account Rejected',
                    text: '<?php echo isset($_SESSION['login_error']) ? $_SESSION['login_error'] : "Your account registration has been rejected. Please contact the administrator for more information."; ?>',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
            <?php unset($_SESSION['login_error']);
            unset($_SESSION['login_error_type']); ?>
        <?php elseif (isset($_GET['login']) && $_GET['login'] === 'error'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: '<?php echo isset($_SESSION['login_error']) ? $_SESSION['login_error'] : "Invalid email or password"; ?>',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
            <?php unset($_SESSION['login_error']); ?>
        <?php elseif (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Complete',
                    text: 'Please wait for admin approval to access your account.',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
            <?php unset($_SESSION['register_success']); ?>
        <?php elseif (isset($_GET['register']) && $_GET['register'] === 'error'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: '<?php echo isset($_SESSION['register_error']) ? $_SESSION['register_error'] : "Something went wrong. Please try again."; ?>',
                    confirmButtonText: 'OK',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                }).then(() => {
                    document.querySelector('.container').classList.add('sign-up-mode');
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
            <?php unset($_SESSION['register_error']); ?>
        <?php elseif (isset($_GET['auth']) && $_GET['auth'] === 'error'): ?>
            window.onload = function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Unauthorized Access',
                    text: 'You must log in to access that page.',
                    confirmButtonText: 'Confirm',
                    didOpen: () => {
                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                    },
                });
                window.history.replaceState({}, document.title, window.location.pathname);
            };
        <?php endif; ?>

        function showVerificationModal() {
            const govIdTypes = [
                'Philippine Passport',
                'National ID (PhilSys ID)',
                'Driver\'s License',
                'SSS ID / UMID Card',
                'GSIS eCard',
                'PRC ID',
                'Postal ID',
                'Voter\'s ID / Voter\'s Certification',
                'Senior Citizen ID',
                'PWD ID',
                'OFW / OWWA ID',
                'PhilHealth ID',
                'Barangay ID'
            ];

            const optionsHtml = govIdTypes.map(id => `<option value="${id}">${id}</option>`).join('');

            Swal.fire({
                title: 'Complete Your Registration',
                html: `
                    <form id="verification-form" class="swal-verification-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="selfie-input">Take a Selfie <span class="required">*</span></label>
                            <input type="file" id="selfie-input" name="selfie_image" accept="image/*" capture="user" required>
                            <div id="selfie-preview" class="file-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="gov-id-type">Government ID Type <span class="required">*</span></label>
                            <select id="gov-id-type" name="gov_id_type" required>
                                <option value="">Select ID Type</option>
                                ${optionsHtml}
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gov-id-input">Upload Government ID <span class="required">*</span></label>
                            <input type="file" id="gov-id-input" name="gov_id_image" accept="image/*" required>
                            <div id="gov-id-preview" class="file-preview"></div>
                        </div>
                    </form>
                `,
                showCancelButton: false,
                confirmButtonText: 'Submit',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                    document.body.classList.remove("swal2-shown", "swal2-height-auto");

                    // Preview selfie
                    document.getElementById('selfie-input').addEventListener('change', function (e) {
                        const preview = document.getElementById('selfie-preview');
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                preview.innerHTML = `<img src="${e.target.result}" alt="Selfie Preview">`;
                            };
                            reader.readAsDataURL(file);
                        }
                    });

                    // Preview government ID
                    document.getElementById('gov-id-input').addEventListener('change', function (e) {
                        const preview = document.getElementById('gov-id-preview');
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                preview.innerHTML = `<img src="${e.target.result}" alt="ID Preview">`;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                },
                preConfirm: () => {
                    const selfie = document.getElementById('selfie-input').files[0];
                    const govIdType = document.getElementById('gov-id-type').value;
                    const govId = document.getElementById('gov-id-input').files[0];

                    if (!selfie) {
                        Swal.showValidationMessage('Please take a selfie');
                        return false;
                    }
                    if (!govIdType) {
                        Swal.showValidationMessage('Please select a government ID type');
                        return false;
                    }
                    if (!govId) {
                        Swal.showValidationMessage('Please upload your government ID');
                        return false;
                    }

                    return { selfie, govIdType, govId };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Uploading...',
                        text: 'Please wait while we process your verification documents.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create FormData and submit
                    const formData = new FormData();
                    formData.append('selfie_image', result.value.selfie);
                    formData.append('gov_id_type', result.value.govIdType);
                    formData.append('gov_id_image', result.value.govId);

                    fetch('./conn/endpoint/register.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (response.redirected) {
                                window.location.href = response.url;
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload Failed',
                                text: 'Something went wrong. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        }

        function showModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Signup form event listener
        document.querySelector('form[action="./conn/endpoint/register.php"]').addEventListener('submit', function (e) {
            document.getElementById('loader').style.display = "block";
            document.getElementById('signup-button').disabled = true;
            document.querySelector('.signup-form').style.opacity = '0.5';
        });

        const signupEmailField = document.getElementById('signup-email');
        const signupPasswordField = document.getElementById('signup-password');
        const signupConfirmPasswordField = document.getElementById('signup-confirm-password');
        const signupStrengthIndicator = document.getElementById('signup_password_strength');
        const signupInstructionText = document.getElementById('signup_instruction_text');
        const signupButton = document.getElementById('signup-button');

        // Email validation
        signupEmailField.addEventListener('input', function () {
            const email = signupEmailField.value;
            const emailRegex = /^[^\s@]+@gmail\.com$/;
            if (emailRegex.test(email)) {
                signupEmailField.setCustomValidity('');
                signupInstructionText.textContent = '';
                signupButton.disabled = false;
            } else {
                signupEmailField.setCustomValidity('Email must be a valid Gmail address');
                signupInstructionText.style.color = '#8B0000';
                signupInstructionText.textContent = 'Please enter a valid Gmail address.';
                signupButton.disabled = true;
            }
        });

        const signupContactField = document.getElementById('signup-contact');
        const signupContactValidation = document.getElementById('signup_contact_validation');

        // Contact validation
        signupContactField.addEventListener('input', function () {
            const contact = signupContactField.value;
            const contactRegex = /^09\d{9}$/;

            if (contactRegex.test(contact)) {
                signupContactField.setCustomValidity('');
                signupContactValidation.textContent = '';
                if (signupConfirmPasswordField.value === signupPasswordField.value && signupEmailField.validity.valid) {
                    signupButton.disabled = false;
                }
            } else {
                signupContactField.setCustomValidity('Invalid contact number');
                signupContactValidation.style.color = '#8B0000';
                signupContactValidation.textContent = 'Contact must start with 09 and be exactly 11 digits.';
                signupButton.disabled = true;
            }
        });

        // Confirm password match
        signupConfirmPasswordField.addEventListener('input', function () {
            if (signupConfirmPasswordField.value !== signupPasswordField.value) {
                signupConfirmPasswordField.setCustomValidity('Passwords do not match');
                signupButton.disabled = true;
            } else {
                signupConfirmPasswordField.setCustomValidity('');
                if (signupEmailField.validity.valid && signupContactField.validity.valid) {
                    signupButton.disabled = false;
                } else {
                    signupButton.disabled = true;
                }
            }
        });

        function forgotPassword() {
            Swal.fire({
                title: 'Reset Password',
                html: `
            <div class="swal-form">
                <div class="form-group">
                    <label for="fp-email" class="form-label">Email Address *</label>
                    <input type="email" id="fp-email" class="swal2-input" placeholder="Enter your email address" required>
                </div>
                
                <div class="form-group">
                    <label for="fp-newpass" class="form-label">New Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="fp-newpass" class="swal2-input" placeholder="Enter new password" required style="padding-right: 40px;">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordForgot('fp-newpass', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                    </div>
                    <div id="fp-password-strength" style="margin-top: 5px; font-size: 12px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="fp-confirmpass" class="form-label">Confirm New Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="fp-confirmpass" class="swal2-input" placeholder="Confirm new password" required style="padding-right: 40px;">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordForgot('fp-confirmpass', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                    </div>
                    <div id="fp-password-match" style="margin-top: 5px; font-size: 12px;"></div>
                </div>
            </div>
        `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Reset Password',
                cancelButtonText: 'Cancel',
                didOpen: () => {
                    document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                    document.body.classList.remove("swal2-shown", "swal2-height-auto");
                },
                preConfirm: () => {
                    const email = document.getElementById('fp-email').value.trim();
                    const newPass = document.getElementById('fp-newpass').value;
                    const confirmPass = document.getElementById('fp-confirmpass').value;

                    if (!email || !newPass || !confirmPass) {
                        Swal.showValidationMessage('All fields are required');
                        return false;
                    }

                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        Swal.showValidationMessage('Please enter a valid email address');
                        return false;
                    }

                    if (newPass !== confirmPass) {
                        Swal.showValidationMessage('Passwords do not match');
                        return false;
                    }

                    return { email: email, password: newPass };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Resetting Password...',
                        text: 'Please wait while we process your request.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('./conn/endpoint/forgotpassword.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(result.value)
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Your password has been reset successfully!',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    didOpen: () => {
                                        document.documentElement.classList.remove("swal2-shown", "swal2-height-auto");
                                        document.body.classList.remove("swal2-shown", "swal2-height-auto");
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message || 'Something went wrong. Please try again.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                title: 'Error',
                                text: 'Unable to reset password. Please check your connection and try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                }
            });
        }

        function togglePasswordForgot(inputId, toggleIcon) {
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
    </script>
</body>

</html>