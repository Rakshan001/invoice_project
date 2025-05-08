<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="coming-soon-wrapper">
                
                <h1 class="display-4 mb-4">Coming Soon!</h1>
                <p class="lead text-muted mb-4">We're working hard to bring you an amazing quotation feature. Stay tuned!</p>
                <div class="features-preview mb-5">
                    <h5 class="mb-4">What to expect:</h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded shadow-sm">
                                <i class="fas fa-file-contract text-primary mb-3 fa-2x"></i>
                                <h6>Professional Quotations</h6>
                                <p class="small text-muted">Create and manage professional quotations with ease</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded shadow-sm">
                                <i class="fas fa-sync text-success mb-3 fa-2x"></i>
                                <h6>Convert to Invoice</h6>
                                <p class="small text-muted">Convert quotations to invoices with one click</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded shadow-sm">
                                <i class="fas fa-history text-info mb-3 fa-2x"></i>
                                <h6>Track History</h6>
                                <p class="small text-muted">Keep track of all your quotation history</p>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="dashboard.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.coming-soon-wrapper {
    padding: 3rem 0;
}

.feature-card {
    background: #fff;
    transition: transform 0.3s ease;
    border: 1px solid #e9ecef;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.text-primary {
    color: #0d6efd;
}

.text-success {
    color: #198754;
}

.text-info {
    color: #0dcaf0;
}
</style>

<?php include 'includes/footer.php'; ?> 