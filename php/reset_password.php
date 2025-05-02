<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $password = $data['password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

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

    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > ?");
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user['user_id']);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password reset successfully. Please login.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
    }
    exit();
}
?>