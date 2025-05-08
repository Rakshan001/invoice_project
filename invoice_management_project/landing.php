<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - AccoBills</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="accobills.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3f37c9;
            --secondary-color: #4895ef;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .landing-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .welcome-logo {
            width: 150px;
            height: 150px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 25px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .welcome-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .welcome-text {
            margin-bottom: 3rem;
        }

        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .welcome-text p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .action-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-action {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-width: 200px;
            justify-content: center;
        }

        .btn-invoice {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-invoice:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }

        .btn-quotation {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-quotation:hover {
            background: rgba(67, 97, 238, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.2);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="welcome-logo">
            <img src="accobills.png" alt="AccoBills Logo">
        </div>
        <div class="welcome-text">
            <h1>Welcome to AccoBills</h1>
            <p>Choose what you'd like to create today</p>
        </div>
        <div class="action-buttons">
            <a href="create_invoice.php" class="btn btn-action btn-invoice">
                <i class="fas fa-file-invoice"></i>
                Create Invoice
            </a>
            <a href="create_quotation.php" class="btn btn-action btn-quotation">
                <i class="fas fa-file-alt"></i>
                Create Quotation
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 