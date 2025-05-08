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

// Fetch clients for dropdown
$stmt = $conn->prepare("SELECT client_id, name as client_name, email FROM client_master WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

// Fetch quotation settings
$stmt = $conn->prepare("SELECT * FROM quotation_settings WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$settings = $result->fetch_assoc();

if (!$settings) {
    // Create default settings if not exists
    $defaultTaxRate = 18.00;
    $defaultDiscountRate = 0.00;
    $quotationPrefix = 'QT';
    $nextNumber = 1;
    $validityDays = 30;
    
    $stmt = $conn->prepare("
        INSERT INTO quotation_settings (company_id, default_tax_rate, default_discount_rate, quotation_prefix, next_number, validity_days)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iddsii", $company['company_id'], $defaultTaxRate, $defaultDiscountRate, $quotationPrefix, $nextNumber, $validityDays);
    $stmt->execute();
    
    $settings = [
        'default_tax_rate' => $defaultTaxRate,
        'default_discount_rate' => $defaultDiscountRate,
        'quotation_prefix' => $quotationPrefix,
        'next_number' => $nextNumber,
        'validity_days' => $validityDays,
        'default_terms' => 'Standard terms and conditions apply.'
    ];
}

// Generate next quotation number
$quotation_number = $settings['quotation_prefix'] . str_pad($settings['next_number'], 4, '0', STR_PAD_LEFT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['analyze_title'])) {
    try {
        // Validate required fields
        if (empty($_POST['client_id'])) {
            throw new Exception("Please select a client");
        }
        
        if (empty($_POST['items']) || !is_array($_POST['items'])) {
            throw new Exception("Please add at least one item");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Prepare data with proper validation
            $client_id = $_POST['client_id'];
            $quotation_date = isset($_POST['quotation_date']) ? $_POST['quotation_date'] : date('Y-m-d');
            $valid_until = isset($_POST['valid_until']) ? $_POST['valid_until'] : date('Y-m-d', strtotime('+' . $settings['validity_days'] . ' days'));
            $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
            $tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
            $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
            $discount_rate = isset($_POST['discount_rate']) ? floatval($_POST['discount_rate']) : 0;
            $discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
            $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
            $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
            $terms_conditions = isset($_POST['terms_conditions']) ? $_POST['terms_conditions'] : '';
            $scope = isset($_POST['scope']) ? $_POST['scope'] : '';
            $payment_terms = isset($_POST['payment_terms']) ? $_POST['payment_terms'] : '';

            // Insert quotation
            $stmt = $conn->prepare("
                INSERT INTO quotations (
                    quotation_number, company_id, client_id, quotation_date,
                    validity_days, total_amount, tax_amount, discount_amount,
                    grand_total, terms_conditions, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            
            $stmt->bind_param("isddddss", $quotation_number, $company['company_id'], $client_id, $quotation_date, $settings['validity_days'], $subtotal, $tax_amount, $discount_amount, $total_amount, $terms_conditions);
            $stmt->execute();

            $quotation_id = $conn->insert_id;

            // Insert quotation items with validation
            $stmt = $conn->prepare("
                INSERT INTO quotation_items (
                    quotation_id, description, quantity, unit_price,
                    tax_rate, tax_amount, total_amount
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($_POST['items'] as $item) {
                if (!empty($item['description'])) {
                    $stmt->bind_param("isdddd", $quotation_id, $item['description'], floatval($item['quantity']), floatval($item['unit_price']), floatval($item['tax_rate']), floatval($item['tax_amount']), floatval($item['total_amount']));
                    $stmt->execute();
                }
            }

            // Update quotation settings next number
            $stmt = $conn->prepare("
                UPDATE quotation_settings 
                SET next_number = next_number + 1 
                WHERE company_id = ?
            ");
            $stmt->bind_param("i", $company['company_id']);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            
            header("Location: view_quotation.php?id=" . $quotation_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// After client selection
if (isset($_POST['client_id'])) {
    // Get ML predictions
    $stmt = $conn->prepare("
        SELECT 
            AVG(total_amount) as avg_amount,
            COUNT(*) as total_quotations,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) / COUNT(*) as acceptance_rate
        FROM quotations 
        WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->bind_param("i", $_POST['client_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $client_stats = $result->fetch_assoc();

    // Get frequently ordered items
    $stmt = $conn->prepare("
        SELECT 
            qi.description,
            qi.unit_price,
            COUNT(*) as frequency,
            AVG(qi.quantity) as avg_quantity
        FROM quotation_items qi
        JOIN quotations q ON q.quotation_id = qi.quotation_id
        WHERE q.client_id = ?
        GROUP BY qi.description, qi.unit_price
        ORDER BY frequency DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $_POST['client_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $frequent_items = [];
    while ($row = $result->fetch_assoc()) {
        $frequent_items[] = $row;
    }
}

// Function to get suggested items based on title using ML
function getSuggestedItems($title) {
    // Common project types and their suggested items
    $projectTypes = [
        // Web Development
        'website' => [
            ['description' => 'Website Design & Development', 'default_price' => 25000],
            ['description' => 'Responsive Design Implementation', 'default_price' => 10000],
            ['description' => 'Content Management System', 'default_price' => 15000],
            ['description' => 'SEO Integration', 'default_price' => 8000],
            ['description' => 'Security Implementation & SSL', 'default_price' => 12000]
        ],
        'ecommerce' => [
            ['description' => 'E-commerce Platform Development', 'default_price' => 35000],
            ['description' => 'Payment Gateway Integration', 'default_price' => 12000],
            ['description' => 'Product Management System', 'default_price' => 18000],
            ['description' => 'Order Processing System', 'default_price' => 15000],
            ['description' => 'Inventory Management Module', 'default_price' => 20000]
        ],
        // Mobile Development
        'mobile' => [
            ['description' => 'Mobile App Development', 'default_price' => 40000],
            ['description' => 'UI/UX Design', 'default_price' => 15000],
            ['description' => 'API Integration', 'default_price' => 20000],
            ['description' => 'Push Notification System', 'default_price' => 10000],
            ['description' => 'App Store Deployment', 'default_price' => 5000]
        ],
        // Enterprise Software
        'erp' => [
            ['description' => 'ERP System Implementation', 'default_price' => 150000],
            ['description' => 'Business Process Automation', 'default_price' => 75000],
            ['description' => 'Data Migration & Integration', 'default_price' => 50000],
            ['description' => 'User Training Program', 'default_price' => 35000],
            ['description' => 'Support & Maintenance (Annual)', 'default_price' => 45000]
        ],
        // Digital Services
        'digital' => [
            ['description' => 'Digital Marketing Strategy', 'default_price' => 25000],
            ['description' => 'Social Media Management', 'default_price' => 15000],
            ['description' => 'Content Marketing Plan', 'default_price' => 20000],
            ['description' => 'SEO Optimization', 'default_price' => 18000],
            ['description' => 'Analytics & Reporting', 'default_price' => 12000]
        ],
        // Cloud Services
        'cloud' => [
            ['description' => 'Cloud Infrastructure Setup', 'default_price' => 75000],
            ['description' => 'Cloud Migration Services', 'default_price' => 100000],
            ['description' => 'Cloud Security Implementation', 'default_price' => 45000],
            ['description' => 'Backup & Recovery Setup', 'default_price' => 35000],
            ['description' => 'Performance Optimization', 'default_price' => 25000]
        ],
        // Custom Software
        'software' => [
            ['description' => 'Custom Software Development', 'default_price' => 125000],
            ['description' => 'System Integration', 'default_price' => 75000],
            ['description' => 'Database Design & Development', 'default_price' => 45000],
            ['description' => 'Testing & Quality Assurance', 'default_price' => 35000],
            ['description' => 'Documentation & Training', 'default_price' => 25000]
        ]
    ];
    
    // Keywords mapping for better matching
    $keywordMap = [
        // Web Development
        'web' => 'website',
        'portal' => 'website',
        'cms' => 'website',
        'wordpress' => 'website',
        'shop' => 'ecommerce',
        'store' => 'ecommerce',
        'marketplace' => 'ecommerce',
        
        // Mobile Development
        'app' => 'mobile',
        'android' => 'mobile',
        'ios' => 'mobile',
        'flutter' => 'mobile',
        'react native' => 'mobile',
        
        // Enterprise Software
        'enterprise' => 'erp',
        'erp' => 'erp',
        'business' => 'erp',
        'automation' => 'erp',
        
        // Digital Services
        'marketing' => 'digital',
        'seo' => 'digital',
        'social media' => 'digital',
        'digital' => 'digital',
        
        // Cloud Services
        'aws' => 'cloud',
        'azure' => 'cloud',
        'cloud' => 'cloud',
        'hosting' => 'cloud',
        
        // Custom Software
        'software' => 'software',
        'application' => 'software',
        'system' => 'software'
    ];
    
    $title = strtolower($title);
    $words = explode(' ', $title);
    $matchedItems = [];
    
    // Check each word in the title against our keyword map
    foreach ($words as $word) {
        if (isset($keywordMap[$word]) && isset($projectTypes[$keywordMap[$word]])) {
            $matchedItems = array_merge($matchedItems, $projectTypes[$keywordMap[$word]]);
        }
    }
    
    // Direct check for project types
    foreach ($projectTypes as $type => $items) {
        if (strpos($title, $type) !== false) {
            $matchedItems = array_merge($matchedItems, $items);
        }
    }
    
    // Remove duplicates based on description
    $uniqueItems = [];
    foreach ($matchedItems as $item) {
        $uniqueItems[$item['description']] = $item;
    }
    
    return array_slice(array_values($uniqueItems), 0, 5);
}

// Add this before the form HTML
$suggested_items = [];
$quotation_title = '';

// Only process if it's a POST request specifically for analyzing title
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze_title'])) {
    $quotation_title = $_POST['quotation_title'];
    $suggested_items = getSuggestedItems($quotation_title);
    
    // If no items found, set a message
    if (empty($suggested_items)) {
        $noSuggestionsMessage = "We couldn't generate specific suggestions for this type of project. Please add your custom items below.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quotation</title>
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
            width: 200px;
            background: white;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }
        
        .content {
            flex: 1;
            margin-left: 200px;
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
        
        .nav-item {
            position: relative;
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4e73df;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Welcome card */
        .welcome-card {
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Cards */
        .card {
            margin-bottom: 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .card-header i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Form elements */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.375rem 0.75rem;
            border: 1px solid #d1d3e2;
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        label.form-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* Tables */
        .table th {
            font-size: 0.8rem;
            text-transform: uppercase;
            background-color: #f8f9fc;
            border-color: #e3e6f0;
            color: #6e707e;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        
        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        /* Icons */
        .icon-circle {
            height: 2rem;
            width: 2rem;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            background-color: #eaecf4;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
            }
            
            .content {
                margin-left: 0;
            }
        }

        .clear-title {
            border-left: none;
            padding: 0 12px;
        }
        .clear-title:hover {
            background-color: #e9ecef;
        }
        .input-group .form-control:focus + .clear-title {
            border-color: #86b7fe;
        }

        #itemsTable .input-group {
            flex-wrap: nowrap;
        }
        #itemsTable .input-group-text {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        #itemsTable input[type="number"] {
            text-align: right;
        }
        #itemsTable .tax-rate {
            min-width: 60px;
        }
        #itemsTable th {
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        /* Adjust input field widths */
        .unit-price-col {
            width: 180px !important;
        }
        .tax-rate-col {
            width: 150px !important;
        }
        .total-col {
            width: 180px !important;
        }

        /* Make summary box more compact */
        .summary-box {
            max-width: 300px;
            margin-left: auto;
        }

        /* Adjust input group widths */
        .input-group.amount-input {
            width: 180px !important;
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
                <a href="create_quotation.php" class="nav-link active">
                    <i class="fas fa-plus"></i> Create Quotation
                </a>
                <a href="list_quotations.php" class="nav-link">
                    <i class="fas fa-list"></i> All Quotations
                </a>
                
                <div class="sidebar-heading">OTHER</div>
                <a href="quotation_settings.php" class="nav-link">
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
                    <h4 class="mb-0">Create Quotation</h4>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h4>Create New Quotation</h4>
                <p>Fill out the form below to create a professional quotation for your client.</p>
                <a href="list_quotations.php" class="btn btn-light">
                    <i class="fas fa-list me-2"></i>View All Quotations
                </a>
            </div>
            
            <!-- Alert if error -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Smart Content Generator -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-magic"></i>
                    <h5>Smart Content Generator</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3" id="smartContentForm">
                        <div class="col-md-9">
                            <label class="form-label">Quotation Title</label>
                            <div class="input-group">
                                <input type="text" name="quotation_title" class="form-control" 
                                       value="<?= htmlspecialchars($quotation_title) ?>"
                                       placeholder="e.g., Website Development, Mobile App, ERP System" required>
                                <button type="button" class="btn btn-outline-secondary clear-title" title="Clear title">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="analyze_title" class="btn btn-primary w-100">
                                <i class="fas fa-wand-magic-sparkles me-2"></i>
                                Generate Content
                            </button>
                        </div>
                    </form>

                    <?php if (isset($noSuggestionsMessage)): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= htmlspecialchars($noSuggestionsMessage) ?>
                    </div>
                    <?php endif; ?>

                    <div id="suggestedItemsContainer" <?= empty($suggested_items) ? 'style="display:none;"' : '' ?>>
                        <div class="mt-4">
                            <h6 class="mb-3">Suggested Items</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Suggested Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suggested_items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['description']) ?></td>
                                            <td>₹<?= number_format($item['default_price'], 2) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary add-suggested-item"
                                                        data-description="<?= htmlspecialchars($item['description']) ?>"
                                                        data-price="<?= $item['default_price'] ?>">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-sm add-all-items">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Add All Items
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-9">
                    <!-- Main Form -->
                    <form id="quotationForm" method="POST" class="needs-validation" novalidate>
                        <!-- Add hidden input for quotation title -->
                        <input type="hidden" name="quotation_title" value="<?= isset($_POST['quotation_title']) ? htmlspecialchars($_POST['quotation_title']) : '' ?>">
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-file-invoice"></i>
                                <h5>Quotation Details</h5>
                            </div>
                            <div class="card-body">
                                <!-- Client Selection Section -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Quotation Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-hashtag text-primary"></i>
                                            </span>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($quotation_number) ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-calendar-alt text-primary"></i>
                                            </span>
                                            <input type="date" name="quotation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-9">
                                        <label class="form-label">Client <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-user text-primary"></i>
                                            </span>
                                            <select name="client_id" id="client_select" class="form-select" required>
                                                <option value="">Select Client</option>
                                                <?php foreach ($clients as $client): ?>
                                                <option value="<?= $client['client_id'] ?>" <?= (isset($_POST['client_id']) && $_POST['client_id'] == $client['client_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($client['client_name']) ?> (<?= htmlspecialchars($client['email']) ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                                <i class="fas fa-plus"></i> New
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Valid Until</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-hourglass-half text-primary"></i>
                                            </span>
                                            <input type="date" name="valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+' . $settings['validity_days'] . ' days')) ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0">Items</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="itemsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th width="100">Quantity</th>
                                                        <th class="unit-price-col">Unit Price</th>
                                                        <th class="tax-rate-col">Tax Rate</th>
                                                        <th class="total-col">Total</th>
                                                        <th width="40"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="item-row">
                                                        <td>
                                                            <input type="text" name="items[0][description]" class="form-control" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="items[0][quantity]" class="form-control quantity" value="1" min="1" required>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₹</span>
                                                                <input type="number" name="items[0][unit_price]" class="form-control unit-price" value="0.00" min="0" step="0.01" required>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <input type="number" name="items[0][tax_rate]" class="form-control tax-rate" value="<?= $settings['default_tax_rate'] ?>" min="0" max="100" required>
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₹</span>
                                                                <input type="number" name="items[0][total_amount]" class="form-control item-total" readonly>
                                                            </div>
                                                            <input type="hidden" name="items[0][tax_amount]" class="tax-amount" value="0">
                                                            <input type="hidden" name="items[0][discount_rate]" value="0">
                                                            <input type="hidden" name="items[0][discount_amount]" value="0">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="6" class="py-2">
                                                            <button type="button" class="btn btn-success btn-sm" id="addItem">
                                                                <i class="fas fa-plus me-1"></i> Add Item
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scope Section -->
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-contract text-primary me-2"></i>
                                            <h6 class="mb-0">Scope [Assumptions & Dependencies]</h6>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <textarea name="scope" class="form-control" rows="3" placeholder="✓ Customer enquiries from contact us page, feedback page etc. must be managed by client itself&#10;✓ 10 working days to complete the project once the requirement is freeze."></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary suggest-scope">
                                                <i class="fas fa-magic me-1"></i> Suggest Scope
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Terms Section -->
                                <div class="card mb-3">
                                    <div class="card-header bg-light py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-credit-card text-primary me-2"></i>
                                            <h6 class="mb-0">Mode of Payment</h6>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <textarea name="payment_terms" class="form-control" rows="3" placeholder="50% Advance - against purchase order&#10;50% Balance payment - against delivery&#10;All the payments must be made in Cheque/DD in the name of your company."></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary suggest-payment">
                                                <i class="fas fa-magic me-1"></i> Suggest Payment Terms
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes and Terms -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about this quotation..."></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">    
                                        <div class="mb-3">
                                            <label class="form-label">Terms & Conditions</label>
                                            <textarea name="terms_conditions" class="form-control" rows="3"><?= htmlspecialchars($settings['default_terms']) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                <div class="col-md-3">
                    <!-- Summary Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Subtotal</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="subtotal" class="form-control" id="subtotal" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="tax_amount" class="form-control" id="taxAmount" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" name="discount_rate" class="form-control" id="discountRate" value="<?= $settings['default_discount_rate'] ?>" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="discount_amount" class="form-control" id="discountAmount" readonly>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" name="total_amount" class="form-control form-control-lg fw-bold" id="totalAmount" readonly>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-save me-2"></i> Create Quotation
                            </button>
                        </div>
                    </div>
                    
                    <!-- Smart Insights Panel -->
                    <?php if (isset($client_stats)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-brain"></i>
                            <h5>Smart Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="d-flex justify-content-between">
                                    <div class="text-center">
                                        <div class="mb-1">Average Order</div>
                                        <div class="h5">₹<?= number_format($client_stats['avg_amount'], 0) ?></div>
                                    </div>
                                    <div class="text-center">
                                        <div class="mb-1">Accept Rate</div>
                                        <div class="h5"><?= number_format($client_stats['acceptance_rate'] * 100, 0) ?>%</div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($frequent_items)): ?>
                            <div class="mt-3">
                                <h6 class="mb-2">Frequently Ordered Items</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($frequent_items as $item): ?>
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small text-truncate" style="max-width: 200px;"><?= htmlspecialchars($item['description']) ?></div>
                                            <small class="text-muted">₹<?= number_format($item['unit_price'], 0) ?> × <?= number_format($item['avg_quantity'], 1) ?></small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary add-frequent-item"
                                                data-description="<?= htmlspecialchars($item['description']) ?>"
                                                data-quantity="<?= round($item['avg_quantity']) ?>"
                                                data-price="<?= $item['unit_price'] ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-brain"></i>
                            <h5>Smart Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle mb-2 fa-2x text-muted"></i>
                                <p class="mb-0">Select a client to view smart insights</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addClientForm">
                        <div class="form-group">
                            <label for="client_name">Client Name *</label>
                            <input type="text" class="form-control" id="client_name" name="client_name" required>
                        </div>
                        <div class="form-group">
                            <label for="client_email">Email *</label>
                            <input type="email" class="form-control" id="client_email" name="client_email" required>
                        </div>
                        <div class="form-group">
                            <label for="client_gstin">GST Number</label>
                            <input type="text" class="form-control" id="client_gstin" name="client_gstin">
                        </div>
                        <div class="form-group">
                            <label for="client_state">State *</label>
                            <select class="form-control" id="client_state" name="client_state" required>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="Gujarat">Gujarat</option>
                                <option value="Delhi">Delhi</option>
                                <option value="Karnataka">Karnataka</option>
                                <option value="Tamil Nadu">Tamil Nadu</option>
                                <!-- Add more states as needed -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="client_address">Address</label>
                            <textarea class="form-control" id="client_address" name="client_address" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveClientBtn">
                        <i class="fas fa-save me-1"></i> Save Client
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Alert for Item Addition -->
    <div class="alert alert-success alert-dismissible fade" role="alert" id="itemAddedAlert" style="position: fixed; top: 20px; right: 20px; z-index: 1050; display: none;">
        <i class="fas fa-check-circle me-2"></i>
        <span id="itemAddedMessage">Item added successfully!</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <script>
    // Add this at the beginning of your script section
    document.addEventListener('DOMContentLoaded', function() {
        // Clear button functionality
        const titleInput = document.querySelector('input[name="quotation_title"]');
        const clearButton = document.querySelector('.clear-title');
        
        clearButton.addEventListener('click', function() {
            titleInput.value = '';
            titleInput.focus();
        });

        // Clear form on page load if it's not a POST request
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD) {
            document.getElementById('smartContentForm').reset();
            document.getElementById('suggestedItemsContainer').style.display = 'none';
            titleInput.value = ''; // Ensure the title is cleared
        }
    });

    // Handle Save Client functionality
    document.getElementById('saveClientBtn').addEventListener('click', function() {
        // Get form values
        const name = document.getElementById('client_name').value;
        const email = document.getElementById('client_email').value;
        const gstin = document.getElementById('client_gstin').value;
        const state = document.getElementById('client_state').value;
        const address = document.getElementById('client_address').value;
        
        if (!name || !email) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('gstin', gstin);
        formData.append('state', state);
        formData.append('address', address);
        formData.append('action', 'add_client');
        
        // Send AJAX request
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new client to the dropdown
                const select = document.getElementById('client_select');
                const option = document.createElement('option');
                option.value = data.client_id;
                option.text = name + ' (' + email + ')';
                option.selected = true;
                select.appendChild(option);
                
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addClientModal'));
                modal.hide();
                
                // Show success message
                alert('Client added successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the client.');
        });
    });

    // Function to show success alert
    function showSuccessAlert(message) {
        const alert = document.getElementById('itemAddedAlert');
        document.getElementById('itemAddedMessage').textContent = message;
        alert.style.display = 'block';
        alert.classList.add('show');
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alert.style.display = 'none';
            }, 150);
        }, 3000);
    }

    // Function to add a new item row
    function addNewItemRow() {
        const tbody = document.querySelector('#itemsTable tbody');
        const rowCount = tbody.getElementsByClassName('item-row').length;
        const newRow = document.createElement('tr');
        newRow.className = 'item-row';
        
        newRow.innerHTML = `
            <td>
                <input type="text" name="items[${rowCount}][description]" class="form-control" required>
            </td>
            <td>
                <input type="number" name="items[${rowCount}][quantity]" class="form-control quantity" value="1" min="1" required>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" name="items[${rowCount}][unit_price]" class="form-control unit-price" value="0.00" min="0" step="0.01" required>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" name="items[${rowCount}][tax_rate]" class="form-control tax-rate" value="<?= $settings['default_tax_rate'] ?>" min="0" max="100" required>
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" name="items[${rowCount}][total_amount]" class="form-control item-total" readonly>
                </div>
                <input type="hidden" name="items[${rowCount}][tax_amount]" class="tax-amount" value="0">
                <input type="hidden" name="items[${rowCount}][discount_rate]" value="0">
                <input type="hidden" name="items[${rowCount}][discount_amount]" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-item">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        initializeRowEvents(newRow);
        return newRow;
    }

    // Function to calculate item total
    function calculateItemTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
        
        const subtotal = quantity * unitPrice;
        const taxAmount = (subtotal * taxRate) / 100;
        const total = subtotal + taxAmount;
        
        row.querySelector('.tax-amount').value = taxAmount.toFixed(2);
        row.querySelector('.item-total').value = total.toFixed(2);
        
        calculateTotals();
    }

    // Function to calculate overall totals
    function calculateTotals() {
        let subtotal = 0;
        let totalTax = 0;
        
        document.querySelectorAll('.item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const taxAmount = parseFloat(row.querySelector('.tax-amount').value) || 0;
            
            subtotal += quantity * unitPrice;
            totalTax += taxAmount;
        });
        
        const discountRate = parseFloat(document.getElementById('discountRate').value) || 0;
        const discountAmount = (subtotal * discountRate) / 100;
        
        const total = subtotal + totalTax - discountAmount;
        
        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('taxAmount').value = totalTax.toFixed(2);
        document.getElementById('discountAmount').value = discountAmount.toFixed(2);
        document.getElementById('totalAmount').value = total.toFixed(2);
    }

    // Initialize row events
    function initializeRowEvents(row) {
        const inputs = row.querySelectorAll('.quantity, .unit-price, .tax-rate');
        inputs.forEach(input => {
            input.addEventListener('input', () => calculateItemTotal(row));
        });
        
        row.querySelector('.remove-item').addEventListener('click', function() {
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
                calculateTotals();
            } else {
                alert('At least one item is required.');
            }
        });
    }

    // Handle suggested items
    document.querySelectorAll('.add-suggested-item').forEach(button => {
        button.addEventListener('click', function() {
            const description = this.dataset.description;
            const price = parseFloat(this.dataset.price);
            
            const newRow = addNewItemRow();
            newRow.querySelector('input[name$="[description]"]').value = description;
            newRow.querySelector('input[name$="[unit_price]"]').value = price.toFixed(2);
            calculateItemTotal(newRow);
            
            showSuccessAlert('Item "' + description + '" added successfully!');
        });
    });

    // Handle "Add All Items" button
    document.querySelector('.add-all-items')?.addEventListener('click', function() {
        const tbody = document.querySelector('#itemsTable tbody');
        // Clear existing first empty row
        if (tbody.querySelectorAll('.item-row').length === 1 && 
            !tbody.querySelector('input[name$="[description]"]').value) {
            tbody.querySelector('.item-row').remove();
        }
        
        document.querySelectorAll('.add-suggested-item').forEach(button => {
            const description = button.dataset.description;
            const price = parseFloat(button.dataset.price);
            
            const newRow = addNewItemRow();
            newRow.querySelector('input[name$="[description]"]').value = description;
            newRow.querySelector('input[name$="[unit_price]"]').value = price.toFixed(2);
            calculateItemTotal(newRow);
        });
        
        showSuccessAlert('All suggested items added successfully!');
    });

    // Handle discount rate changes
    document.getElementById('discountRate').addEventListener('input', calculateTotals);

    // Add new item button
    document.getElementById('addItem').addEventListener('click', addNewItemRow);

    // Initialize existing rows
    document.querySelectorAll('.item-row').forEach(row => initializeRowEvents(row));

    // Handle scope suggestions
    document.querySelector('.suggest-scope').addEventListener('click', function() {
        const title = document.querySelector('input[name="quotation_title"]').value.toLowerCase();
        let scope = '';
        
        if (title.includes('website') || title.includes('web')) {
            scope = `✓ Website will be responsive and compatible with all modern browsers
✓ Content Management System (CMS) for easy updates
✓ SEO-friendly structure and optimization
✓ Contact forms with spam protection
✓ Integration with Google Analytics
✓ 3 rounds of revisions included
✓ 30 days of post-launch support`;
        } else if (title.includes('mobile') || title.includes('app')) {
            scope = `✓ Native mobile application for Android and iOS
✓ User authentication and profile management
✓ Push notification system
✓ Offline data synchronization
✓ API integration and backend development
✓ App store submission assistance
✓ 3 months of technical support`;
        } else if (title.includes('hardware') || title.includes('network')) {
            scope = `✓ Complete hardware installation and configuration
✓ Network setup and security implementation
✓ Data migration from existing systems
✓ Staff training on new equipment
✓ Documentation of network architecture
✓ 24/7 technical support
✓ Quarterly maintenance visits`;
        } else if (title.includes('security') || title.includes('cctv')) {
            scope = `✓ Security system design and implementation
✓ Installation of all security equipment
✓ Configuration of monitoring systems
✓ Staff training on security protocols
✓ 24/7 monitoring and alert system
✓ Regular security audits
✓ Emergency response protocol`;
        } else if (title.includes('erp') || title.includes('enterprise')) {
            scope = `✓ Complete business process analysis
✓ Customized ERP solution implementation
✓ Data migration and integration
✓ User training and documentation
✓ System testing and quality assurance
✓ Go-live support and monitoring
✓ 6 months post-implementation support`;
        } else if (title.includes('government') || title.includes('gov')) {
            scope = `✓ Compliance with government regulations and standards
✓ Secure data handling and storage
✓ Integration with existing government systems
✓ Comprehensive documentation and training
✓ Security audits and certifications
✓ Regular maintenance and updates
✓ 24/7 technical support`;
        } else {
            scope = `✓ Detailed project planning and analysis
✓ Regular progress updates and reporting
✓ Quality assurance and testing
✓ Documentation and knowledge transfer
✓ User training and support
✓ Post-implementation maintenance
✓ 3 months warranty period`;
        }
        
        document.querySelector('textarea[name="scope"]').value = scope;
        showSuccessAlert('Scope has been automatically generated!');
    });

    // Handle payment terms suggestions
    document.querySelector('.suggest-payment').addEventListener('click', function() {
        const title = document.querySelector('input[name="quotation_title"]').value.toLowerCase();
        let terms = '';
        
        if (title.includes('website') || title.includes('mobile') || title.includes('software')) {
            terms = `40% - Advance payment against purchase order
30% - After completion of development and UAT
30% - After successful deployment and training`;
        } else if (title.includes('hardware') || title.includes('network')) {
            terms = `50% - Advance payment against purchase order
40% - After delivery and installation
10% - After testing and sign-off`;
        } else if (title.includes('maintenance') || title.includes('amc')) {
            terms = `100% - Advance payment for annual contract
Note: Quarterly/Monthly payment options available
Hardware parts will be billed separately`;
        } else {
            terms = `50% - Advance payment against purchase order
30% - On completion of implementation
20% - After final testing and sign-off`;
        }
        
        document.querySelector('textarea[name="payment_terms"]').value = terms;
        showSuccessAlert('Payment terms have been automatically generated!');
    });

    // Initialize calculations for first row
    calculateTotals();
    </script>
</body>
</html> 