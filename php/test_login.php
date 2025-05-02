<?php
// Test script to verify login works
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #4e73df;
            color: white;
            border: none;
            cursor: pointer;
        }
        .result {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login API Test</h1>
        <p>Use this form to test the login API endpoint.</p>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" id="email" value="srisha2373@gmail.com">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" value="password123">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="remember" checked>
                Remember me
            </label>
        </div>
        
        <button id="submitBtn">Test Login</button>
        
        <div class="result" id="result">
            <h3>API Response:</h3>
            <pre id="response">Click "Test Login" to see response...</pre>
        </div>
        
        <h2>PHP Database Connection Test</h2>
        <?php
        require_once 'config/database.php';
        if ($conn->connect_error) {
            echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color: green;'>Database connection successful!</p>";
            
            // Test query to see if users table exists
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            if ($result->num_rows > 0) {
                echo "<p style='color: green;'>Users table exists</p>";
                
                // Check if there are any users
                $users = $conn->query("SELECT user_id, email, full_name FROM users LIMIT 5");
                if ($users->num_rows > 0) {
                    echo "<p>Found " . $users->num_rows . " users:</p>";
                    echo "<ul>";
                    while ($user = $users->fetch_assoc()) {
                        echo "<li>ID: " . $user['user_id'] . " - " . $user['full_name'] . " (" . $user['email'] . ")</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='color: red;'>No users found in the database!</p>";
                }
            } else {
                echo "<p style='color: red;'>Users table does not exist</p>";
            }
        }
        ?>
    </div>

    <script>
        document.getElementById('submitBtn').addEventListener('click', function() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            const responseElement = document.getElementById('response');
            
            responseElement.textContent = 'Loading...';
            
            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                    remember: remember
                })
            })
            .then(response => response.text())
            .then(data => {
                try {
                    // Try to parse as JSON to format it nicely
                    const json = JSON.parse(data);
                    responseElement.textContent = JSON.stringify(json, null, 2);
                } catch (e) {
                    // If not valid JSON, just show the raw response
                    responseElement.textContent = data;
                }
            })
            .catch(error => {
                responseElement.textContent = 'Error: ' + error;
            });
        });
    </script>
</body>
</html> 