<?php
session_start();
require_once 'config/database.php';
require_once 'includes/mail_functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize variable to track if email was sent
$emailSent = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        try {
            // First, check if the reset_token and reset_expires columns exist
            $checkColumnsQuery = "SHOW COLUMNS FROM users LIKE 'reset_token'";
            $checkResult = $conn->query($checkColumnsQuery);
            
            // If the columns don't exist, add them
            if ($checkResult->num_rows === 0) {
                $alterQuery = "ALTER TABLE users 
                               ADD COLUMN reset_token VARCHAR(100) NULL,
                               ADD COLUMN reset_expires DATETIME NULL";
                $conn->query($alterQuery);
                error_log("Added reset_token and reset_expires columns to users table");
            }
            
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            // Set expiry time to 24 hours from now
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store token in database
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $token, $expires, $email);
            $updateStmt->execute();
            
            // Log for debugging
            error_log("Reset token created: Token=$token, Expires=$expires, Current time=" . date('Y-m-d H:i:s'));
            
            // Generate reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            
            // Send email with reset link
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your-email@gmail.com'; // Replace with your email
                $mail->Password = 'your-app-password'; // Replace with your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Recipients
                $mail->setFrom('your-email@gmail.com', 'Your Name');
                $mail->addAddress($email);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>You have requested to reset your password. Click the link below to proceed:</p>
                    <p><a href='$resetLink'>Reset Password</a></p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                
                $mail->send();
                $emailSent = true;
                $_SESSION['success'] = "Password reset instructions have been sent to your email.";
            } catch (Exception $e) {
                error_log("Email sending failed: " . $mail->ErrorInfo);
                $_SESSION['error'] = "Failed to send reset email. Please try again later.";
            }
        } catch (Exception $e) {
            error_log("Error in password reset process: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred. Please try again later.";
        }
    } else {
        // For security, show the same message even if email doesn't exist
        $_SESSION['success'] = "If an account exists with this email, password reset instructions have been sent.";
        $emailSent = true; // Set to true to show success message even if email doesn't exist
    }
    
    if ($emailSent) {
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Invoice System</title>
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

        .success-icon {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <div class="logo-circle">
                <i class="fas fa-key"></i>
            </div>
            <h1>Forgot Password</h1>
            <p>Enter your email to reset your password</p>
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

            <?php if($emailSent): ?>
                <div class="text-center my-4">
                    <i class="fas fa-envelope-open-text success-icon"></i>
                    <h4>Email Sent!</h4>
                    <p class="instructions">
                        We've sent a password reset link to your email address. Please check your inbox and follow the instructions to reset your password.
                    </p>
                    <p class="text-muted small">
                        If you don't receive an email within a few minutes, check your spam folder.
                    </p>
                    <a href="login.php" class="btn btn-reset mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Return to Login
                    </a>
                </div>
            <?php else: ?>
                <p class="instructions">
                    Enter the email address associated with your account, and we'll send you a link to reset your password.
                </p>
                
                <form action="forgot_password.php" method="POST">
                    <div class="form-group">
                        <i class="fas fa-envelope form-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Your email address" required>
                    </div>
                    
                    <button type="submit" class="btn btn-reset">
                        <i class="fas fa-paper-plane me-2"></i>Send Reset Link
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
</body>
</html> 