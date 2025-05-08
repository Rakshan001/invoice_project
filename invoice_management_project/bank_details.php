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

// Fetch bank details
$stmt = $conn->prepare("SELECT * FROM company_bank WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$bank = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $ifsc = $_POST['ifsc'];
    $bank_name = $_POST['bank_name'];
    $branch_name = $_POST['branch_name'];

    if ($bank) {
        // Update existing bank details
        $stmt = $conn->prepare("
            UPDATE company_bank 
            SET account_name = ?, account_number = ?, ifsc = ?, bank_name = ?, branch_name = ?
            WHERE company_id = ?
        ");
        $success = $stmt->execute([
            $account_name, $account_number, $ifsc, $bank_name, $branch_name, $company['company_id']
        ]);
    } else {
        // Insert new bank details
        $stmt = $conn->prepare("
            INSERT INTO company_bank 
            (company_id, account_name, account_number, ifsc, bank_name, branch_name) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $company['company_id'], $account_name, $account_number, $ifsc, $bank_name, $branch_name
        ]);
    }

    if ($success) {
        $_SESSION['success'] = "Bank details saved successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error saving bank details.";
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-0">Bank Information</h4>
            <p class="text-muted mb-0">Manage your company's banking details for invoices</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bank Details</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <div class="stat-card-icon bg-gradient-success me-3">
                        <i class="fas fa-university"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Banking Information</h5>
                        <p class="text-muted mb-0 small">These details will appear on your invoices</p>
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
                    <!-- Bank Information -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-success mb-3">Bank Information</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                       value="<?php echo htmlspecialchars($bank['bank_name'] ?? ''); ?>" required>
                                <label for="bank_name">Bank Name</label>
                                <div class="invalid-feedback">Please enter bank name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="branch_name" name="branch_name" 
                                       value="<?php echo htmlspecialchars($bank['branch_name'] ?? ''); ?>" required>
                                <label for="branch_name">Branch Name</label>
                                <div class="invalid-feedback">Please enter branch name.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-success mb-3">Account Information</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="<?php echo htmlspecialchars($bank['account_name'] ?? ''); ?>" required>
                                <label for="account_name">Account Holder Name</label>
                                <div class="invalid-feedback">Please enter account holder name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       value="<?php echo htmlspecialchars($bank['account_number'] ?? ''); ?>" required>
                                <label for="account_number">Account Number</label>
                                <div class="invalid-feedback">Please enter account number.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="ifsc" name="ifsc" 
                                       value="<?php echo htmlspecialchars($bank['ifsc'] ?? ''); ?>" required>
                                <label for="ifsc">IFSC Code</label>
                                <div class="invalid-feedback">Please enter IFSC code.</div>
                                <div class="form-text">Enter the 11-digit IFSC code</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Bank Details
                        </button>
                    </div>
                </form>
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
    color: var(--success-color);
}

.breadcrumb-item a {
    color: var(--success-color);
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
    background: var(--success-color);
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

.form-floating > .form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 1rem 0.75rem;
}

.form-floating > .form-control:focus {
    border-color: var(--success-color);
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-success {
    background: linear-gradient(118deg, var(--success-color), #169b6b);
    border: none;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(28, 200, 138, 0.3);
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

.form-text {
    color: #6c757d;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column-reverse;
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
})();
</script>

<?php include 'includes/footer.php'; ?> 