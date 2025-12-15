<?php 
session_start();
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
<head>
    <?php include './components/header_links.php'; ?>
    <link rel="icon" href="<?= htmlspecialchars($systemLoginLogo) ?>" type="image/icon type">
    <title>Reset Password - <?= htmlspecialchars($systemName) ?></title>
    <style>
        body {
            background-image: url('<?= htmlspecialchars($loginBg) ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: "Poppins", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .reset-container {
            background-color: #fff;
            width: 760px;
            max-width: 100vw;
            min-height: 580px;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
        }

        .reset-form-section {
            width: 50%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .reset-intro-section {
            width: 50%;
            background: linear-gradient(170deg, #00247c, #00247c);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }

        .reset-intro-section img {
            max-width: 60%;
            height: auto;
            margin-bottom: 20px;
        }

        .reset-intro-section h3 {
            margin: 15px 0;
            font-size: 1.5rem;
        }

        .reset-intro-section p {
            margin: 10px 0;
            opacity: 0.9;
        }

        .reset-form-section h2 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: none;
            background-color: #efefef;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            background-color: #e6e6e6;
        }

        .form-group input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            color: #999;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .toggle-password i {
            font-size: 18px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .password-match {
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .password-match.success {
            color: #28a745;
        }

        .password-match.error {
            color: #dc3545;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background-color: #00247c;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #001a5c;
        }

        .btn-submit:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #00247c;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .error-input {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
        }

        @media screen and (max-width: 768px) {
            .reset-container {
                flex-direction: column;
                height: auto;
            }

            .reset-intro-section {
                width: 100%;
                order: 1;
                padding: 30px 20px;
            }

            .reset-intro-section img {
                max-width: 40%;
            }

            .reset-form-section {
                width: 100%;
                order: 2;
                padding: 30px 20px;
            }

            .reset-form-section h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <!-- Form Section -->
        <div class="reset-form-section">
            <h2>Reset Password</h2>
            <form id="reset-form">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="otp">OTP Code *</label>
                    <input type="text" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="[0-9]{6}" required>
                    <span class="help-text">Check your email for the OTP code</span>
                </div>
                
                <div class="form-group">
                    <label for="new-password">New Password *</label>
                    <div class="input-wrapper">
                        <input type="password" id="new-password" name="password" placeholder="Enter new password" required>
                        <span class="toggle-password" onclick="togglePassword('new-password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password">Confirm Password *</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password" required>
                        <span class="toggle-password" onclick="togglePassword('confirm-password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <span class="password-match" id="password-match"></span>
                </div>
                
                <button type="submit" class="btn-submit" id="submit-btn">Reset Password</button>
            </form>
            
            <div class="back-link">
                <a href="index.php">← Back to Login</a>
            </div>
        </div>

        <!-- Intro Section -->
        <div class="reset-intro-section">
            <img src="<?= htmlspecialchars($systemLoginLogo) ?>" alt="<?= htmlspecialchars($systemName) ?>">
            <h3>Secure Password Reset</h3>
            <p>Enter the OTP code sent to your email to create a new password for your account.</p>
            <p style="margin-top: 20px; font-size: 13px;">
                <i class="fas fa-shield-alt"></i> Your security is our priority
            </p>
        </div>
    </div>

    <?php include './components/cdn_scripts.php'; ?>
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }

        // Password match checker
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('new-password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.textContent = '';
                matchDiv.className = 'password-match';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.textContent = 'Passwords match ✓';
                matchDiv.className = 'password-match success';
            } else {
                matchDiv.textContent = 'Passwords do not match';
                matchDiv.className = 'password-match error';
            }
        });

        // Also check when new password changes
        document.getElementById('new-password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm-password').value;
            if (confirmPassword.length > 0) {
                document.getElementById('confirm-password').dispatchEvent(new Event('input'));
            }
        });

        // OTP input validation (numbers only)
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Form submission
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const otp = document.getElementById('otp').value.trim();
            const password = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const submitBtn = document.getElementById('submit-btn');
            
            // Validations
            if (!email || !otp || !password || !confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all fields',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if (otp.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid OTP',
                    text: 'OTP must be 6 digits',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            if (password.length < 3) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Too Short',
                    text: 'Password must be at least 3 characters long',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            // Submit form
            fetch('./conn/endpoint/verify_otp_reset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, otp, password })
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Your password has been reset successfully!',
                        confirmButtonText: 'Login Now'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to reset password. Please try again.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to process your request. Please try again.',
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</body>
</html>