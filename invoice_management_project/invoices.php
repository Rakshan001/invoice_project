<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get company data
$stmt = $conn->prepare("SELECT company_id FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    $_SESSION['error'] = "Please set up your company details first.";
    header("Location: company_details.php");
    exit();
}

// Delete invoice if requested
if (isset($_POST['delete_invoice']) && isset($_POST['invoice_id'])) {
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("DELETE FROM invoice_description WHERE invoice_id = ?");
        $stmt->bind_param("i", $_POST['invoice_id']);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM invoice WHERE invoice_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $_POST['invoice_id'], $company['company_id']);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        $_SESSION['success'] = "Invoice deleted successfully!";
        header("Location: invoices.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting invoice: " . $e->getMessage();
    }
}

// Update invoice status if requested
if (isset($_POST['update_status']) && isset($_POST['invoice_id']) && isset($_POST['status'])) {
    try {
        $allowedStatuses = ['pending', 'paid', 'cancelled'];
        $status = strtolower($_POST['status']);
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception("Invalid status");
        }
        
        $stmt = $conn->prepare("UPDATE invoice SET status = ? WHERE invoice_id = ? AND company_id = ?");
        $stmt->bind_param("sii", $status, $_POST['invoice_id'], $company['company_id']);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success'] = "Invoice status updated successfully!";
        header("Location: invoices.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating status: " . $e->getMessage();
    }
}

// Initialize search variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "WHERE i.company_id = ?";
$searchPattern = "%$search%";

// Get all invoices for the company with search
$query = "
    SELECT i.*, c.name as client_name 
    FROM invoice i 
    LEFT JOIN client_master c ON i.client_id = c.client_id 
    $whereClause";

if (!empty($search)) {
    $query .= " AND (i.invoice_number LIKE ? OR c.name LIKE ?)";
}

$query .= " ORDER BY i.invoice_date DESC, i.invoice_id DESC";

$stmt = $conn->prepare($query);

if (!empty($search)) {
    $stmt->bind_param("iss", $company['company_id'], $searchPattern, $searchPattern);
} else {
    $stmt->bind_param("i", $company['company_id']);
}

$stmt->execute();
$result = $stmt->get_result();
$invoices = [];
while ($row = $result->fetch_assoc()) {
    $invoices[] = $row;
}
$stmt->close();

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'paid':
            return 'btn-success';
        case 'cancelled':
            return 'btn-danger';
        case 'pending':
        default:
            return 'btn-warning';
    }
}

// Add status column to invoice table if it doesn't exist
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM invoice LIKE 'status'");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $stmt->close();
        $stmt = $conn->prepare("ALTER TABLE invoice ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-0">Invoice Management</h4>
            <p class="text-muted mb-0">View, download and manage all your invoices</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="stat-card-icon bg-gradient-primary me-3">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Invoice List</h5>
                            <p class="text-muted mb-0 small">All invoices created for your clients</p>
                        </div>
                    </div>
                    <a href="create_invoice.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create New Invoice
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <div class="px-4 py-3 border-bottom">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-8 col-sm-12">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by invoice number or client name" value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <?php if (!empty($search)): ?>
                                    <a href="invoices.php" class="btn btn-light"><i class="fas fa-times"></i> Clear</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 text-md-end">
                            <span class="text-muted">Found <?php echo count($invoices); ?> invoices</span>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Invoice</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Total</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <?php if (!empty($search)): ?>
                                            <i class="fas fa-search fa-4x mb-3 text-primary opacity-50"></i>
                                            <p class="h5 mb-3">No matching invoices found</p>
                                            <p class="text-muted mb-4">Try with a different search term or clear the search</p>
                                            <a href="invoices.php" class="btn btn-primary">
                                                <i class="fas fa-times me-2"></i>Clear Search
                                            </a>
                                        <?php else: ?>
                                            <i class="fas fa-file-invoice fa-4x mb-3 text-primary opacity-50"></i>
                                            <p class="h5 mb-3">No invoices found</p>
                                            <p class="text-muted mb-4">Create your first invoice to get started!</p>
                                            <a href="create_invoice.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Create Invoice
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="invoice-icon bg-light-primary text-primary rounded me-3">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-semibold">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                                                <p class="mb-0 small text-muted">INV-<?php echo htmlspecialchars($invoice['invoice_id']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                    <td>
                                        <div>
                                            <p class="mb-0 fw-medium"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                                            <p class="mb-0 small text-muted text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($invoice['client_name']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm status-dropdown <?php echo getStatusBadgeClass($invoice['status'] ?? 'pending'); ?> dropdown-toggle" type="button" id="statusDropdown<?php echo $invoice['invoice_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <?php echo ucfirst($invoice['status'] ?? 'pending'); ?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $invoice['invoice_id']; ?>">
                                                <li>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['invoice_id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <button type="submit" name="status" value="pending" class="dropdown-item <?php echo ($invoice['status'] ?? 'pending') == 'pending' ? 'active' : ''; ?>">
                                                            <i class="fas fa-clock me-2 text-warning"></i> Pending
                                                        </button>
                                                        <button type="submit" name="status" value="paid" class="dropdown-item <?php echo ($invoice['status'] ?? '') == 'paid' ? 'active' : ''; ?>">
                                                            <i class="fas fa-check-circle me-2 text-success"></i> Paid
                                                        </button>
                                                        <button type="submit" name="status" value="cancelled" class="dropdown-item <?php echo ($invoice['status'] ?? '') == 'cancelled' ? 'active' : ''; ?>">
                                                            <i class="fas fa-times-circle me-2 text-danger"></i> Cancelled
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold">₹<?php echo number_format($invoice['net_total'], 2); ?></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="javascript:void(0)" 
                                               class="btn btn-sm btn-outline-primary" 
                                               onclick="window.open('generate_pdf.php?invoice_id=<?php echo $invoice['invoice_id']; ?>', '_blank')"
                                               title="View PDF">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               class="btn btn-sm btn-outline-success"
                                               onclick="window.open('generate_pdf.php?invoice_id=<?php echo $invoice['invoice_id']; ?>&download=1', '_blank')"
                                               title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteInvoice(<?php echo $invoice['invoice_id']; ?>)"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.breadcrumb-item + .breadcrumb-item::before {
    color: var(--primary-color);
}

.breadcrumb-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.stat-card-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
}

.card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    border-top-left-radius: 12px !important;
    border-top-right-radius: 12px !important;
}

.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}

.table thead {
    background-color: rgba(0, 0, 0, 0.02);
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background-color: rgba(78, 84, 200, 0.05);
}

.invoice-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.bg-light-primary {
    background-color: rgba(78, 84, 200, 0.1);
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.input-group-text {
    border: 1px solid #dee2e6;
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 0.25rem 0.6rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.btn-primary {
    background: linear-gradient(118deg, var(--primary-color), var(--secondary-color));
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
}

.empty-state {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.alert {
    border-radius: 8px;
    border: none;
}

.gap-2 {
    gap: 0.5rem;
}

/* Status badge styles */
.status-dropdown {
    min-width: 100px;
    font-weight: 500;
    border: none;
}

.btn-success {
    background-color: rgba(40, 199, 111, 0.2);
    color: #28C76F;
}

.btn-warning {
    background-color: rgba(255, 159, 67, 0.2);
    color: #FF9F43;
}

.btn-danger {
    background-color: rgba(234, 84, 85, 0.2);
    color: #EA5455;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
}

.dropdown-item.active {
    background-color: rgba(78, 84, 200, 0.1);
    color: var(--primary-color);
}

.dropdown-item:hover {
    background-color: rgba(78, 84, 200, 0.05);
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .table td {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .card-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .card-header .btn {
        margin-top: 1rem;
        width: 100%;
    }
    
    .invoice-icon {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .d-flex.justify-content-end {
        flex-wrap: wrap;
    }
    
    .btn-sm {
        margin-bottom: 0.25rem;
    }
}
</style>

<script>
function deleteInvoice(invoiceId) {
    if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="invoice_id" value="${invoiceId}">
            <input type="hidden" name="delete_invoice" value="1">
        `;
        document.body.append(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?> 