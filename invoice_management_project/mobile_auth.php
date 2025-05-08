<?php
session_start();
require_once 'config/database.php';

// Check if this is an AJAX request that expects JSON
$wants_json = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Get user ID from URL parameter or POST data
$user_id = null;
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
} elseif (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
}

// If no user_id provided, return error
if (!$user_id) {
    if ($wants_json) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'No user ID provided'
        ]);
    } else {
        echo "<h1>Authentication Error</h1><p>No user ID provided</p>";
    }
    exit();
}

// Verify the user_id exists in database
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Valid user, set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    
    // Set cookies for better session persistence
    setcookie('user_id', $user['user_id'], time() + 3600, '/');
    setcookie('mobile_app', 'true', time() + 3600, '/');
    
    if ($wants_json) {
        // If AJAX request, return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Authentication successful',
            'user' => [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ],
            'redirect' => "create_invoice.php?user_id={$user['user_id']}&mobile_app=true"
        ]);
    } else {
        // Otherwise redirect directly to create_invoice.php
        header("Location: create_invoice.php?user_id={$user['user_id']}&mobile_app=true");
    }
} else {
    // Invalid user
    if ($wants_json) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user ID'
        ]);
    } else {
        echo "<h1>Authentication Error</h1><p>Invalid user ID</p>";
    }
}

$stmt->close();
$conn->close();
?> 