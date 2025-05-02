<?php
session_start();
require_once 'config/database.php';
require_once 'includes/mail_functions.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $step = $data['step'] ?? '';

    if ($step == 'step1') {
        $full_name = $data['full_name'] ?? '';
        $email = $data['email'] ?? '';

        if (empty($full_name) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'This email is already registered. Please login instead.']);
            exit();
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));
        $current_time = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("INSERT INTO otp_verification (email, otp, created_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = ?, created_at = ?");
        $stmt->bind_param("sssss", $email, $otp, $current_time, $otp, $current_time);
        $stmt->execute();

        if (sendVerificationOTP($email, $otp, $full_name)) {
            $_SESSION['reg_step'] = 2;
            $_SESSION['reg_name'] = $full_name;
            $_SESSION['reg_email'] = $email;
            $_SESSION['otp_time'] = time();
            echo json_encode(['status' => 'success', 'message' => 'Verification code sent to your email.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not send verification email. Please try again later.']);
        }
        exit();
    }

    if ($step == 'resend_otp' && isset($_SESSION['reg_email'])) {
        $email = $_SESSION['reg_email'];
        $full_name = $_SESSION['reg_name'];
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $current_time = date("Y-m-d H:i:s");

        $stmt = $conn->prepare("UPDATE otp_verification SET otp = ?, created_at = ? WHERE email = ?");
        $stmt->bind_param("sss", $otp, $current_time, $email);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO otp_verification (email, otp, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $otp, $current_time);
            $stmt->execute();
        }

        if (sendVerificationOTP($email, $otp, $full_name)) {
            $_SESSION['otp_time'] = time();
            echo json_encode(['status' => 'success', 'message' => 'New verification code sent to your email.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Could not send verification email. Please try again later.']);
        }
        exit();
    }

    if ($step == 'step2') {
        $otp = $data['otp'] ?? '';
        $email = $_SESSION['reg_email'] ?? '';

        if (empty($otp) || strlen($otp) != 6 || !is_numeric($otp)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 6-digit verification code.']);
            exit();
        }
        if (empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please start the registration process again.']);
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM otp_verification WHERE email = ? AND otp = ?");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['reg_step'] = 3;
            echo json_encode(['status' => 'success', 'message' => 'Email verified successfully. Please set your password.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid verification code. Please try again or request a new one.']);
        }
        exit();
    }

    if ($step == 'step3') {
        $email = $_SESSION['reg_email'] ?? '';
        $full_name = $_SESSION['reg_name'] ?? '';
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        $agree_terms = $data['agree_terms'] ?? false;

        if (empty($email) || empty($full_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Session expired. Please start the registration process again.']);
            exit();
        }
        if (empty($password) || empty($confirm_password)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter and confirm your password.']);
            exit();
        }
        if (strlen($password) < 8) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long.']);
            exit();
        }
        if ($password !== $confirm_password) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
            exit();
        }
        if (!$agree_terms) {
            echo json_encode(['status' => 'error', 'message' => 'You must agree to the terms and conditions.']);
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $username = strstr($email, '@', true) . rand(100, 999);

        $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, email_verified) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $username, $hashed_password, $email, $full_name);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $user_id = $conn->insert_id;
            $stmt = $conn->prepare("DELETE FROM otp_verification WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            $_SESSION['logged_in'] = true;

            unset($_SESSION['reg_step']);
            unset($_SESSION['reg_email']);
            unset($_SESSION['reg_name']);
            unset($_SESSION['otp_time']);

            echo json_encode(['status' => 'success', 'message' => 'Registration completed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to complete registration. Please try again later.']);
        }
        exit();
    }
}
?>