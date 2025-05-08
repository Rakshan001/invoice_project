<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once('../config/database.php');
$user_id = $_SESSION['user_id'];

// Fetch company details
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Fetch current settings
$stmt = $conn->prepare("SELECT * FROM quotation_settings WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$settings = $result->fetch_assoc();

// Create default settings if not exists
if (!$settings) {
    $stmt = $conn->prepare("
        INSERT INTO quotation_settings (company_id, default_tax_rate, default_discount_rate, quotation_prefix, next_number, validity_days)
        VALUES (?, 18.00, 0.00, 'QT', 1, 30)
    ");
    $stmt->bind_param("i", $company['company_id']);
    $stmt->execute();
    
    // Fetch the newly created settings
    $stmt = $conn->prepare("SELECT * FROM quotation_settings WHERE company_id = ?");
    $stmt->bind_param("i", $company['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $default_tax_rate = floatval($_POST['default_tax_rate']);
    $default_discount_rate = floatval($_POST['default_discount_rate']);
    $quotation_prefix = $_POST['quotation_prefix'];
    $next_number = intval($_POST['next_number']);
    $validity_days = intval($_POST['validity_days']);
    $default_terms = $_POST['default_terms'];

    try {
        $stmt = $conn->prepare("
            UPDATE quotation_settings 
            SET default_tax_rate = ?,
                default_discount_rate = ?,
                quotation_prefix = ?,
                next_number = ?,
                validity_days = ?,
                default_terms = ?
            WHERE company_id = ?
        ");
        $stmt->bind_param("ddsiisi", 
            $default_tax_rate,
            $default_discount_rate,
            $quotation_prefix,
            $next_number,
            $validity_days,
            $default_terms,
            $company['company_id']
        );
        $stmt->execute();

        $_SESSION['success'] = "Quotation settings updated successfully.";
        header("Location: quotation_settings.php");
        exit();
    } catch (Exception $e) {
        error_log("Error updating quotation settings: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update settings. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Settings</title>
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
        }
        
        /* Layout */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }
        
        .content {
            flex: 1;
            margin-left: 250px;
            padding: 1rem;
        }
        
        /* Sidebar */
        .sidebar-brand {
            height: 60px;
            display: flex;
            align-items: center;
            padding-left: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 1px solid #e3e6f0;
        }
        
        .sidebar-heading {
            color: #b7b9cc;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.75rem 1rem 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #6e707e;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .nav-link i {
            margin-right: 0.5rem;
            width: 1.25rem;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .nav-link.active, .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }
        
        /* Header */
        .topbar {
            height: 60px;
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            margin-bottom: 1.5rem;
        }
        
        /* Welcome card */
        .welcome-card {
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .welcome-card h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .card-header i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-file-invoice me-2"></i>
                <span>Quotations</span>
            </div>
            
            <div class="nav flex-column">
                <div class="sidebar-heading">MAIN</div>
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="create_quotation.php" class="nav-link">
                    <i class="fas fa-plus"></i> Create Quotation
                </a>
                <a href="list_quotations.php" class="nav-link">
                    <i class="fas fa-list"></i> All Quotations
                </a>
                
                <div class="sidebar-heading">OTHER</div>
                <a href="quotation_settings.php" class="nav-link active">
                    <i class="fas fa-cog"></i> Quotation Settings
                </a>
                
                <a href="../dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Main Dashboard
                </a>
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Top Navigation -->
            <div class="topbar">
                <div>
                    <h4 class="mb-0">Quotation Settings</h4>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h4>Configure Your Quotation Settings</h4>
                <p>Customize your quotation preferences, including numbering format, default rates, and terms.</p>
            </div>

            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cog"></i>
                    <h5>General Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Quotation Prefix</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-hashtag"></i>
                                        </span>
                                        <input type="text" name="quotation_prefix" class="form-control" 
                                               value="<?= htmlspecialchars($settings['quotation_prefix']) ?>" required>
                                    </div>
                                    <div class="form-text">This prefix will be added to all quotation numbers (e.g., QT0001)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Next Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-sort-numeric-up"></i>
                                        </span>
                                        <input type="number" name="next_number" class="form-control" 
                                               value="<?= htmlspecialchars($settings['next_number']) ?>" required min="1">
                                    </div>
                                    <div class="form-text">The next quotation will be: <?= htmlspecialchars($settings['quotation_prefix']) . str_pad($settings['next_number'], 4, '0', STR_PAD_LEFT) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Default Tax Rate (%)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-percent"></i>
                                        </span>
                                        <input type="number" name="default_tax_rate" class="form-control" 
                                               value="<?= htmlspecialchars($settings['default_tax_rate']) ?>" 
                                               step="0.01" min="0" max="100" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Default Discount Rate (%)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <input type="number" name="default_discount_rate" class="form-control" 
                                               value="<?= htmlspecialchars($settings['default_discount_rate']) ?>" 
                                               step="0.01" min="0" max="100" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Validity Days</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="number" name="validity_days" class="form-control" 
                                               value="<?= htmlspecialchars($settings['validity_days']) ?>" 
                                               min="1" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Default Terms & Conditions</label>
                            <textarea name="default_terms" class="form-control" rows="5"><?= htmlspecialchars($settings['default_terms']) ?></textarea>
                            <div class="form-text">These terms will be automatically added to new quotations</div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 