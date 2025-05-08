<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AccoBills - Smart Invoice Management</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/invoice/accobills.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --info-color: #4361ee;
            --warning-color: #f72585;
            --danger-color: #e63946;
            --dark-color: #1E1E2D;
            --light-color: #F8F8FB;
            --body-bg: #F8F8FB;
            --sidebar-width: 260px;
            --header-height: 70px;
            --font-family: 'Poppins', sans-serif;
            --card-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
            --transition-speed: 0.3s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--body-bg);
            color: #6E6B7B;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 90px;
            left: 0;
            height: calc(100% - 90px);
            width: var(--sidebar-width);
            background: #fff;
            box-shadow: var(--card-shadow);
            z-index: 1030;
            transition: all var(--transition-speed);
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar.collapsed .sidebar-link span,
        .sidebar.collapsed .nav-item-header {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-icon {
            min-width: 100%;
            margin: 0;
            justify-content: center;
        }
        
        .sidebar.collapsed .sidebar-link {
            padding: 0.8rem;
            justify-content: center;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }
        
        .sidebar-brand {
            padding: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
            height: 110px;
        }
        
        .sidebar-brand a {
            display: flex;
            align-items: center;
            text-decoration: none;
            width: 100%;
            padding: 0.5rem;
            border-radius: 15px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            box-shadow: 0 8px 16px rgba(67, 97, 238, 0.2);
            transition: all 0.3s ease;
        }
        
        .sidebar-brand a:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(67, 97, 238, 0.3);
        }
        
        .brand-logo {
            width: 85px;
            height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: white;
            padding: 12px;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }
        
        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .brand-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
        }
        
        .sidebar-close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sidebar-close:hover {
            background: rgba(67, 97, 238, 0.2);
            transform: rotate(90deg);
        }
        
        .sidebar-close i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .sidebar-menu {
            padding: 1.5rem 1rem;
            list-style: none;
            margin: 0;
        }
        
        .nav-item-header {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            color: var(--primary-color);
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        .sidebar-item {
            position: relative;
        }
        
        .sidebar-link {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            color: #625F6E;
            text-decoration: none;
            transition: all var(--transition-speed);
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover {
            background: rgba(142, 142, 253, 0.1);
            color: var(--primary-color);
        }
        
        .sidebar-link.active {
            background: linear-gradient(118deg, rgba(78, 84, 200, 0.1), rgba(143, 148, 251, 0.1));
            color: var(--primary-color);
            font-weight: 500;
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-icon {
            font-size: 1.1rem;
            min-width: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Header styles */
        .main-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            height: 90px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
            z-index: 1040;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            transition: all var(--transition-speed);
        }
        
        .header-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .header-logo {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: white;
            padding: 12px;
            margin-right: 1.2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .header-logo:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-brand-text {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin: 0;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .header-right {
            display: flex;
            align-items: center;
        }
        
        .header-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: auto;
        }
        
        .header-nav-item {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .header-nav-item:hover {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .header-nav-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        .menu-toggle {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .menu-toggle i {
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed ~ .main-header .menu-toggle i {
            transform: rotate(180deg);
        }
        
        .page-title {
            flex-grow: 1;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
        }
        
        .header-action-icon {
            position: relative;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6E6B7B;
            border-radius: 50%;
            cursor: pointer;
            transition: all var(--transition-speed);
            margin-left: 0.5rem;
        }
        
        .header-action-icon:hover {
            background: rgba(142, 142, 253, 0.1);
            color: var(--primary-color);
        }
        
        .header-action-icon .badge {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
            border-radius: 50%;
            background: var(--accent-color);
            color: #fff;
        }
        
        .profile-dropdown {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-dropdown:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .profile-img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.8rem;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
        }
        
        .profile-name {
            font-size: 1rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .profile-role {
            font-size: 0.8rem;
            color: #6E6B7B;
            margin: 0;
            opacity: 0.9;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 90px;
            padding: 1.5rem;
            min-height: calc(100vh - 90px);
            transition: margin-left var(--transition-speed);
        }
        
        /* Card styles */
        .card {
            background: #fff;
            border-radius: 0.5rem;
            border: none;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed);
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(34, 41, 47, 0.13);
        }
        
        .card-header {
            background: transparent;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(34, 41, 47, 0.05);
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        /* Button Styles */
        .btn {
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 0.3rem;
            transition: all var(--transition-speed);
        }
        
        .btn-primary {
            background: linear-gradient(118deg, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 15px rgba(78, 84, 200, 0.4);
            transform: translateY(-3px);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(118deg, var(--primary-color), var(--secondary-color));
            color: #fff;
        }
        
        /* Gradient backgrounds */
        .bg-gradient-primary {
            background: linear-gradient(118deg, var(--primary-color), var(--secondary-color));
        }
        
        .bg-gradient-success {
            background: linear-gradient(118deg, var(--success-color), #5ddcb1);
        }
        
        .bg-gradient-danger {
            background: linear-gradient(118deg, var(--danger-color), #FF9F86);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(118deg, var(--warning-color), #FFCC81);
        }
        
        .bg-gradient-info {
            background: linear-gradient(118deg, var(--info-color), #7DE7FB);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 991.98px) {
            .header-brand-text {
                display: none;
            }
            
            .header-logo {
                width: 65px;
                height: 65px;
                margin-right: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 260px;
            }
            
            .sidebar.collapsed {
                transform: translateX(0);
                width: 260px;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        @media (max-width: 767.98px) {
            .profile-info {
                display: none;
            }
            
            .main-header {
                padding: 0 1rem;
            }
            
            .main-content {
                padding: calc(var(--header-height) + 1rem) 1rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-left">
            <div class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>

        <a href="/invoice/dashboard.php" class="header-brand">
            <div class="header-logo">
                <img src="/invoice/accobills.png" alt="AccoBills Logo">
            </div>
            <h5 class="header-brand-text">AccoBills</h5>
        </a>

        <div class="header-right">
            <div class="dropdown">
                <div class="profile-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-img">
                        <?php
                        if (isset($_SESSION['username'])) {
                            echo strtoupper(substr($_SESSION['username'], 0, 2));
                        } else {
                            echo '<i class="fas fa-user"></i>';
                        }
                        ?>
                    </div>
                    <div class="profile-info">
                        <p class="profile-name mb-0"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></p>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li class="nav-item-header">MAIN</li>
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item-header">COMPANY</li>
            <li class="sidebar-item">
                <a href="company_details.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'company_details.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-building"></i>
                    <span>Company</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="bank_details.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'bank_details.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-university"></i>
                    <span>Bank Details</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="tax_settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'tax_settings.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-percent"></i>
                    <span>Tax Rates</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="invoice_settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'invoice_settings.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-cog"></i>
                    <span>Invoice Settings</span>
                </a>
            </li>
            
            <li class="nav-item-header">BUSINESS</li>
            <li class="sidebar-item">
                <a href="clients.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-users"></i>
                    <span>Clients</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'invoices.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-file-invoice"></i>
                    <span>Invoices</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="create_invoice.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_invoice.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-plus-circle"></i>
                    <span>Create Invoice</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="analytics.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <li class="nav-item-header">COMMUNICATIONS</li>
            <li class="sidebar-item">
                <a href="email_templates.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'email_templates.php' ? 'active' : ''; ?>">
                    <i class="sidebar-icon fas fa-envelope"></i>
                    <span>Email Templates</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content Container -->
    <div class="main-content">

    <!-- Add this before closing body tag -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            
            // Check for saved state
            const sidebarState = localStorage.getItem('sidebarCollapsed');
            if (sidebarState === 'true') {
                sidebar.classList.add('collapsed');
            }

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                // Save state
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });

            // Handle mobile responsiveness
            function checkWidth() {
                if (window.innerWidth <= 991.98) {
                    sidebar.classList.add('collapsed');
                } else {
                    if (sidebarState === 'true') {
                        sidebar.classList.add('collapsed');
                    } else {
                        sidebar.classList.remove('collapsed');
                    }
                }
            }

            window.addEventListener('resize', checkWidth);
            checkWidth(); // Check on load
        });
    </script>
</body>
</html> 