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

// Fetch existing invoice settings
$stmt = $conn->prepare("SELECT * FROM invoice_master WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$invoice_settings = $result->fetch_assoc();
$stmt->close();

// Fetch available invoice templates
$stmt = $conn->prepare("SELECT * FROM invoice_templates ORDER BY is_default DESC, name ASC");
$stmt->execute();
$result = $stmt->get_result();
$invoice_templates = [];
while ($row = $result->fetch_assoc()) {
    $invoice_templates[] = $row;
}
$stmt->close();

// Get user's selected template
$stmt = $conn->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = 'invoice_template'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_template = $result->fetch_assoc();
$stmt->close();

$selected_template_id = isset($user_template['preference_value']) ? $user_template['preference_value'] : null;

// If no template is selected, find the default one
if (!$selected_template_id) {
    foreach ($invoice_templates as $template) {
        if ($template['is_default'] == 1) {
            $selected_template_id = $template['template_id'];
            break;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_invoice_settings'])) {
        $invoice_number = $_POST['invoice_number'];
        $template_id = $_POST['template_id'];
    
        if ($invoice_settings) {
            // Update existing invoice settings
            $stmt = $conn->prepare("
                UPDATE invoice_master 
                SET invoice_number = ? 
                WHERE company_id = ?
            ");
            $success = $stmt->execute([$invoice_number, $company['company_id']]);
        } else {
            // Insert new invoice settings
            $stmt = $conn->prepare("
                INSERT INTO invoice_master 
                (company_id, invoice_number) 
                VALUES (?, ?)
            ");
            $success = $stmt->execute([$company['company_id'], $invoice_number]);
        }
    
        // Save selected template
        $stmt = $conn->prepare("
            INSERT INTO user_preferences (user_id, preference_key, preference_value)
            VALUES (?, 'invoice_template', ?)
            ON DUPLICATE KEY UPDATE preference_value = ?
        ");
        $template_success = $stmt->execute([$_SESSION['user_id'], $template_id, $template_id]);
    
        if ($success && $template_success) {
            $_SESSION['success'] = "Invoice settings saved successfully!";
            header("Location: invoice_settings.php");
            exit();
        } else {
            $error = "Error saving invoice settings.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="mb-0">Invoice Settings</h4>
                <p class="text-muted mb-0">Manage your invoice numbering and template preferences</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-lg-end mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Invoice Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="card-title mb-3">Invoice Numbering</h5>
                                <div class="mb-3">
                                    <label for="invoice_number" class="form-label">Next Invoice Number</label>
                                    <input type="number" class="form-control" id="invoice_number" name="invoice_number" 
                                           value="<?php echo isset($invoice_settings['invoice_number']) ? $invoice_settings['invoice_number'] : 1; ?>" required>
                                    <div class="form-text">This will be the number of your next invoice.</div>
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="card-title mb-3">Invoice Template</h5>
                        <p class="text-muted mb-4">Choose your preferred invoice design. You can switch between templates at any time.</p>
                        
                        <div class="row">
                            <?php foreach ($invoice_templates as $template): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 template-card <?php echo ($selected_template_id == $template['template_id']) ? 'border-primary' : ''; ?>">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="template_id" 
                                                               id="template_<?php echo $template['template_id']; ?>" 
                                                               value="<?php echo $template['template_id']; ?>"
                                                               <?php echo ($selected_template_id == $template['template_id']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="template_<?php echo $template['template_id']; ?>">
                                                            <h5 class="mb-1"><?php echo $template['name']; ?></h5>
                                                        </label>
                                                    </div>
                                                    <p class="text-muted mb-3"><?php echo $template['description']; ?></p>
                                                </div>
                                                <?php if ($template['is_default']): ?>
                                                    <span class="badge bg-success">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="template-preview bg-light rounded p-3 mb-3" style="height: 200px;">
                                                <?php if ($template['preview_image']): ?>
                                                    <img src="<?php echo $template['preview_image']; ?>" class="img-fluid" alt="<?php echo $template['name']; ?>">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center h-100">
                                                        <div class="text-center">
                                                            <i class="fas fa-file-invoice fa-3x mb-2 text-primary"></i>
                                                            <p class="mb-0"><?php echo $template['name']; ?></p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="text-end">
                                                <a href="generate_pdf.php?invoice_id=<?php echo isset($_GET['preview_id']) ? $_GET['preview_id'] : (isset($_SESSION['last_invoice_id']) ? $_SESSION['last_invoice_id'] : ''); ?>&template=<?php echo $template['template_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank" 
                                                   <?php echo !isset($_GET['preview_id']) && !isset($_SESSION['last_invoice_id']) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-eye me-1"></i> Preview Template
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="save_invoice_settings" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.template-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}
.template-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
}
.template-card.border-primary {
    border-color: #0d6efd;
    box-shadow: 0 0 0 1px #0d6efd;
}
.template-preview {
    overflow: hidden;
    background: #f8f9fa;
    border: 1px solid rgba(0,0,0,.1);
}
.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make entire card clickable for template selection
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on the preview button
            if (e.target.closest('.btn')) {
                return;
            }
            
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Remove border from all cards
            document.querySelectorAll('.template-card').forEach(c => {
                c.classList.remove('border-primary');
            });
            
            // Add border to selected card
            this.classList.add('border-primary');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?> 