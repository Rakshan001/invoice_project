<?php
session_start();
require_once 'config/database.php';

// Function to format numbers in Indian currency format (1,23,456.00)
function formatIndianCurrency($num) {
    // Remove any non-numeric characters
    $num = preg_replace('/[^0-9.]/', '', $num);
    
    // Split number and decimal parts
    $parts = explode('.', $num);
    $whole = $parts[0];
    $decimal = isset($parts[1]) ? $parts[1] : '';
    
    // Pad or truncate decimal to 2 places
    if (!empty($decimal)) {
        $decimal = strlen($decimal) > 2 ? substr($decimal, 0, 2) : str_pad($decimal, 2, '0');
    } else {
        $decimal = '00';
    }
    
    // Format the whole number part with commas
    $formatted = '';
    
    // Get the last 3 digits
    $lastThree = strlen($whole) > 3 ? substr($whole, -3) : $whole;
    
    // Get the remaining digits
    $remaining = strlen($whole) > 3 ? substr($whole, 0, -3) : '';
    
    // Format the remaining digits
    if (!empty($remaining)) {
        // Break the remaining digits into chunks of 2
        $formatted = implode(',', array_map(function($item) {
            return str_pad($item, 2, '0', STR_PAD_LEFT);
        }, str_split(str_pad($remaining, ceil(strlen($remaining) / 2) * 2, '0', STR_PAD_LEFT), 2)));
        
        // Remove leading zeros
        $formatted = ltrim($formatted, '0');
        $formatted = ($formatted === '') ? '' : $formatted . ',';
    }
    
    // Add the last 3 digits
    $formatted .= $lastThree;
    
    // If decimal is .00, return without it
    if ($decimal === '00') {
        return $formatted;
    }
    
    return $formatted . '.' . $decimal;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get company data
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

// Get bank details
$stmt = $conn->prepare("SELECT * FROM company_bank WHERE company_id = ?");
$company_id = $company ? $company['company_id'] : 0;
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$bank = $result->fetch_assoc();
$stmt->close();

// Get invoice stats
$invoice_count = 0;
$total_amount = 0;
$recent_invoices = [];

// Calculate profile completion percentage
$completion = 0;
if ($user) $completion += 33;
if ($company) $completion += 33;
if ($bank) $completion += 34;

if ($company) {
    // Count total invoices
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM invoice WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $invoice_count = $row['count'];
    $stmt->close();
    
    // Get total amount
    $stmt = $conn->prepare("SELECT SUM(net_total) as total FROM invoice WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_amount = $row['total'] ?: 0;
    $stmt->close();
    
    // Get recent invoices
    $stmt = $conn->prepare("
        SELECT i.invoice_id, i.invoice_number, i.invoice_date, i.client_name, i.net_total
        FROM invoice i
        WHERE i.company_id = ?
        ORDER BY i.invoice_date DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_invoices = [];
    while ($row = $result->fetch_assoc()) {
        $recent_invoices[] = $row;
    }
    $stmt->close();
    
    // Get clients count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM client_master WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $clients_count = $row['count'];
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8 col-md-12">
            <div class="welcome-card">
                <div class="welcome-text">
                    <h4 class="mb-0">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h4>
                    <p class="text-muted mb-0">Here's what's happening with your business today.</p>
                </div>
                <div class="welcome-img">
                    <img src="https://cdn-icons-png.flaticon.com/512/3588/3588614.png" alt="Dashboard" width="80">
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mt-3 mt-lg-0">
            <div class="header-actions text-md-end">
                <a href="coming-soon.php" class="btn btn-success me-2">
                    <i class="fas fa-file-contract me-2"></i>New Quotation
                </a>
                <a href="create_invoice.php" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-2"></i>New Invoice
                </a>
                <a href="clients.php?action=add" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Add Client
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-info">
                    <span class="stat-card-title">Total Invoices</span>
                    <h2 class="stat-card-value"><?php echo number_format($invoice_count); ?></h2>
                    <span class="stat-card-desc">All time invoices created</span>
                </div>
                <div class="stat-card-icon bg-gradient-primary">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-info">
                    <span class="stat-card-title">Total Revenue</span>
                    <h2 class="stat-card-value">₹<?php echo formatIndianCurrency($total_amount); ?></h2>
                    <span class="stat-card-desc">All time revenue generated</span>
                </div>
                <div class="stat-card-icon bg-gradient-success">
                    <i class="fas fa-rupee-sign"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="stat-card-info">
                    <span class="stat-card-title">Total Clients</span>
                    <h2 class="stat-card-value"><?php echo number_format($clients_count); ?></h2>
                    <span class="stat-card-desc">Active clients in your business</span>
                </div>
                <div class="stat-card-icon bg-gradient-info">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Setup Steps Section -->
    <?php if ($completion < 100): ?>
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Complete Your Setup</h5>
                <span class="badge bg-info"><?php echo $completion; ?>% Complete</span>
            </div>
            <div class="card-body pb-0">
                <div class="setup-progress mb-3">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $completion; ?>%" aria-valuenow="<?php echo $completion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="row g-3">
                    <?php if (!$company): ?>
                    <div class="col-md-4">
                        <div class="setup-card">
                            <div class="setup-icon bg-light text-primary">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="setup-info">
                                <h6>Company Details</h6>
                                <p class="text-muted small mb-0">Set up your company details</p>
                                <a href="company_details.php" class="btn btn-sm btn-primary mt-2">Set up now</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$bank && $company): ?>
                    <div class="col-md-4">
                        <div class="setup-card">
                            <div class="setup-icon bg-light text-primary">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="setup-info">
                                <h6>Bank Details</h6>
                                <p class="text-muted small mb-0">Add your banking information</p>
                                <a href="bank_details.php" class="btn btn-sm btn-primary mt-2">Set up now</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($company && $clients_count == 0): ?>
                    <div class="col-md-4">
                        <div class="setup-card">
                            <div class="setup-icon bg-light text-primary">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="setup-info">
                                <h6>Add Clients</h6>
                                <p class="text-muted small mb-0">Add clients to start invoicing</p>
                                <a href="clients.php?action=add" class="btn btn-sm btn-primary mt-2">Add now</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Invoices -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Invoices</h5>
                <a href="invoices.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table recent-invoices-table mb-0">
                        <tbody>
                            <?php if (count($recent_invoices) > 0): ?>
                                <?php foreach ($recent_invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="invoice-icon bg-light-primary text-primary">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div class="invoice-info ms-3">
                                                <p class="invoice-id mb-0 fw-bold">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                                                <p class="invoice-date mb-0 text-muted small"><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="invoice-client mb-0 text-truncate"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                                    </td>
                                    <td class="text-end">
                                        <p class="invoice-amount mb-0 fw-bold">₹<?php echo formatIndianCurrency($invoice['net_total']); ?></p>
                                        <a href="generate_pdf.php?invoice_id=<?php echo $invoice['invoice_id']; ?>&download=1" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center p-4">
                                        <div class="empty-state">
                                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="No Invoices" width="60">
                                            <p class="mt-2 mb-0">No invoices created yet</p>
                                            <a href="create_invoice.php" class="btn btn-sm btn-primary mt-2">Create Invoice</a>
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

    <!-- Quick Actions -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="create_invoice.php" class="quick-action-card">
                            <div class="quick-action-icon bg-gradient-primary">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="quick-action-content">
                                <h6>Create Invoice</h6>
                                <p class="mb-0">Generate a new invoice</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="clients.php?action=add" class="quick-action-card">
                            <div class="quick-action-icon bg-gradient-success">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="quick-action-content">
                                <h6>Add Client</h6>
                                <p class="mb-0">Register a new client</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="invoices.php" class="quick-action-card">
                            <div class="quick-action-icon bg-gradient-info">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="quick-action-content">
                                <h6>View Invoices</h6>
                                <p class="mb-0">Manage all invoices</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="email_templates.php" class="quick-action-card">
                            <div class="quick-action-icon bg-gradient-warning">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="quick-action-content">
                                <h6>Email Templates</h6>
                                <p class="mb-0">Manage email templates</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize charts when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // No charts to initialize - Charts will be implemented in a separate page
});
</script>

<style>
/* Dashboard specific styles */
.page-header {
    margin-bottom: 2rem;
}

.welcome-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(118deg, #e6efff, #f2f7ff);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.welcome-img {
    margin-left: 1rem;
}

/* Stat cards */
.stat-card {
    overflow: hidden;
}

.stat-card .card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
}

.stat-card-info {
    display: flex;
    flex-direction: column;
}

.stat-card-title {
    color: #6E6B7B;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat-card-value {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--dark-color);
}

.stat-card-desc {
    font-size: 0.8rem;
    color: #6E6B7B;
}

.stat-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
}

/* Setup cards */
.setup-card {
    display: flex;
    align-items: flex-start;
    padding: 1.25rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: #fff;
    transition: all 0.3s ease;
    height: 100%;
}

.setup-card:hover {
    box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
    transform: translateY(-5px);
}

.setup-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 1rem;
}

.setup-info {
    flex: 1;
}

/* Quick action cards */
.quick-action-card {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    border-radius: 0.5rem;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.05);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    height: 100%;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
    color: inherit;
}

.quick-action-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-right: 1rem;
    color: #fff;
}

.quick-action-content {
    flex: 1;
}

.quick-action-content h6 {
    margin-bottom: 0.25rem;
    font-weight: 600;
    color: var(--dark-color);
}

.quick-action-content p {
    color: #6E6B7B;
    font-size: 0.9rem;
}

/* Recent invoices */
.recent-invoices-table td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.invoice-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.bg-light-primary {
    background-color: rgba(78, 84, 200, 0.1);
}

.empty-state {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Light backgrounds */
.bg-light-success {
    background-color: rgba(40, 199, 111, 0.1);
    color: var(--success-color);
}

.bg-light-warning {
    background-color: rgba(255, 159, 67, 0.1);
    color: var(--warning-color);
}

.bg-light-danger {
    background-color: rgba(234, 84, 85, 0.1);
    color: var(--danger-color);
}

.bg-light-info {
    background-color: rgba(0, 207, 232, 0.1);
    color: var(--info-color);
}

/* Responsive fixes */
@media (max-width: 767.98px) {
    .stat-card .card-body {
        padding: 1.25rem;
    }
    
    .stat-card-value {
        font-size: 1.5rem;
    }
    
    .welcome-img {
        display: none;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 