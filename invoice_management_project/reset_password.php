<?php
session_start();
require_once 'config/database.php';

$tokenValid = false;
$tokenExpired = false;
$email = '';

// Check if token is provided in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Debug the token
    error_log("Verifying reset token: Token=$token, Current time=" . date('Y-m-d H:i:s'));
    
    try {
        // First check if the token exists at all
        $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Log the found token and its expiry
            error_log("Token found in database for user: " . $user['email'] . ", Expires: " . $user['reset_expires']);
            
            // Now check if it's still valid (not expired)
            $currentTime = date('Y-m-d H:i:s');
            if ($user['reset_expires'] > $currentTime) {
                $tokenValid = true;
                $email = $user['email'];
                error_log("Token is valid. Current time: $currentTime, Expiry time: " . $user['reset_expires']);
            } else {
                $tokenExpired = true;
                error_log("Token is expired. Current time: $currentTime, Expiry time: " . $user['reset_expires']);
            }
        } else {
            error_log("No user found with token: $token");
        }
    } catch (Exception $e) {
        error_log("Database error checking token: " . $e->getMessage());
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Debug
    error_log("Processing password reset form. Token: $token");
    
    // Validate password
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } else if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        try {
            // First check if the token exists and is valid
            $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update password and clear reset token
                $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
                $updateStmt->bind_param("si", $hashed_password, $user['user_id']);
                $updateStmt->execute();
                
                $_SESSION['success'] = "Your password has been successfully reset. Please login with your new password.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid or expired reset token.";
            }
        } catch (Exception $e) {
            error_log("Error resetting password: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred while resetting your password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Invoice System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5E5CE6;
            --primary-dark: #4B48BF;
            --secondary-color: #6c757d;
            --text-color: #333;
            --text-muted: #6c757d;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-container {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .reset-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
        }

        .logo-circle {
            width: 70px;
            height: 70px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .logo-circle i {
            font-size: 30px;
            color: var(--primary-color);
        }

        .reset-header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .reset-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .reset-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            height: 55px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(94, 92, 230, 0.15);
            outline: none;
        }

        .form-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .form-group:focus-within .form-icon {
            color: var(--primary-color);
        }

        .btn-reset {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            color: var(--white);
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(94, 92, 230, 0.2);
            transition: all 0.3s ease;
            height: 55px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-reset:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(94, 92, 230, 0.25);
        }

        .btn-reset:active {
            transform: translateY(0);
        }

        .back-to-login {
            color: var(--primary-color);
            text-decoration: none;
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-to-login:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .back-to-login i {
            margin-right: 0.5rem;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--error-color);
        }

        .instructions {
            margin-bottom: 2rem;
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .error-icon, .success-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .error-icon {
            color: var(--error-color);
        }

        .success-icon {
            color: var(--success-color);
        }
        
        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .progress {
            height: 5px;
            margin-top: 0.5rem;
            border-radius: 5px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 576px) {
            .reset-container {
                margin: 1rem;
            }
            
            .reset-header {
                padding: 2rem 1.5rem 1rem;
            }
            
            .reset-form {
                padding: 1.5rem;
            }
            
            .form-control, .btn-reset {
                height: 50px;
            }
        }
        
        /* Expired Link Styling */
        .expired-icon-container {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .expired-icon-container i {
            font-size: 32px;
            color: #e74c3c;
        }
        
        .expired-container h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        
        .expired-container p {
            color: #666;
            max-width: 350px;
            margin: 0 auto 20px;
            line-height: 1.6;
        }
        
        .btn-request-new {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-request-new:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="logo-circle">
                <i class="fas fa-key"></i>
            </div>
            <h1>Reset Password</h1>
            <p>Create a new password for your account</p>
        </div>
        
        <div class="reset-form">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(!$tokenValid && !isset($_POST['token'])): ?>
                <div class="text-center my-4">
                    <?php if($tokenExpired): ?>
                        <div class="expired-container text-center">
                            <div class="expired-icon-container mb-4">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Link Expired</h4>
                            <p>
                                Your password reset link has expired. For security reasons, these links are only valid for a limited time.
                            </p>
                            <p class="text-muted mb-4">
                                Please request a new password reset link.
                            </p>
                            <a href="forgot_password.php" class="btn-request-new">
                                <i class="fas fa-sync-alt"></i> Request New Link
                            </a>
                        </div>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle error-icon"></i>
                        <h4>Invalid Link</h4>
                        <p class="instructions">
                            The password reset link is invalid or has already been used.
                        </p>
                        <p class="text-muted small mb-4">
                            Please request a new password reset link.
                        </p>
                        <a href="forgot_password.php" class="btn btn-reset">
                            <i class="fas fa-redo me-2"></i>Request New Link
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="instructions">
                    Enter a new password for your account. Make sure it's secure and easy to remember.
                </p>
                
                <form action="reset_password.php" method="POST" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
                    
                    <div class="form-group">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="New password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div id="password-strength" class="password-requirements">
                            <div class="progress">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="strength-text mt-1 d-block">Password must be at least 8 characters long</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-reset" id="resetBtn">
                        <i class="fas fa-check-circle me-2"></i>Reset Password
                    </button>
                </form>
                
                <a href="login.php" class="back-to-login">
                    <i class="fas fa-arrow-left"></i>Back to Login
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const passwordField = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const resetBtn = document.getElementById('resetBtn');
        
        if (passwordInput && confirmInput && resetBtn) {
            const strengthBar = document.querySelector('#password-strength .progress-bar');
            const strengthText = document.querySelector('#password-strength .strength-text');
            
            function validatePassword() {
                // Basic validation
                const isValidLength = passwordInput.value.length >= 8;
                const doPasswordsMatch = passwordInput.value === confirmInput.value;
                
                // Password strength calculation
                let strength = 0;
                const password = passwordInput.value;
                
                if (password.length >= 8) strength += 25;
                if (password.match(/[a-z]+/)) strength += 25;
                if (password.match(/[A-Z]+/)) strength += 25;
                if (password.match(/[0-9]+/) || password.match(/[^a-zA-Z0-9]+/)) strength += 25;
                
                // Update strength indicator
                strengthBar.style.width = strength + '%';
                
                // Change color based on strength
                strengthBar.className = 'progress-bar';
                if (strength <= 25) {
                    strengthBar.classList.add('bg-danger');
                    strengthText.textContent = 'Weak password';
                    strengthText.className = 'strength-text mt-1 d-block text-danger';
                } else if (strength <= 50) {
                    strengthBar.classList.add('bg-warning');
                    strengthText.textContent = 'Fair password';
                    strengthText.className = 'strength-text mt-1 d-block text-warning';
                } else if (strength <= 75) {
                    strengthBar.classList.add('bg-info');
                    strengthText.textContent = 'Good password';
                    strengthText.className = 'strength-text mt-1 d-block text-info';
                } else {
                    strengthBar.classList.add('bg-success');
                    strengthText.textContent = 'Strong password';
                    strengthText.className = 'strength-text mt-1 d-block text-success';
                }
                
                // Add validation feedback
                if (passwordInput.value.length > 0) {
                    if (isValidLength) {
                        passwordInput.classList.add('is-valid');
                        passwordInput.classList.remove('is-invalid');
                    } else {
                        passwordInput.classList.add('is-invalid');
                        passwordInput.classList.remove('is-valid');
                    }
                }
                
                if (confirmInput.value.length > 0) {
                    if (doPasswordsMatch) {
                        confirmInput.classList.add('is-valid');
                        confirmInput.classList.remove('is-invalid');
                    } else {
                        confirmInput.classList.add('is-invalid');
                        confirmInput.classList.remove('is-valid');
                    }
                }
                
                // Enable/disable submit button based on validation
                resetBtn.disabled = !(isValidLength && doPasswordsMatch);
            }
            
            passwordInput.addEventListener('input', validatePassword);
            confirmInput.addEventListener('input', validatePassword);
            
            // Initial validation check
            validatePassword();
        }
    });
    </script>
</body>
</html> 