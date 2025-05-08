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

// Get filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;

// Get company ID from session
$company_id = $company['company_id'];

// Build query conditions
$where = ["q.company_id = ?"]; // Using ? for MySQLi
$params = [$company_id];
$types = "i"; // Integer for company_id

if ($status) {
    $where[] = "q.status = ?";
    $params[] = $status;
    $types .= "s"; // String for status
}

if ($search) {
    $where[] = "(q.quotation_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss"; // Three strings for search parameters
}

$where_clause = implode(" AND ", $where);

// Get total count
$count_query = "
    SELECT COUNT(*) as total
    FROM quotations q 
    JOIN client_master c ON q.client_id = c.client_id 
    WHERE $where_clause
";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_records = $result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// Fetch quotations
$query = "
    SELECT 
        q.*, 
        c.name as client_name,
        c.email,
        DATE_ADD(q.quotation_date, INTERVAL q.validity_days DAY) as valid_until,
        (SELECT COUNT(*) FROM quotation_items WHERE quotation_id = q.quotation_id) as items_count
    FROM quotations q 
    JOIN client_master c ON q.client_id = c.client_id 
    WHERE $where_clause
    ORDER BY q.$sort $order
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
$types .= "ii"; // Two integers for LIMIT parameters
$params[] = $offset;
$params[] = $per_page;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$quotations = [];
while ($row = $result->fetch_assoc()) {
    $quotations[] = $row;
}

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(total_amount) as total_amount
    FROM quotations 
    WHERE company_id = ?
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations List</title>
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
        
        /* Cards */
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }
        
        /* Stats */
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .stat-icon.pending {
            color: #f6c23e;
        }
        
        .stat-icon.revenue {
            color: #1cc88a;
        }
        
        .stat-icon.rejected {
            color: #e74a3b;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        /* Content Header */
        .content-header {
            padding: 0 0 1rem 0;
        }
        
        .content-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .breadcrumb {
            margin-bottom: 0;
            background: transparent;
            padding: 0;
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
                <a href="list_quotations.php" class="nav-link active">
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
                    <h4 class="mb-0">All Quotations</h4>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="welcome-card">
                <h4>Manage Your Quotations</h4>
                <p>View, edit, and manage all your quotations in one place.</p>
                <a href="create_quotation.php" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i>Create New Quotation
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">All Quotations</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">All Quotations</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="stat-icon">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <h2><?= number_format($stats['total'] ?? 0) ?></h2>
                                <p class="text-muted">Total Quotations</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="stat-icon pending">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h2><?= number_format($stats['pending'] ?? 0) ?></h2>
                                <p class="text-muted">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="stat-icon revenue">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h2><?= number_format($stats['accepted'] ?? 0) ?></h2>
                                <p class="text-muted">Accepted</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="stat-icon rejected">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <h2><?= number_format($stats['rejected'] ?? 0) ?></h2>
                                <p class="text-muted">Rejected</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">Quotation List</h5>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <a href="create_quotation.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create New Quotation
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search quotations..." value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Sort by Date</option>
                                    <option value="total_amount" <?= $sort === 'total_amount' ? 'selected' : '' ?>>Sort by Amount</option>
                                    <option value="quotation_number" <?= $sort === 'quotation_number' ? 'selected' : '' ?>>Sort by Number</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="order" class="form-select" onchange="this.form.submit()">
                                    <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                                    <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                                </select>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quotation #</th>
                                        <th>Client</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Valid Until</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($quotations)): ?>
                                        <?php foreach ($quotations as $quotation): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($quotation['quotation_number']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($quotation['client_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($quotation['email']) ?></small>
                                            </td>
                                            <td><?= number_format($quotation['items_count']) ?> items</td>
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
                                            <td><?= date('d M Y', strtotime($quotation['valid_until'])) ?></td>
                                            <td><?= date('d M Y', strtotime($quotation['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (in_array($quotation['status'], ['pending', 'draft'])): ?>
                                                    <a href="edit_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="send_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-sm btn-success" title="Send">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </a>
                                                    <a href="download_quotation.php?id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-sm btn-primary" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
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

                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">
                                        Previous
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 