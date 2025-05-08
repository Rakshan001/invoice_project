<?php
session_start();
require_once 'config/database.php';

// Check if remember_token column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'remember_token'");

// If remember_token column doesn't exist, add it
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(100) NULL");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Add error logging for debugging
    error_log("Login attempt - Email: " . $email);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Debug: Print the user data
    if ($user) {
        error_log("User found in database");
        error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];

            if ($remember) {
                $remember_token = bin2hex(random_bytes(32));
                $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $remember_token, $user['user_id']);
                $update_stmt->execute();
                
                setcookie('remember_token', $remember_token, time() + (86400 * 30), '/');
            }

            header("Location: landing.php");
            exit();
        }
    } else {
        error_log("No user found with email: " . $email);
    }

    $_SESSION['error'] = "Invalid email or password";
}

// Check remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        header("Location: landing.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AccoBills</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="accobills.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3f37c9;
            --secondary-color: #4895ef;
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
            margin: 0;
        }

        .container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            margin: 20px;
        }

        .login-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: var(--white);
        }

        .logo-container {
            width: 130px;
            height: 130px;
            margin: 0 auto 1.5rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 25px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .logo-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-header p {
            opacity: 0.95;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .login-form {
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

        .btn-login {
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

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(94, 92, 230, 0.25);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-password {
            text-align: right;
            display: block;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            cursor: pointer;
            user-select: none;
        }

        .extra-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #dee2e6;
            z-index: 1;
        }

        .divider span {
            position: relative;
            background-color: white;
            padding: 0 1rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            z-index: 2;
        }

        .register-link, .admin-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .register-link:hover, .admin-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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

        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem 1rem;
            }
            
            .login-form {
                padding: 1.5rem;
            }
            
            .form-control, .btn-login {
                height: 50px;
            }
            
            .extra-options {
                flex-direction: row;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="accobills.png" alt="AccoBills Logo">
                </div>
                <h1>AccoBills</h1>
                <p>Smart Invoice Management System</p>
            </div>
            
            <div class="login-form">
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <i class="fas fa-envelope form-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                    
                    <div class="form-group">
                        <i class="fas fa-lock form-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                    
                    <div class="extra-options">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>SIGN IN
                    </button>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="register-link">Register Now</a></p>
                    </div>
                    
                    <div class="divider">
                        <span>OR</span>
                    </div>
                    
                    <div class="text-center">
                        <a href="admin_login.php" class="admin-link">
                            <i class="fas fa-user-shield me-2"></i>Admin Access
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 