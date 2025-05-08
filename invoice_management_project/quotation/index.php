<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

// Get statistics
try {
    $stats = [];
    
    // Total quotations count and sum
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(total_amount) as total_amount
        FROM quotations 
        WHERE company_id = ?
    ");
    $stmt->bind_param("i", $company['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total'] = $row['total'] ?? 0;
    $stats['total_amount'] = $row['total_amount'] ?? 0;
    
    // Quotation counts by status
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM quotations 
        WHERE company_id = ?
        GROUP BY status
    ");
    $stmt->bind_param("i", $company['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Initialize status counts
    $stats['pending'] = 0;
    $stats['accepted'] = 0;
    $stats['rejected'] = 0;
    $stats['draft'] = 0;
    
    // Fill in actual counts
    while ($row = $result->fetch_assoc()) {
        if (isset($row['status']) && isset($row['count'])) {
            $stats[$row['status']] = $row['count'];
        }
    }
    
    // Get recent quotations
    $stmt = $conn->prepare("
        SELECT q.*, c.name as client_name, c.email as client_email 
        FROM quotations q 
        JOIN client_master c ON q.client_id = c.client_id 
        WHERE q.company_id = ? 
        ORDER BY q.created_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $company['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_quotations = [];
    while ($row = $result->fetch_assoc()) {
        $recent_quotations[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Error fetching quotation statistics: " . $e->getMessage());
    $stats = [
        'total' => 0,
        'total_amount' => 0,
        'pending' => 0,
        'accepted' => 0,
        'rejected' => 0,
        'draft' => 0
    ];
    $recent_quotations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Dashboard</title>
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
        
        /* Stat cards */
        .stat-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stat-card h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #5a5c69;
            margin: 0.5rem 0;
        }
        
        .stat-card p {
            color: #6e707e;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .stat-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-icon.revenue {
            background-color: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }
        
        .stat-icon.pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: #f6c23e;
        }
        
        .stat-icon.rejected {
            background-color: rgba(231, 74, 59, 0.1);
            color: #e74a3b;
        }
        
        /* Tables */
        .table {
            color: #5a5c69;
            margin-bottom: 0;
        }
        
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
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 1.875rem;
            height: 1.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.25rem;
            color: #6e707e;
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            transition: all 0.15s;
        }
        
        .action-btn:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
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
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
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
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="create_quotation.php" class="nav-link">
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
                    <h4 class="mb-0">Quotation Dashboard</h4>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h4>Welcome to Quotation Management</h4>
                <p>Create and manage your quotations efficiently.</p>
                <div class="d-flex gap-2">
                    <a href="create_quotation.php" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Create New Quotation
                    </a>
                    <a href="list_quotations.php" class="btn btn-outline-light">
                        <i class="fas fa-list me-2"></i>View All Quotations
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h2><?= number_format($stats['total']) ?></h2>
                            <p>Total Quotations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2><?= number_format($stats['accepted']) ?></h2>
                            <p>Accepted Quotations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h2><?= number_format($stats['pending']) ?></h2>
                            <p>Pending Quotations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon rejected">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <h2><?= number_format($stats['rejected']) ?></h2>
                            <p>Rejected Quotations</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                            <h2>₹<?= number_format($stats['total_amount']) ?></h2>
                            <p>Total Quotation Value</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-pencil-alt"></i>
                            </div>
                            <h2><?= number_format($stats['draft']) ?></h2>
                            <p>Draft Quotations</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Quotations -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-history"></i>
                        Recent Quotations
                    </h5>
                    <a href="list_quotations.php" class="btn btn-primary btn-sm">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Quotation #</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_quotations)): ?>
                                    <?php foreach ($recent_quotations as $index => $quotation): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($quotation['quotation_number']) ?></td>
                                        <td><?= date('d M Y', strtotime($quotation['created_at'])) ?></td>
                                        <td>
                                            <div>
                                                <span><?= htmlspecialchars($quotation['client_name']) ?></span>
                                                <div class="small text-muted"><?= htmlspecialchars($quotation['client_email']) ?></div>
                                            </div>
                                        </td>
                                        <td>₹<?= number_format($quotation['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $quotation['status'] === 'accepted' ? 'success' : 
                                                ($quotation['status'] === 'rejected' ? 'danger' : 
                                                ($quotation['status'] === 'draft' ? 'secondary' : 'warning')) 
                                            ?>">
                                                <?= ucfirst($quotation['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                   class="action-btn" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="download_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                   class="action-btn" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="send_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                   class="action-btn" title="Send">
                                                    <i class="fas fa-paper-plane"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No quotations found</p>
                                            <a href="create_quotation.php" class="btn btn-primary btn-sm mt-2">
                                                <i class="fas fa-plus me-1"></i> Create New Quotation
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 