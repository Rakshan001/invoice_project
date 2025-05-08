<?php
session_start();
require_once 'config/database.php';
require_once 'includes/mail_functions.php';

// Initialize variables
$email = '';
$otp_sent = false;

// Check if user is redirected from login
if (isset($_SESSION['temp_email'])) {
    $email = $_SESSION['temp_email'];
    
    // Check if the form has not been submitted yet
    if (!isset($_POST['verify_email']) && !isset($_POST['verify_otp'])) {
        // Generate and send OTP automatically
        $otp = generateOTP();
        
        // Store OTP in database
        if (storeOTP($conn, $email, $otp)) {
            // Send OTP via email
            if (sendVerificationOTP($email, $otp)) {
                $otp_sent = true;
                $success = "OTP has been sent to your email. Please check and enter below.";
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "Failed to generate OTP. Please try again.";
        }
    }
}

// Handle send OTP button
if (isset($_POST['verify_email'])) {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() == 0) {
        $error = "Email not found. Please register first.";
    } else {
        // Generate and send OTP
        $otp = generateOTP();
        
        // Store user email temporarily in session
        $_SESSION['temp_email'] = $email;
        
        // Store OTP in database
        if (storeOTP($conn, $email, $otp)) {
            // Send OTP via email
            if (sendVerificationOTP($email, $otp)) {
                $otp_sent = true;
                $success = "OTP has been sent to your email. Please check and enter below.";
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "Failed to generate OTP. Please try again.";
        }
    }
}

// Verify OTP
if (isset($_POST['verify_otp'])) {
    $email = $_SESSION['temp_email'];
    $otp = $_POST['otp'];
    
    // Verify OTP
    if (verifyOTP($conn, $email, $otp)) {
        // Get user details for session
        $stmt = $conn->prepare("SELECT user_id, email, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        
        // Clear temporary session data
        unset($_SESSION['temp_email']);
        
        $_SESSION['success'] = "Email verified successfully! You are now logged in.";
        header("Location: landing.php");
        exit();
    } else {
        $error = "Invalid or expired OTP. Please try again.";
        $otp_sent = true; // Keep OTP form visible
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Invoice Management System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --accent-color: #6c63ff;
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
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-container {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .verify-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .verify-header .logo {
            width: 70px;
            height: 70px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .verify-header .logo i {
            font-size: 28px;
            color: var(--primary-color);
        }

        .verify-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .verify-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .verify-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-control {
            height: 55px;
            border-radius: 10px;
            border: 2px solid #e1e1e1;
            padding: 0.75rem 1.2rem 0.75rem 3rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 84, 200, 0.1);
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

        .btn {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--white);
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 55px;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .login-link {
            color: var(--text-muted);
            text-decoration: none;
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .login-link:hover {
            color: var(--primary-color);
        }

        .login-link i {
            margin-right: 0.5rem;
        }

        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .otp-input {
            letter-spacing: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
        }

        @media (max-width: 576px) {
            .verify-container {
                border-radius: 15px;
            }
            
            .verify-header {
                padding: 2rem 1.5rem 1rem;
            }
            
            .verify-form {
                padding: 1.5rem;
            }
            
            .form-control, .btn {
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="logo">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h1>Verify Your Email</h1>
            <p>Enter the verification code sent to your email</p>
        </div>
        
        <div class="verify-form">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$otp_sent): ?>
                <!-- Email Form -->
                <form method="POST" action="">
                    <div class="form-group">
                        <i class="fas fa-envelope form-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="<?php echo $email; ?>" required>
                    </div>
                    
                    <button type="submit" name="verify_email" class="btn mb-3">
                        <i class="fas fa-paper-plane me-2"></i>Send Verification Code
                    </button>
                </form>
            <?php else: ?>
                <!-- OTP Verification Form -->
                <form method="POST" action="">
                    <div class="text-center mb-3">
                        <p>We've sent a verification code to <strong><?php echo $email; ?></strong></p>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-key form-icon"></i>
                        <input type="text" class="form-control otp-input" id="otp" name="otp" placeholder="Enter OTP" maxlength="6" required autocomplete="off">
                    </div>
                    
                    <button type="submit" name="verify_otp" class="btn mb-3">
                        <i class="fas fa-check-circle me-2"></i>Verify Email
                    </button>
                    
                    <div class="text-center mt-3">
                        <small>Didn't receive the code? <a href="verify_email.php">Try again</a></small>
                    </div>
                </form>
            <?php endif; ?>
            
            <a href="login.php" class="login-link">
                <i class="fas fa-arrow-left"></i>Back to Login
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus OTP input if available
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.focus();
            }
        });
    </script>
</body>
</html> 