<?php
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch company name for welcome message
require_once('../config/database.php');
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT company_name FROM company_master WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --bg-light: #f8f9fc;
            --border-radius: 0.35rem;
        }
        
        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: #5a5c69;
            min-height: 100vh;
        }
        
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.25rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .sidebar-brand:hover {
            color: var(--primary-color);
        }
        
        .sidebar-heading {
            color: #858796;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 1rem 1.25rem 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .nav-link {
            color: #858796;
            padding: 0.75rem 1.25rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            border-radius: 0;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
            background-color: var(--bg-light);
        }
        
        .nav-link i {
            width: 1rem;
            text-align: center;
            color: inherit;
            font-size: 0.85rem;
        }
        
        /* Content area */
        .content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        /* Welcome card */
        .welcome-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .welcome-card h4 {
            color: #5a5c69;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            color: #858796;
            margin-bottom: 0;
        }
        
        /* Welcome section */
        .welcome-section {
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .welcome-section h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section p {
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        /* Stats cards */
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stats-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .stats-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #5a5c69;
            margin-bottom: 0.25rem;
        }
        
        .stats-label {
            color: #858796;
            font-size: 0.875rem;
            margin: 0;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--border-radius);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-light {
            background-color: white;
            border-color: #e3e6f0;
            color: #6e707e;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        /* User avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="index.php" class="sidebar-brand">
                <i class="fas fa-file-invoice"></i>
                Quotations
            </a>
            <div class="sidebar-heading">MAIN</div>
            <div class="nav flex-column">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="create_quotation.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'create_quotation.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i>
                    Create Quotation
                </a>
                <a href="list_quotations.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'list_quotations.php' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    All Quotations
                </a>
            </div>
            <div class="sidebar-heading">OTHER</div>
            <div class="nav flex-column">
                <a href="quotation_settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'quotation_settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    Quotation Settings
                </a>
                <a href="tax_settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tax_settings.php' ? 'active' : '' ?>">
                    <i class="fas fa-percentage"></i>
                    Tax Settings
                </a>
                <a href="../dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Main Dashboard
                </a>
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Content area -->
        <div class="content">
            <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
            <!-- Welcome section for dashboard -->
            <div class="welcome-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Welcome back, <?= htmlspecialchars($company['company_name']) ?>! 👋</h2>
                        <p>Here's what's happening with your quotations today.</p>
                    </div>
                    <div>
                        <a href="create_quotation.php" class="btn btn-light">
                            <i class="fas fa-plus"></i>New Quotation
                        </a>
                        <button class="btn btn-light ms-2">
                            <i class="fas fa-user-plus"></i>Add Client
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 