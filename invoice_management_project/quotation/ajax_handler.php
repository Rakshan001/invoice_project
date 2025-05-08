<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Handle different AJAX actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add_client':
            addClient($conn);
            break;
        default:
            sendResponse(false, 'Invalid action');
            break;
    }
} else {
    sendResponse(false, 'No action specified');
}

/**
 * Add a new client to the database
 */
function addClient($conn) {
    // Check required fields
    if (empty($_POST['name']) || empty($_POST['email'])) {
        sendResponse(false, 'Name and email are required');
        return;
    }
    
    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get company ID
    $stmt = $conn->prepare("SELECT company_id FROM company_master WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        sendResponse(false, 'Company not found');
        return;
    }
    
    $company_id = $company['company_id'];
    
    // Check if client email already exists for this company
    $stmt = $conn->prepare("SELECT client_id FROM client_master WHERE email = ? AND company_id = ?");
    $stmt->execute([$_POST['email'], $company_id]);
    if ($stmt->fetch()) {
        sendResponse(false, 'A client with this email already exists');
        return;
    }
    
    try {
        // Prepare data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $gstin = isset($_POST['gstin']) ? trim($_POST['gstin']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $state = isset($_POST['state']) ? trim($_POST['state']) : 'Maharashtra'; // Default state if not provided
        
        // Insert new client with correct column names
        $stmt = $conn->prepare("
            INSERT INTO client_master (
                company_id, name, email, gst, address, state, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $company_id,
            $name,
            $email,
            $gstin,
            $address,
            $state
        ]);
        
        $client_id = $conn->lastInsertId();
        
        sendResponse(true, 'Client added successfully', ['client_id' => $client_id]);
    } catch (PDOException $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = []) {
    $response = array_merge(
        ['success' => $success, 'message' => $message],
        $data
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 