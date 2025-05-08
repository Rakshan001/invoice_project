<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if company exists
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    $_SESSION['error'] = "Please add company details first.";
    header("Location: company_details.php");
    exit();
}

// Handle tax addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tax'])) {
    $tax_rate = $_POST['tax_rate'];
    $description = $_POST['description'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting this tax as default, unset any existing default
    if ($is_default) {
        $stmt = $conn->prepare("UPDATE tax_master SET is_default = 0 WHERE company_id = ?");
        $stmt->execute([$company['company_id']]);
    }
    
    // Insert new tax rate
    $stmt = $conn->prepare("
        INSERT INTO tax_master 
        (company_id, tax, description, is_default) 
        VALUES (?, ?, ?, ?)
    ");
    $success = $stmt->execute([$company['company_id'], $tax_rate, $description, $is_default]);
    
    if ($success) {
        $_SESSION['success'] = "Tax rate added successfully!";
        header("Location: tax_settings.php");
        exit();
    } else {
        $error = "Error saving tax rate.";
    }
}

// Handle tax update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tax'])) {
    $tax_master_id = $_POST['tax_master_id'];
    $tax_rate = $_POST['tax_rate'];
    $description = $_POST['description'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // If setting this tax as default, unset any existing default
    if ($is_default) {
        $stmt = $conn->prepare("UPDATE tax_master SET is_default = 0 WHERE company_id = ?");
        $stmt->execute([$company['company_id']]);
    }
    
    // Update tax rate
    $stmt = $conn->prepare("
        UPDATE tax_master 
        SET tax = ?, description = ?, is_default = ? 
        WHERE tax_master_id = ? AND company_id = ?
    ");
    $success = $stmt->execute([$tax_rate, $description, $is_default, $tax_master_id, $company['company_id']]);
    
    if ($success) {
        $_SESSION['success'] = "Tax rate updated successfully!";
        header("Location: tax_settings.php");
        exit();
    } else {
        $error = "Error updating tax rate.";
    }
}

// Handle tax deletion
if (isset($_POST['delete_tax'])) {
    $tax_master_id = $_POST['tax_master_id'];
    
    $stmt = $conn->prepare("DELETE FROM tax_master WHERE tax_master_id = ? AND company_id = ?");
    $success = $stmt->execute([$tax_master_id, $company['company_id']]);
    
    if ($success) {
        $_SESSION['success'] = "Tax rate deleted successfully!";
        header("Location: tax_settings.php");
        exit();
    } else {
        $error = "Error deleting tax rate.";
    }
}

// Fetch existing tax rates
$stmt = $conn->prepare("SELECT * FROM tax_master WHERE company_id = ? ORDER BY is_default DESC, tax ASC");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$tax_rates = [];
while ($row = $result->fetch_assoc()) {
    $tax_rates[] = $row;
}
$stmt->close();

// Fetch tax for editing
$editing_tax = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM tax_master WHERE tax_master_id = ? AND company_id = ?");
    $stmt->execute([$_GET['edit'], $company['company_id']]);
    $editing_tax = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-0">Tax Settings</h4>
            <p class="text-muted mb-0">Manage your company's applicable tax rates</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tax Settings</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <div class="stat-card-icon bg-gradient-danger me-3">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?php echo $editing_tax ? 'Edit Tax Rate' : 'Add Tax Rate'; ?></h5>
                        <p class="text-muted mb-0 small"><?php echo $editing_tax ? 'Update existing tax rate' : 'Add a new tax rate'; ?></p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <?php if ($editing_tax): ?>
                        <input type="hidden" name="tax_master_id" value="<?php echo $editing_tax['tax_master_id']; ?>">
                        <input type="hidden" name="update_tax" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_tax" value="1">
                    <?php endif; ?>

                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-danger mb-3">Tax Information</h6>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                               value="<?php echo htmlspecialchars($editing_tax['tax'] ?? ''); ?>" 
                               step="0.01" min="0" max="100" required>
                        <label for="tax_rate">Tax Rate (%)</label>
                        <div class="invalid-feedback">Please enter a valid tax rate between 0-100.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="description" name="description" 
                               value="<?php echo htmlspecialchars($editing_tax['description'] ?? ''); ?>">
                        <label for="description">Description (Optional)</label>
                        <div class="form-text">E.g., GST, VAT, Sales Tax, etc.</div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" 
                               <?php echo ($editing_tax && $editing_tax['is_default']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_default">
                            Set as default tax rate
                        </label>
                        <div class="form-text">This rate will be pre-selected when creating invoices.</div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-2"></i><?php echo $editing_tax ? 'Update Tax Rate' : 'Add Tax Rate'; ?>
                        </button>
                        <?php if ($editing_tax): ?>
                            <a href="tax_settings.php" class="btn btn-light">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="stat-card-icon bg-gradient-danger me-3">
                            <i class="fas fa-list"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Tax Rates</h5>
                            <p class="text-muted mb-0 small">All configured tax rates</p>
                        </div>
                    </div>
                    <span class="badge bg-danger"><?php echo count($tax_rates); ?> Tax Rates</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Tax Rate</th>
                                <th>Description</th>
                                <th class="text-center">Default</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tax_rates)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-percent fa-4x mb-3 text-danger opacity-50"></i>
                                            <p class="h5 mb-3">No tax rates found</p>
                                            <p class="text-muted mb-3 small">Add your first tax rate using the form on the left</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tax_rates as $tax): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="tax-icon bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                            <span class="fw-medium"><?php echo htmlspecialchars($tax['tax']); ?>%</span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($tax['description'] ? $tax['description'] : '-'); ?></td>
                                    <td class="text-center">
                                        <?php if ($tax['is_default']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> Default</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end">
                                            <a href="?edit=<?php echo $tax['tax_master_id']; ?>" class="btn btn-sm btn-outline-danger me-2" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="" class="d-inline delete-form">
                                                <input type="hidden" name="tax_master_id" value="<?php echo $tax['tax_master_id']; ?>">
                                                <button type="submit" name="delete_tax" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
    color: var(--danger-color);
}

.breadcrumb-item a {
    color: var(--danger-color);
    text-decoration: none;
}

.section-title {
    position: relative;
    padding-bottom: 0.5rem;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: var(--danger-color);
    border-radius: 3px;
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

.tax-icon {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-floating > .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 1rem 0.75rem;
}

.form-floating > .form-control:focus {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 0.2rem rgba(234, 84, 85, 0.25);
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.btn-danger {
    background: linear-gradient(118deg, var(--danger-color), #f86566);
    border: none;
    color: #fff;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(234, 84, 85, 0.3);
    color: #fff;
}

.btn-light {
    background: #f8f9fa;
    border-color: #dee2e6;
}

.btn-light:hover {
    background: #e9ecef;
}

.alert {
    border-radius: 8px;
    border: none;
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
    background-color: rgba(234, 84, 85, 0.05);
}

.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 8px;
}

.empty-state {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .card {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 767.98px) {
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
    }
    
    .table td {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>

<script>
// Form validation
(function() {
    'use strict';
    
    // Fetch all forms that need validation
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
        
    // Confirm delete
    document.querySelectorAll('.delete-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!confirm('Are you sure you want to delete this tax rate?')) {
                event.preventDefault();
            }
        });
    });
})();
</script>

<?php include 'includes/footer.php'; ?> 