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

// Handle client deletion
if (isset($_POST['delete_client'])) {
    $client_id = $_POST['client_id'];
    $stmt = $conn->prepare("DELETE FROM client_master WHERE client_id = ? AND company_id = ?");
    $stmt->bind_param("ii", $client_id, $company['company_id']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Client deleted successfully!";
    header("Location: clients.php");
    exit();
}

// Handle client addition/update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_client'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $gst = $_POST['gst'];
    $state = $_POST['state'];
    $email = $_POST['email'];
    $client_id = $_POST['client_id'] ?? null;

    if ($client_id) {
        // Update existing client
        $stmt = $conn->prepare("
            UPDATE client_master 
            SET name = ?, address = ?, gst = ?, state = ?, email = ? 
            WHERE client_id = ? AND company_id = ?
        ");
        $stmt->bind_param("sssssii", 
            $name, $address, $gst, $state, $email, $client_id, $company['company_id']
        );
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // Insert new client
        $stmt = $conn->prepare("
            INSERT INTO client_master 
            (company_id, name, address, gst, state, email) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss",
            $company['company_id'], $name, $address, $gst, $state, $email
        );
        $success = $stmt->execute();
        $stmt->close();
    }

    if ($success) {
        $_SESSION['success'] = "Client saved successfully!";
        header("Location: clients.php");
        exit();
    } else {
        $error = "Error saving client details.";
    }
}

// Fetch all clients
$stmt = $conn->prepare("SELECT * FROM client_master WHERE company_id = ? ORDER BY name");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
$stmt->close();

// Fetch client for editing
$editing_client = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM client_master WHERE client_id = ? AND company_id = ?");
    $stmt->bind_param("ii", $_GET['edit'], $company['company_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_client = $result->fetch_assoc();
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-0">Client Management</h4>
            <p class="text-muted mb-0">Add, edit, and manage your client database</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clients</li>
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
                    <div class="stat-card-icon bg-gradient-warning me-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0"><?php echo $editing_client ? 'Edit Client' : 'Add New Client'; ?></h5>
                        <p class="text-muted mb-0 small"><?php echo $editing_client ? 'Update client information' : 'Create a new client record'; ?></p>
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
                    <?php if ($editing_client): ?>
                        <input type="hidden" name="client_id" value="<?php echo $editing_client['client_id']; ?>">
                    <?php endif; ?>

                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-warning mb-3">Client Information</h6>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($editing_client['name'] ?? ''); ?>" required>
                        <label for="name">Client Name</label>
                        <div class="invalid-feedback">Please enter client name.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($editing_client['email'] ?? ''); ?>" required>
                        <label for="email">Email Address</label>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="address" name="address" style="height: 100px" required><?php echo htmlspecialchars($editing_client['address'] ?? ''); ?></textarea>
                        <label for="address">Address</label>
                        <div class="invalid-feedback">Please enter client address.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="gst" name="gst" 
                                       value="<?php echo htmlspecialchars($editing_client['gst'] ?? ''); ?>">
                                <label for="gst">GST Number</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($editing_client['state'] ?? ''); ?>" required>
                                <label for="state">State</label>
                                <div class="invalid-feedback">Please enter state.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i><?php echo $editing_client ? 'Update Client' : 'Add Client'; ?>
                        </button>
                        <?php if ($editing_client): ?>
                            <a href="clients.php" class="btn btn-light">
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
                        <div class="stat-card-icon bg-gradient-warning me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Client List</h5>
                            <p class="text-muted mb-0 small">Your client database</p>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark"><?php echo count($clients); ?> Clients</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Email</th>
                                <th>GST</th>
                                <th>State</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clients)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="empty-state">
                                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="No Clients" width="60">
                                            <p class="mt-2 mb-0">No clients added yet</p>
                                            <p class="text-muted mb-3 small">Add your first client using the form on the left</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="client-avatar bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($client['name']); ?></p>
                                                <p class="mb-0 small text-muted text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($client['address']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td><?php echo htmlspecialchars($client['gst'] ? $client['gst'] : '-'); ?></td>
                                    <td><?php echo htmlspecialchars($client['state']); ?></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end">
                                            <a href="?edit=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-outline-warning me-2" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="" class="d-inline delete-form">
                                                <input type="hidden" name="client_id" value="<?php echo $client['client_id']; ?>">
                                                <button type="submit" name="delete_client" class="btn btn-sm btn-outline-danger" title="Delete">
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
    color: var(--warning-color);
}

.breadcrumb-item a {
    color: var(--warning-color);
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
    background: var(--warning-color);
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

.client-avatar {
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
    border-color: var(--warning-color);
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
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

.btn-warning {
    background: linear-gradient(118deg, var(--warning-color), #ffc107);
    border: none;
    color: #212529;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
    color: #212529;
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
    background-color: rgba(255, 193, 7, 0.05);
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
            if (!confirm('Are you sure you want to delete this client?')) {
                event.preventDefault();
            }
        });
    });
})();
</script>

<?php include 'includes/footer.php'; ?> 