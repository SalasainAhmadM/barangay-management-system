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

        <?php if (isset($_GET['login']) && $_GET['login'] === 'error'): ?>
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
                    title: 'Registration Successful',
                    text: '<?php echo isset($_SESSION['register_success']) ? $_SESSION['register_success'] : "Your account has been created successfully!"; ?>',
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
                    // Automatically switch to signup form after error
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


        function showModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Signup form event listener
        document.querySelector('form[action="./conn/endpoint/register.php"]').addEventListener('submit', function (e) {
            // Show the loader
            document.getElementById('loader').style.display = "block";

            // Disable the signup button to prevent multiple submissions
            document.getElementById('signup-button').disabled = true;

            // Optional: Hide the form while the loader is shown (if desired)
            document.querySelector('.signup-form').style.opacity = '0.5';
        });

        const signupEmailField = document.getElementById('signup-email');
        const signupPasswordField = document.getElementById('signup-password');
        const signupConfirmPasswordField = document.getElementById('signup-confirm-password');
        const signupStrengthIndicator = document.getElementById('signup_password_strength');
        const signupInstructionText = document.getElementById('signup_instruction_text');
        const signupButton = document.getElementById('signup-button');

        // Email validation event listener
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

        // Contact number validation
        signupContactField.addEventListener('input', function () {
            const contact = signupContactField.value;
            const contactRegex = /^09\d{9}$/; // Must start with 09 and be 11 digits long

            if (contactRegex.test(contact)) {
                signupContactField.setCustomValidity('');
                signupContactValidation.textContent = '';
                // Re-enable button only if all other validations are also good
                if (
                    checkPasswordStrength(signupPasswordField.value) === 'Strong' &&
                    signupConfirmPasswordField.value === signupPasswordField.value &&
                    signupEmailField.validity.valid
                ) {
                    signupButton.disabled = false;
                }
            } else {
                signupContactField.setCustomValidity('Invalid contact number');
                signupContactValidation.style.color = '#8B0000';
                signupContactValidation.textContent = 'Contact must start with 09 and be exactly 11 digits.';
                signupButton.disabled = true;
            }
        });

        // Password validation event listener
        // signupPasswordField.addEventListener('input', function () {
        //     const password = signupPasswordField.value;
        //     const strength = checkPasswordStrength(password);
        //     signupStrengthIndicator.textContent = `Password Strength: ${strength}`;

        //     if (strength === 'Weak' || strength === 'Moderate') {
        //         signupStrengthIndicator.style.color = '#8B0000';
        //     } else {
        //         signupStrengthIndicator.style.color = '#00247c';
        //     }

        //     if (password.length >= 8 && strength !== 'Strong') {
        //         signupInstructionText.style.color = '#8B0000';
        //         signupInstructionText.textContent = 'Password must include at least 2 numbers, 5 lowercase letters, and 1 uppercase letter.';
        //         signupButton.disabled = true;
        //     } else if (strength === 'Strong' && signupConfirmPasswordField.value === password && signupEmailField.validity.valid) {
        //         signupInstructionText.textContent = '';
        //         signupButton.disabled = false;
        //     } else {
        //         signupInstructionText.textContent = '';
        //         signupButton.disabled = true;
        //     }
        // });

        // Confirm password match check
        signupConfirmPasswordField.addEventListener('input', function () {
            if (signupConfirmPasswordField.value !== signupPasswordField.value) {
                signupConfirmPasswordField.setCustomValidity('Passwords do not match');
                signupButton.disabled = true;
            } else {
                signupConfirmPasswordField.setCustomValidity('');
                // Enable sign-up button once passwords match (no need for strong password check)
                if (signupEmailField.validity.valid && signupContactField.validity.valid) {
                    signupButton.disabled = false;
                } else {
                    signupButton.disabled = true;
                }
            }
        });


        // function checkPasswordStrength(password) {
        //     const regexStrong = /(?=(.*[a-z]){5,})(?=.*[A-Z])(?=(.*[0-9]){2,})/;

        //     if (password.length >= 8 && regexStrong.test(password)) {
        //         return 'Strong';
        //     } else if (password.length >= 6) {
        //         return 'Moderate';
        //     } else {
        //         return 'Weak';
        //     }
        // }

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
                
                <div class="form-group">
                    <small style="color: #666; font-size: 12px;">
                        Password must contain at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and be at least 8 characters long
                    </small>
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

                    // Get password field elements
                    const newPasswordField = document.getElementById('fp-newpass');
                    const confirmPasswordField = document.getElementById('fp-confirmpass');
                    const passwordStrengthDiv = document.getElementById('fp-password-strength');
                    const passwordMatchDiv = document.getElementById('fp-password-match');

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
                    const email = document.getElementById('fp-email').value.trim();
                    const newPass = document.getElementById('fp-newpass').value;
                    const confirmPass = document.getElementById('fp-confirmpass').value;

                    if (!email || !newPass || !confirmPass) {
                        Swal.showValidationMessage('All fields are required');
                        return false;
                    }

                    // Validate email format
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        Swal.showValidationMessage('Please enter a valid email address');
                        return false;
                    }

                    // Validate password strength
                    const passwordStrength = checkPasswordStrength(newPass);
                    if (passwordStrength !== 'Strong') {
                        Swal.showValidationMessage('Password must be strong (at least 5 lowercase letters, 1 uppercase letter, 2 numbers, and 8 characters long)');
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
                    // Show loading state
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

        // Function to toggle password visibility for forgot password
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