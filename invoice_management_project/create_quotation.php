<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon - AccoBills</title>
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

        .coming-soon-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .coming-soon-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: white;
            border-radius: 20px;
            padding: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .coming-soon-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .coming-soon-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .coming-soon-text p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .coming-soon-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="coming-soon-container">
        <div class="coming-soon-logo">
            <img src="accobills.png" alt="AccoBills Logo">
        </div>
        <div class="coming-soon-text">
            <i class="fas fa-tools coming-soon-icon"></i>
            <h1>Coming Soon!</h1>
            <p>We're working hard to bring you the best quotation management experience. This feature will be available soon!</p>
            <a href="landing.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 