<?php
session_start();
require_once 'config/database.php';
require_once 'includes/mail_functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$email = '';
$full_name = '';
$otp_sent = false;
$email_verified = false;

// Default registration step
if (!isset($_SESSION['reg_step'])) {
    $_SESSION['reg_step'] = 1;
}

// Display appropriate error or success messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Step 1: Email verification with OTP
if (isset($_POST['submit_step1'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    
    // Validate input
    if (empty($full_name) || empty($email)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
    } else {
        // Check if the email already exists
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            $_SESSION['error'] = "This email is already registered. Please login instead.";
        } else {
            // Generate random 6-digit OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $current_time = date("Y-m-d H:i:s");
            
            // Store OTP in database
            $query = "INSERT INTO otp_verification (email, otp, created_at) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE otp = ?, created_at = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email, $otp, $current_time, $otp, $current_time]);
            
            // Send OTP via email
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(SMTP_USERNAME, 'Invoice System');
                $mail->addAddress($email, $full_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Verification Code for Registration';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #4a4a4a;'>Email Verification</h2>
                        <p>Hello {$full_name},</p>
                        <p>Thank you for registering with our Invoice System. Please use the following verification code to complete your registration:</p>
                        <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                            {$otp}
                        </div>
                        <p>This code will expire in 15 minutes.</p>
                        <p>If you did not request this code, please ignore this email.</p>
                        <p>Regards,<br>Invoice System Team</p>
                    </div>
                ";
                
                $mail->send();
                
                // Store registration data in session for next steps
                $_SESSION['reg_step'] = 2;
                $_SESSION['reg_name'] = $full_name;
                $_SESSION['reg_email'] = $email;
                $_SESSION['otp_time'] = time();
                
                $_SESSION['success'] = "Verification code sent to your email.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Could not send verification email. Please try again later.";
            }
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: register.php");
    exit();
}

// Resend OTP if requested
if (isset($_GET['resend']) && $_GET['resend'] == 'true' && isset($_SESSION['reg_email'])) {
    $email = $_SESSION['reg_email'];
    $full_name = $_SESSION['reg_name'];
    
    // Generate new OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $current_time = date("Y-m-d H:i:s");
    
    // Log for debugging
    error_log("Resending OTP: Email=$email, OTP=$otp");
    
    try {
        // Update OTP in database
        $query = "UPDATE otp_verification SET otp = ?, created_at = ? WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$otp, $current_time, $email]);
        
        if ($stmt->rowCount() === 0) {
            // No rows updated, try inserting instead
            $query = "INSERT INTO otp_verification (email, otp, created_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email, $otp, $current_time]);
        }
        
        // Use the utility function to send OTP via email
        if (sendVerificationOTP($email, $otp)) {
            $_SESSION['success'] = "New verification code sent to your email.";
            $_SESSION['otp_time'] = time();
        } else {
            $_SESSION['error'] = "Could not send verification email. Please try again later.";
        }
    } catch (Exception $e) {
        error_log("Error in resend OTP: " . $e->getMessage());
        $_SESSION['error'] = "Something went wrong. Please try again.";
    }
    
    // Redirect to avoid form resubmission
    header("Location: register.php");
    exit();
}

// Step 2: OTP Verification
if (isset($_POST['submit_step2'])) {
    $otp = $_POST['otp'];
    $email = isset($_SESSION['reg_email']) ? $_SESSION['reg_email'] : '';
    
    // Log for debugging
    error_log("Verifying OTP: Email=$email, Entered OTP=$otp");
    
    if (empty($otp) || strlen($otp) != 6 || !is_numeric($otp)) {
        $_SESSION['error'] = "Please enter a valid 6-digit verification code.";
    } else if (empty($email)) {
        $_SESSION['error'] = "Session expired. Please start the registration process again.";
        $_SESSION['reg_step'] = 1;
    } else {
        try {
            // Debug query to see what's in the database
            $debug_query = "SELECT * FROM otp_verification WHERE email = ?";
            $debug_stmt = $conn->prepare($debug_query);
            $debug_stmt->execute([$email]);
            $debug_result = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Debug OTP records for $email: " . print_r($debug_result, true));
            
            // Check if OTP is valid (no expiry for testing)
            $query = "SELECT * FROM otp_verification WHERE email = ? AND otp = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email, $otp]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // For debugging
            error_log("OTP Check: Email=$email, OTP=$otp, Result count=".count($result));
            
            if (count($result) > 0) {
                // OTP verified successfully
                $_SESSION['reg_step'] = 3;
                $_SESSION['success'] = "Email verified successfully. Please set your password.";
            } else {
                $_SESSION['error'] = "Invalid verification code. Please try again or request a new one.";
            }
        } catch (Exception $e) {
            error_log("Error in OTP verification: " . $e->getMessage());
            $_SESSION['error'] = "Something went wrong. Please try again.";
        }
    }
    
    header("Location: register.php");
    exit();
}

// Step 3: Complete Registration with Password
if (isset($_POST['submit_step3'])) {
    $email = isset($_SESSION['reg_email']) ? $_SESSION['reg_email'] : '';
    $full_name = isset($_SESSION['reg_name']) ? $_SESSION['reg_name'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $agree_terms = isset($_POST['agree_terms']) ? true : false;
    
    // Debug log
    error_log("Registering user: Email=$email, Name=$full_name");
    
    if (empty($email) || empty($full_name)) {
        $_SESSION['error'] = "Session expired. Please start the registration process again.";
        $_SESSION['reg_step'] = 1;
    } else if (empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "Please enter and confirm your password.";
    } else if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
    } else if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else if (!$agree_terms) {
        $_SESSION['error'] = "You must agree to the terms and conditions.";
    } else {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate a username from email (before the @ symbol)
            $username = strstr($email, '@', true);
            // Make sure username is unique by adding random numbers if needed
            $username = $username . rand(100, 999);
            
            // Insert the user into database
            $query = "INSERT INTO users (username, password, email, full_name) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$username, $hashed_password, $email, $full_name]);
            
            if ($stmt->rowCount() > 0) {
                // Get the new user's ID
                $user_id = $conn->lastInsertId();
                
                // Clean up OTP verification record
                $query = "DELETE FROM otp_verification WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$email]);
                
                // Set up session for the new user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['email'] = $email;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true;
                
                // Clear registration session variables
                unset($_SESSION['reg_step']);
                unset($_SESSION['reg_email']);
                unset($_SESSION['reg_name']);
                unset($_SESSION['otp_time']);
                
                // Redirect to landing page
                $_SESSION['success'] = "Registration completed successfully! Welcome aboard.";
                header("Location: landing.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to complete registration. Please try again later.";
                error_log("Failed to insert user: " . $conn->errorInfo()[2]);
            }
        } catch (PDOException $e) {
            error_log("Database error during registration: " . $e->getMessage());
            $_SESSION['error'] = "An error occurred during registration. Please try again.";
        }
    }
    
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Invoice Management System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
/>
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

        .register-container {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .register-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: var(--white);
        }

        .register-header .logo {
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

        .register-header .logo i {
            font-size: 28px;
            color: var(--primary-color);
        }

        .register-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .register-form {
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

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            background: none;
            border: none;
            outline: none;
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
        
        .btn-secondary {
            background: var(--text-muted);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .password-requirements {
            margin-top: 0.5rem;
            padding-left: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .password-requirements ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .password-requirements li {
            position: relative;
            padding-left: 1.2rem;
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }

        .password-requirements li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--accent-color);
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-col {
            flex: 1;
        }
        
        .otp-container {
            max-width: 250px;
            position: relative;
        }
        
        .otp-input {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: 0.5rem;
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border: 2px solid #e1e1e1;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            height: 70px;
        }
        
        .otp-input:focus {
            background-color: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 84, 200, 0.25);
            outline: none;
        }
        
        .steps-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
            position: relative;
        }
        
        .steps-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 15%;
            right: 15%;
            height: 3px;
            background-color: #e9ecef;
            transform: translateY(-50%);
            z-index: 0;
        }
        
        .step {
            width: 50px;
            height: 50px;
            position: relative;
            z-index: 1;
            margin: 0 1.5rem;
            transition: all 0.3s ease;
        }
        
        .step .rounded-circle {
            background-color: #f0f0f0;
            color: #777;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .step.active .rounded-circle {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(78, 84, 200, 0.3);
            transform: scale(1.1);
        }
        
        .step.completed .rounded-circle {
            background: var(--success-color);
            color: white;
        }
        
        .btn-link.resend-otp {
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .btn-link.resend-otp:hover {
            transform: translateY(-2px);
            color: var(--primary-color);
        }
        
        .btn-link.resend-otp:disabled {
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 576px) {
            .register-container {
                border-radius: 15px;
            }
            
            .register-header {
                padding: 2rem 1.5rem 1rem;
            }
            
            .register-form {
                padding: 1.5rem;
            }
            
            .form-control, .btn {
                height: 50px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Create an Account</h1>
            <p>Join us to manage your invoices efficiently</p>
        </div>
        
        <div class="register-form">
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
            
            <div class="steps-indicator">
                <div class="step <?php echo $_SESSION['reg_step'] == 1 ? 'active' : (($_SESSION['reg_step'] ?? 1) > 1 ? 'completed' : ''); ?>">
                    <div class="d-flex align-items-center justify-content-center h-100 w-100 rounded-circle">
                        <i class="fas <?php echo ($_SESSION['reg_step'] ?? 1) > 1 ? 'fa-check' : 'fa-user'; ?>"></i>
                    </div>
                </div>
                <div class="step <?php echo $_SESSION['reg_step'] == 2 ? 'active' : (($_SESSION['reg_step'] ?? 1) > 2 ? 'completed' : ''); ?>">
                    <div class="d-flex align-items-center justify-content-center h-100 w-100 rounded-circle">
                        <i class="fas <?php echo ($_SESSION['reg_step'] ?? 1) > 2 ? 'fa-check' : 'fa-key'; ?>"></i>
                    </div>
                </div>
                <div class="step <?php echo $_SESSION['reg_step'] == 3 ? 'active' : ''; ?>">
                    <div class="d-flex align-items-center justify-content-center h-100 w-100 rounded-circle">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
            </div>
            
            <!-- Step 1: Email Entry -->
            <form method="POST" action="" class="<?php echo ($_SESSION['reg_step'] ?? 1) == 1 ? '' : 'd-none'; ?>">
                <div class="mb-4">
                    <label for="full_name" class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo isset($_SESSION['reg_name']) ? $_SESSION['reg_name'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_SESSION['reg_email']) ? $_SESSION['reg_email'] : ''; ?>" required>
                    </div>
                </div>
                
                <button type="submit" name="submit_step1" class="btn mb-3">
                    <i class="fas fa-paper-plane me-2"></i>Send Verification Code
                </button>
            </form>
            
            <!-- Step 2: OTP Verification -->
            <form method="POST" action="" id="otpForm" class="<?php echo ($_SESSION['reg_step'] ?? 1) == 2 ? '' : 'd-none'; ?>">
                <div class="mb-4 text-center">
                    <label for="otp" class="form-label fw-bold mb-3">Verification Code</label>
                    <div class="d-flex justify-content-center mb-3">
                        <div class="otp-container">
                            <input type="text" class="form-control otp-input" id="otp" name="otp" 
                                   placeholder="Enter code" maxlength="6" inputmode="numeric" pattern="[0-9]*" required>
                        </div>
                    </div>
                    <div class="form-text mt-3">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        Check your email for the verification code
                        <?php if (isset($_SESSION['otp_time'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Code sent at: <?php echo date('h:i A', $_SESSION['otp_time']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" name="submit_step2" class="btn mb-3 w-100">
                    <i class="fas fa-check-circle me-2"></i>Verify Code
                </button>
                
                <div class="text-center mt-3">
                    <a href="register.php?resend=true" class="btn btn-link resend-otp">Didn't receive the code? Send again</a>
                </div>
            </form>
            
            <!-- Step 3: Password Creation -->
            <form method="POST" action="" class="<?php echo ($_SESSION['reg_step'] ?? 1) == 3 ? '' : 'd-none'; ?>">
                <div class="mb-4">
                    <label for="password" class="form-label">Create Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Password must be at least 8 characters long</div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" name="submit_step3" class="btn mb-3" id="registerBtn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <a href="login.php" class="login-link">
                <i class="fas fa-arrow-left"></i>Already have an account? Log in
            </a>
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
        
        // Enhanced password validation with real-time feedback
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const termsCheckbox = document.getElementById('agree_terms');
        const registerBtn = document.getElementById('registerBtn');
        
        if (passwordInput && confirmInput && termsCheckbox && registerBtn) {
            // Create password strength indicator if it doesn't exist
            if (!document.getElementById('password-strength')) {
                const strengthIndicator = document.createElement('div');
                strengthIndicator.id = 'password-strength';
                strengthIndicator.className = 'password-strength mt-2';
                strengthIndicator.innerHTML = `
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="strength-text mt-1 d-block"></small>
                `;
                passwordInput.parentNode.parentNode.appendChild(strengthIndicator);
            }
            
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
                if (isValidLength && doPasswordsMatch && termsCheckbox.checked) {
                    registerBtn.disabled = false;
                } else {
                    registerBtn.disabled = true;
                }
            }
            
            passwordInput.addEventListener('input', validatePassword);
            confirmInput.addEventListener('input', validatePassword);
            termsCheckbox.addEventListener('change', validatePassword);
            
            // Initial validation check
            validatePassword();
        }
        
        // Enhanced OTP input with improved behavior
        const otpInput = document.getElementById('otp');
        const otpForm = document.getElementById('otpForm');
        
        if (otpInput && otpForm) {
            // Focus the OTP input when the page loads in step 2
            if (!otpForm.classList.contains('d-none')) {
                setTimeout(() => otpInput.focus(), 500);
            }
            
            otpInput.addEventListener('input', function(e) {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limit to 6 digits
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
                
                // Visual feedback
                if (this.value.length === 6) {
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
                
                // Add animation on input
                if (e.inputType === 'insertText') {
                    this.classList.add('animate__animated', 'animate__pulse');
                    setTimeout(() => {
                        this.classList.remove('animate__animated', 'animate__pulse');
                    }, 500);
                }
            });
            
            // Handle manual form submission with validation
            otpForm.addEventListener('submit', function(e) {
                if (otpInput.value.length !== 6) {
                    e.preventDefault();
                    otpInput.classList.add('is-invalid');
                    otpInput.focus();
                    return false;
                }
                return true;
            });
            
            // Handle pasting of OTP
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const numericText = pastedText.replace(/[^0-9]/g, '').substring(0, 6);
                this.value = numericText;
                
                // Visual feedback
                if (numericText.length === 6) {
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                }
            });
            
            // Add a manual submit button if needed
            const verifyButton = otpForm.querySelector('button[name="submit_step2"]');
            if (verifyButton) {
                verifyButton.addEventListener('click', function() {
                    if (otpInput.value.length === 6) {
                        otpForm.submit();
                    } else {
                        otpInput.classList.add('is-invalid');
                        otpInput.focus();
                    }
                });
            }
        }
        
        // Resend OTP timer with improved UI
        const resendLink = document.querySelector('.resend-otp');
        if (resendLink) {
            let countdown = 30;
            let timer;
            
            function startResendTimer() {
                resendLink.classList.add('disabled');
                resendLink.setAttribute('disabled', 'disabled');
                resendLink.style.pointerEvents = 'none';
                resendLink.innerHTML = `Resend in ${countdown} seconds`;
                
                timer = setInterval(() => {
                    countdown--;
                    resendLink.innerHTML = `Resend in ${countdown} seconds`;
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        resendLink.classList.remove('disabled');
                        resendLink.removeAttribute('disabled');
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.innerHTML = `Didn't receive the code? Send again`;
                        countdown = 30;
                    }
                }, 1000);
            }
            
            // Check if we should start the timer (if the page was just loaded with OTP sent)
            if (document.querySelector('.alert-success')) {
                startResendTimer();
            }
            
            resendLink.addEventListener('click', function(e) {
                if (!this.hasAttribute('disabled')) {
                    // Allow the link to proceed, but start timer for next time
                    setTimeout(() => {
                        startResendTimer();
                    }, 500);
                } else {
                    e.preventDefault();
                }
            });
        }
        
        // Smooth transition between steps
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.classList.contains('d-none')) {
                form.style.display = 'block';
                setTimeout(() => {
                    form.style.opacity = '1';
                }, 10);
            } else {
                form.style.opacity = '0';
            }
        });
        
        // Improved animations for alerts
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            // Add entrance animation
            alert.classList.add('animate__animated', 'animate__fadeIn');
            
            // Auto-hide after delay
            setTimeout(() => {
                alert.classList.remove('animate__fadeIn');
                alert.classList.add('animate__fadeOut');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }, 5000);
            
            // Add close button if not present
            if (!alert.querySelector('.btn-close')) {
                const closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn-close';
                closeBtn.setAttribute('data-bs-dismiss', 'alert');
                closeBtn.setAttribute('aria-label', 'Close');
                alert.appendChild(closeBtn);
                
                closeBtn.addEventListener('click', function() {
                    alert.classList.add('animate__fadeOut');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                });
            }
        });
    });
    </script>
</body>
</html>