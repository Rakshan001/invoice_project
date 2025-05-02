<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// If it's a preflight OPTIONS request, respond with 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle only POST requests for login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed']);
    exit;
}

require_once 'config/database.php';

// Get the raw input
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// If JSON data is not valid
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Extract and sanitize input
$email = isset($data['email']) ? $conn->real_escape_string($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$remember = isset($data['remember']) ? filter_var($data['remember'], FILTER_VALIDATE_BOOLEAN) : false;

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    $conn->close();
    exit;
}

// Prepare a secure query using prepared statements
$stmt = $conn->prepare("SELECT user_id, email, full_name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        $user_id = $user['user_id'];
        $remember_token = null;

        // Generate and store a remember token if requested
        if ($remember) {
            $remember_token = bin2hex(random_bytes(32));
            $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $remember_token, $user_id);
            
            if (!$update_stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save token: ' . $conn->error]);
                $conn->close();
                exit;
            }
            $update_stmt->close();
        }

        // Get company information if exists
        $company_name = null;
        $company_id = null;
        
        $company_stmt = $conn->prepare("SELECT company_id, name FROM company_master WHERE user_id = ?");
        $company_stmt->bind_param("i", $user_id);
        $company_stmt->execute();
        $company_result = $company_stmt->get_result();
        
        if ($company_result->num_rows > 0) {
            $company = $company_result->fetch_assoc();
            $company_name = $company['name'];
            $company_id = $company['company_id'];
        }
        $company_stmt->close();

        // Return success with user data
        echo json_encode([
            'status' => 'success',
            'data' => [
                'user_id' => $user_id,
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'remember_token' => $remember_token,
                'company_id' => $company_id,
                'company_name' => $company_name
            ]
        ]);
    } else {
        // Password didn't match
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
} else {
    // User not found
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}

$stmt->close();
$conn->close();
?>