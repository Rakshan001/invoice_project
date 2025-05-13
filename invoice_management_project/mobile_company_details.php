<?php
// Add this at the top of your file to help debug file uploads from mobile
if (isset($_GET['debug_mode']) && $_GET['debug_mode'] === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Log file upload attempts
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES)) {
        error_log("Mobile app file upload attempt detected");
        foreach ($_FILES as $key => $file) {
            error_log("File upload field: $key, Error: {$file['error']}, Name: {$file['name']}");
        }
    }
}

// Add this after your file upload handling code to provide feedback to mobile app
if (isset($_GET['allow_file_upload']) && $_GET['allow_file_upload'] === 'true' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add JavaScript to provide feedback to the mobile app
    echo "<script>
        if (window.mobileApp) {
            console.log('Sending file upload result to mobile app');
            if (typeof FlutterWebViewCallback !== 'undefined') {
                FlutterWebViewCallback.postMessage('uploadComplete:' + JSON.stringify({
                    success: true,
                    message: 'Files uploaded successfully'
                }));
            }
        }
    </script>";
}

   session_start();
   require_once 'config/database.php';

   // Check if this is a request from mobile app by URL parameter or cookie
   $from_mobile_app = (isset($_GET['mobile_app']) && $_GET['mobile_app'] === 'true') || 
                      (isset($_COOKIE['mobile_app']) && $_COOKIE['mobile_app'] === 'true');

   // Get user ID from various possible sources
   $mobile_user_id = null;

   // First check GET parameters (highest priority)
   if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
       $mobile_user_id = intval($_GET['user_id']);
   }
   // Then check POST parameters
   elseif (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
       $mobile_user_id = intval($_POST['user_id']);
   }
   // Then check cookies
   elseif (isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id'])) {
       $mobile_user_id = intval($_COOKIE['user_id']);
   }

   // If mobile_user_id is available, set it in session
   if ($mobile_user_id) {
       $_SESSION['user_id'] = $mobile_user_id;
       
       // Set cookies to maintain login state across page loads
       setcookie('user_id', $mobile_user_id, time() + 3600, '/');
       if ($from_mobile_app) {
           setcookie('mobile_app', 'true', time() + 3600, '/');
       }
   }

   // Check if user is logged in
   if (!isset($_SESSION['user_id'])) {
       // For AJAX/API requests, return JSON error
       if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
           header('Content-Type: application/json');
           echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
           exit();
       }
       
       // For mobile apps, provide a clear error message that can be displayed in webview
       if ($from_mobile_app) {
           echo '<html><body>
               <h1>Authentication Error</h1>
               <p>Please return to the app and login again.</p>
           </body></html>';
           exit();
       }
       
       // For regular web requests
       header("Location: login.php");
       exit();
   }

//Above content is for mobile app

// Check if company exists
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

// Get company logo if exists
$logo = null;
if ($company && $company['logo']) {
    $logo = $company['logo'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $email_password = $_POST['email_password'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $cin = $_POST['cin'];
    $gstin = $_POST['gstin'];
    $state = $_POST['state'];

    // Handle file uploads
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $seal = $company['seal'] ?? null;
    $sign = $company['sign'] ?? null;

    // Logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logo_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($logo_ext, ['jpg', 'jpeg', 'png'])) {
            $logo = $upload_dir . 'logo_' . $_SESSION['user_id'] . '.' . $logo_ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo);
        }
    }

    // Seal upload
    if (isset($_FILES['seal']) && $_FILES['seal']['error'] == 0) {
        $seal_ext = strtolower(pathinfo($_FILES['seal']['name'], PATHINFO_EXTENSION));
        if (in_array($seal_ext, ['jpg', 'jpeg', 'png'])) {
            $seal = $upload_dir . 'seal_' . $_SESSION['user_id'] . '.' . $seal_ext;
            move_uploaded_file($_FILES['seal']['tmp_name'], $seal);
        }
    }

    // Signature upload
    if (isset($_FILES['sign']) && $_FILES['sign']['error'] == 0) {
        $sign_ext = strtolower(pathinfo($_FILES['sign']['name'], PATHINFO_EXTENSION));
        if (in_array($sign_ext, ['jpg', 'jpeg', 'png'])) {
            $sign = $upload_dir . 'sign_' . $_SESSION['user_id'] . '.' . $sign_ext;
            move_uploaded_file($_FILES['sign']['tmp_name'], $sign);
        }
    }

    if ($company) {
        // Update existing company
        $stmt = $conn->prepare("
            UPDATE company_master 
            SET name = ?, email = ?, email_password = ?, address = ?, phone = ?, cin = ?, gstin = ?, state = ?, 
                logo = ?, seal = ?, sign = ?
            WHERE user_id = ?
        ");
        $success = $stmt->execute([
            $name, $email, $email_password, $address, $phone, $cin, $gstin, $state,
            $logo, $seal, $sign, $_SESSION['user_id']
        ]);
    } else {
        // Insert new company
        $stmt = $conn->prepare("
            INSERT INTO company_master 
            (user_id, name, email, email_password, address, phone, cin, gstin, state, logo, seal, sign) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $_SESSION['user_id'], $name, $email, $email_password, $address, $phone, $cin, $gstin, $state,
            $logo, $seal, $sign
        ]);
    }

    if ($success) {
        $_SESSION['success'] = "Company details saved successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error saving company details.";
    }
}


// Use the header that omits sidebar for the mobile webview
if ($from_mobile_app) {
    include 'includes/header_minimal.php';
} else {
    include 'includes/header.php';
}


?>

<div class="page-header">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h4 class="mb-0">Company Profile</h4>
            <p class="text-muted mb-0">Manage your company information and branding</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-lg-end mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Company Details</li>
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
                    <div class="stat-card-icon bg-gradient-primary me-3">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Company Information</h5>
                        <p class="text-muted mb-0 small">This information will be displayed on your invoices</p>
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

                <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <!-- Basic Information -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-primary mb-3">Basic Information</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" 
                                        value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" required>
                                <label for="name">Company Name</label>
                                <div class="invalid-feedback">Please enter company name.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" 
                                        value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>" required>
                                <label for="email">Email Address</label>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="email_password" name="email_password" 
                                        value="<?php echo htmlspecialchars($company['email_password'] ?? ''); ?>" required>
                                <label for="email_password">Email Password</label>
                                <div class="invalid-feedback">Please enter the email password.</div>
                                <div class="form-text">Required for sending invoices via email</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                        value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>" required>
                                <label for="phone">Phone Number</label>
                                <div class="invalid-feedback">Please enter phone number.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-primary mb-3">Address Information</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="address" name="address" style="height: 100px" required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                                <label for="address">Company Address</label>
                                <div class="invalid-feedback">Please enter company address.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="state" name="state" 
                                        value="<?php echo htmlspecialchars($company['state'] ?? ''); ?>" required>
                                <label for="state">State</label>
                                <div class="invalid-feedback">Please enter state.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Information -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-primary mb-3">Tax Information</h6>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="cin" name="cin" 
                                        value="<?php echo htmlspecialchars($company['cin'] ?? ''); ?>">
                                <label for="cin">CIN Number</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="gstin" name="gstin" 
                                        value="<?php echo htmlspecialchars($company['gstin'] ?? ''); ?>">
                                <label for="gstin">GSTIN</label>
                            </div>
                        </div>
                    </div>

                    <!-- Company Documents -->
                    <div class="section-title mb-4">
                        <h6 class="fw-bold text-primary mb-3">Company Documents</h6>
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="document-upload">
                                <label class="form-label fw-medium">Company Logo</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    <label class="input-group-text bg-gradient-primary text-white" for="logo">
                                        <i class="fas fa-upload"></i>
                                    </label>
                                </div>
                                <?php if (isset($company['logo']) && $company['logo']): ?>
                                    <div class="mt-3 text-center">
                                        <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="Logo" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="document-upload">
                                <label class="form-label fw-medium">Company Seal</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="seal" name="seal" accept="image/*">
                                    <label class="input-group-text bg-gradient-primary text-white" for="seal">
                                        <i class="fas fa-upload"></i>
                                    </label>
                                </div>
                                <?php if (isset($company['seal']) && $company['seal']): ?>
                                    <div class="mt-3 text-center">
                                        <img src="<?php echo htmlspecialchars($company['seal']); ?>" alt="Seal" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="document-upload">
                                <label class="form-label fw-medium">Digital Signature</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="sign" name="sign" accept="image/*">
                                    <label class="input-group-text bg-gradient-primary text-white" for="sign">
                                        <i class="fas fa-upload"></i>
                                    </label>
                                </div>
                                <?php if (isset($company['sign']) && $company['sign']): ?>
                                    <div class="mt-3 text-center">
                                        <img src="<?php echo htmlspecialchars($company['sign']); ?>" alt="Signature" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Company Details
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
    color: var(--primary-color);
}

.breadcrumb-item a {
    color: var(--primary-color);
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
    background: var(--primary-color);
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
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.document-upload {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px dashed #dee2e6;
    height: 100%;
    transition: all 0.3s ease;
}

.document-upload:hover {
    border-color: var(--primary-color);
    background: #f0f4f8;
}

.input-group {
    border-radius: 8px;
    overflow: hidden;
}

.input-group-text {
    border: none;
    cursor: pointer;
}

.input-group-text:hover {
    opacity: 0.9;
}

.btn {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(118deg, var(--primary-color), var(--secondary-color));
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
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
    .document-upload {
        margin-bottom: 1rem;
    }
    
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

<?php
// Use the appropriate footer based on whether it's a mobile app view
if ($from_mobile_app) {
    include 'includes/footer_minimal.php';
} else {
    include 'includes/footer.php';
}
?>
