<?php
// Start or resume session
session_start();

// Print session information
echo "<h1>Session Information</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Parameters: ";
print_r(session_get_cookie_params());
echo "\n\nSESSION Variables:\n";
print_r($_SESSION);
echo "\n\nCOOKIE Variables:\n";
print_r($_COOKIE);
echo "</pre>";

// Check if user is authenticated
if (isset($_SESSION['user_id'])) {
    echo "<p style='color:green;'>User is authenticated with user_id: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color:red;'>User is NOT authenticated in this session</p>";
}

// Display all request headers sent by the client
echo "<h2>Request Headers</h2>";
echo "<pre>";
$headers = apache_request_headers();
foreach ($headers as $header => $value) {
    echo "$header: $value\n";
}
echo "</pre>";
?>

<h2>Test: Set Session Variable</h2>
<form method="POST">
    <input type="text" name="session_var_name" placeholder="Variable name">
    <input type="text" name="session_var_value" placeholder="Variable value">
    <input type="submit" name="set_session" value="Set Session Variable">
</form>

<?php
// Handle form submission to set session variables
if (isset($_POST['set_session'])) {
    $name = $_POST['session_var_name'];
    $value = $_POST['session_var_value'];
    
    if (!empty($name)) {
        $_SESSION[$name] = $value;
        echo "<p>Session variable '$name' set to '$value'</p>";
        // Refresh the page to show updated session
        echo "<script>window.location.reload();</script>";
    }
}
?> 