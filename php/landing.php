<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "invoice_management";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Log headers for debugging
$headers = getallheaders();
error_log("Landing headers: " . json_encode($headers));

// Extract Authorization and User-ID headers
$token = null;
$user_id = null;

// Check Authorization header
if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
    }
} elseif (isset($headers['authorization'])) { // Case-insensitive check
    $auth_header = $headers['authorization'];
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
    }
}

// Check User-ID header
if (isset($headers['User-ID'])) {
    $user_id = $conn->real_escape_string($headers['User-ID']);
} elseif (isset($headers['user-id'])) { // Case-insensitive check
    $user_id = $conn->real_escape_string($headers['user-id']);
}

if (empty($token) || empty($user_id)) {
    error_log("Missing token or user_id: token=$token, user_id=$user_id");
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Token or User-ID missing.',
        'redirect' => 'login.php'
    ]);
    $conn->close();
    exit;
}

// Validate token and user_id
$query = "SELECT user_id FROM users WHERE user_id = '$user_id' AND remember_token = '$token'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    // Fetch company name
    $query = "SELECT company_name FROM company_master WHERE user_id = '$user_id'";
    $result = $conn->query($query);
    $company = $result->fetch_assoc();

    // Fetch email templates
    $query = "SELECT id, template FROM email_master WHERE user_id = '$user_id'";
    $result = $conn->query($query);
    $email_templates = [];
    while ($row = $result->fetch_assoc()) {
        $email_templates[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'company_name' => $company['company_name'] ?? 'Unknown',
            'email_templates' => $email_templates
        ]
    ]);
} else {
    error_log("Invalid token or user_id: token=$token, user_id=$user_id");
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token or user ID.',
        'redirect' => 'login.php'
    ]);
}

$conn->close();
?>