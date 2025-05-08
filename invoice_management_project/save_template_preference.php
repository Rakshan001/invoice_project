<?php
session_start();
require_once 'config/database.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Make sure template_id is provided
if (!isset($_POST['template_id'])) {
    echo json_encode(['error' => 'No template_id provided']);
    exit;
}

$user_id = $_SESSION['user_id'];
$template_id = intval($_POST['template_id']);

try {
    // Check if the template exists
    $stmt = $conn->prepare("SELECT * FROM invoice_templates WHERE template_id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo json_encode(['error' => 'Invalid template_id']);
        exit;
    }
    
    // Save the preference
    $stmt = $conn->prepare("
        INSERT INTO user_preferences (user_id, preference_key, preference_value)
        VALUES (?, 'invoice_template', ?)
        ON DUPLICATE KEY UPDATE preference_value = ?
    ");
    $success = $stmt->execute([$user_id, $template_id, $template_id]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error saving preference']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 