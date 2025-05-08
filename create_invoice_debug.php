<?php
// Start session
session_start();

// Debug session information (remove this in production)
$debug_mode = true;

if ($debug_mode) {
    echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
    echo "<h3>Session Debug Info</h3>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session Name: " . session_name() . "</p>";
    echo "<pre>SESSION: ";
    print_r($_SESSION);
    echo "</pre>";
    echo "<pre>COOKIES: ";
    print_r($_COOKIE);
    echo "</pre>";
    echo "</div>";
}

// Check if there's a session ID in the URL or a manual login parameter
if (isset($_GET['PHPSESSID'])) {
    session_id($_GET['PHPSESSID']);
    session_start(); // Restart the session with the new ID
}

// For testing only: Allow manual login
if (isset($_GET['force_login']) && $_GET['force_login'] == 'true') {
    $_SESSION['user_id'] = isset($_GET['user_id']) ? $_GET['user_id'] : 1;
    echo "<div style='background-color: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 20px; border: 1px solid #d6e9c6;'>";
    echo "Forcing login with user_id: " . $_SESSION['user_id'];
    echo "</div>";
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<div style='background-color: #f2dede; color: #a94442; padding: 10px; margin-bottom: 20px; border: 1px solid #ebccd1;'>";
        echo "<h3>Authentication Issue</h3>";
        echo "<p>User not logged in. No user_id found in session.</p>";
        echo "<p>You can force a login for testing using: <a href='?force_login=true'>?force_login=true</a></p>";
        echo "</div>";
    } else {
        // In production, redirect to login
        header("Location: login.php");
        exit();
    }
}

// The rest of your original create_invoice.php code would go here
// For this debug version, let's just show a simplified version

if ($debug_mode) {
    echo "<h1>Create Invoice Page</h1>";
    echo "<p>This is a simplified version of the invoice creation page for testing WebView integration.</p>";
    
    echo "<div style='background-color: #d9edf7; color: #31708f; padding: 20px; border: 1px solid #bce8f1;'>";
    echo "<h3>Instructions for Integration</h3>";
    echo "<ol>";
    echo "<li>Place this file in your XAMPP/htdocs directory under invoice_management_project folder</li>";
    echo "<li>Make sure your Flutter app correctly sets the PHPSESSID cookie</li>";
    echo "<li>Update the webServerUrl in auth_provider.dart to point to your local server</li>";
    echo "<li>For testing on a real device, use your computer's network IP (not localhost)</li>";
    echo "</ol>";
    echo "</div>";
    
    // Simple form to simulate invoice creation
    echo "<div style='margin-top: 20px; padding: 20px; border: 1px solid #ddd;'>";
    echo "<h3>Create Invoice Form</h3>";
    echo "<form action='' method='post'>";
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>Client Name:</label>";
    echo "<input type='text' name='client_name' style='width: 100%; padding: 8px;'>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label style='display: block; margin-bottom: 5px;'>Amount:</label>";
    echo "<input type='number' name='amount' style='width: 100%; padding: 8px;'>";
    echo "</div>";
    
    echo "<button type='submit' name='submit_invoice' style='background-color: #4e73df; color: white; border: none; padding: 10px 20px; cursor: pointer;'>Submit Invoice</button>";
    echo "</form>";
    echo "</div>";
    
    // Handle form submission
    if (isset($_POST['submit_invoice'])) {
        echo "<div style='margin-top: 20px; background-color: #dff0d8; color: #3c763d; padding: 10px;'>";
        echo "<p>Invoice submitted successfully!</p>";
        echo "<p>Client: " . htmlspecialchars($_POST['client_name']) . "</p>";
        echo "<p>Amount: $" . htmlspecialchars($_POST['amount']) . "</p>";
        echo "</div>";
    }
}
?> 