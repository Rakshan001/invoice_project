<?php
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

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get company details
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    $_SESSION['error'] = "Please add company details first.";
    
    // Special handling for mobile app
    if ($from_mobile_app) {
        echo '<html><body>
            <h1>Company Setup Required</h1>
            <p>Please set up your company details first.</p>
        </body></html>';
        exit();
    }
    
    header("Location: company_details.php");
    exit();
}

// Get bank details
$stmt = $conn->prepare("SELECT * FROM company_bank WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$bank = $result->fetch_assoc();
$stmt->close();

if (!$bank) {
    $_SESSION['error'] = "Please add bank details first.";
    header("Location: bank_details.php");
    exit();
}

// Get invoice settings for next invoice number
$stmt = $conn->prepare("SELECT * FROM invoice_master WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$invoice_settings = $result->fetch_assoc();
$stmt->close();

// Get all clients for autocomplete
$stmt = $conn->prepare("SELECT * FROM client_master WHERE company_id = ?");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
$stmt->close();

// Get all tax rates, with default one first
$stmt = $conn->prepare("SELECT * FROM tax_master WHERE company_id = ? ORDER BY is_default DESC, tax ASC");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$result = $stmt->get_result();
$tax_rates = [];
while ($row = $result->fetch_assoc()) {
    $tax_rates[] = $row;
}
$stmt->close();

// If no tax rates defined, set a default of 18%
if (empty($tax_rates)) {
    $tax_rates = [
        ['tax_master_id' => 0, 'tax' => 18, 'description' => 'GST', 'is_default' => 1],
        ['tax_master_id' => 0, 'tax' => 12, 'description' => 'GST', 'is_default' => 0],
        ['tax_master_id' => 0, 'tax' => 5, 'description' => 'GST', 'is_default' => 0],
        ['tax_master_id' => 0, 'tax' => 0, 'description' => 'No Tax', 'is_default' => 0]
    ];
}

// Handle new client creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_new_client'])) {
    $name = $_POST['client_name'];
    $address = $_POST['client_address'];
    $gst = $_POST['client_gstin'];
    $state = $_POST['client_state'];
    $email = $_POST['client_email'] ?? '';

    // Insert new client
    $stmt = $conn->prepare("
        INSERT INTO client_master 
        (company_id, name, address, gst, state, email) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", 
        $company['company_id'], 
        $name, 
        $address, 
        $gst, 
        $state, 
        $email
    );
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        $_SESSION['success'] = "New client saved successfully!";
        
        // Get the newly created client ID
        $client_id = $conn->insert_id;
        
        // Stay on the same page
        header("Location: create_invoice.php?client_saved=1&client_id=" . $client_id . "&user_id=" . $user_id);
        exit();
    } else {
        $error = "Error saving client details.";
    }
}

// Handle invoice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_invoice'])) {
    try {
        $conn->begin_transaction();

        // Get client details
        $stmt = $conn->prepare("SELECT * FROM client_master WHERE client_id = ? AND company_id = ?");
        $stmt->bind_param("ii", $_POST['client_id'], $company['company_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();
        $stmt->close();

        if (!$client) {
            throw new Exception("Invalid client selected.");
        }

        // Insert into invoice table
        $stmt = $conn->prepare("
            INSERT INTO invoice (
                company_id, client_id, bank_id, invoice_number, invoice_date,
                client_name, client_address, client_gstin, client_state,
                total_amount, cgst, sgst, total_tax_amount, net_total, 
                rupees_in_words
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?
            )
        ");

        // Convert values to appropriate types
        $company_id = (int)$company['company_id'];
        $client_id = (int)$client['client_id'];
        $bank_id = (int)$bank['company_bank_id'];
        $invoice_number = (int)$_POST['invoice_number'];
        $invoice_date = $_POST['invoice_date'];
        $client_name = $client['name'];
        $client_address = $client['address'];
        $client_gstin = $client['gst'];
        $client_state = $client['state'];
        $total_amount = (float)$_POST['subTotal'];
        $cgst = (float)$_POST['cgst'];
        $sgst = (float)$_POST['sgst'];
        $total_tax_amount = (float)$_POST['totalGST'];
        $net_total = (float)$_POST['grandTotal'];
        $rupees_in_words = $_POST['amount_in_words'];

        $stmt->bind_param("iiiisssssddddds",
            $company_id,
            $client_id,
            $bank_id,
            $invoice_number,
            $invoice_date,
            $client_name,
            $client_address,
            $client_gstin,
            $client_state,
            $total_amount,
            $cgst,
            $sgst,
            $total_tax_amount,
            $net_total,
            $rupees_in_words
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving invoice: " . $stmt->error);
        }
        $invoice_id = $conn->insert_id;
        $stmt->close();

        // Insert invoice items
        $stmt = $conn->prepare("
            INSERT INTO invoice_description (
                invoice_id, s_no, description, amount,
                tax_rate, tax_value, total
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($_POST['items'] as $index => $item) {
            $s_no = $index + 1;
            $description = $item['description'];
            $amount = floatval(str_replace(',', '', $item['amount']));
            $tax_rate = isset($item['tax_rate']) && !empty($item['tax_rate']) ? floatval($item['tax_rate']) : 18.00;
            $tax_value = ($amount * $tax_rate / 100);
            $total = $amount + $tax_value;

            $stmt->bind_param("iisdddd",
                $invoice_id,
                $s_no,
                $description,
                $amount,
                $tax_rate,
                $tax_value,
                $total
            );
            if (!$stmt->execute()) {
                throw new Exception("Error saving invoice items: " . $stmt->error);
            }
        }
        $stmt->close();

        // Update invoice settings
        $stmt = $conn->prepare("UPDATE invoice_master SET invoice_number = invoice_number + 1 WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating invoice number: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();

        // Return JSON response with proper headers
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Invoice saved successfully!',
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoice_number
        ]);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => "Error: " . $e->getMessage()
        ]);
        exit;
    }
}

// Use the header that omits sidebar for the mobile webview
if ($from_mobile_app) {
    include 'includes/header_minimal.php';
} else {
    include 'includes/header.php';
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <div class="invoice-icon bg-primary text-white rounded-circle p-2 me-3">
                            <i class="fas fa-file-invoice fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Create New Invoice</h4>
                            <p class="text-muted mb-0">Generate a new tax invoice</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form id="invoiceForm" method="POST" action="">
                        <!-- Add hidden fields for invoice_id and user_id -->
                        <input type="hidden" id="invoice_id" name="invoice_id" value="">
                        <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id; ?>">
                        
                        <!-- Company Information Preview -->
                        <div class="company-info mb-4 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-auto">
                                    <?php if ($company['logo']): ?>
                                        <img src="<?php echo htmlspecialchars($company['logo']); ?>" alt="Company Logo" class="company-logo" style="max-height: 80px;">
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <h5 class="company-name mb-1"><?php echo htmlspecialchars($company['name']); ?></h5>
                                    <p class="mb-1 small"><?php echo nl2br(htmlspecialchars($company['address'])); ?></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-0 small">CIN: <?php echo htmlspecialchars($company['cin']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0 small">GSTIN: <?php echo htmlspecialchars($company['gstin']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Header -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="invoiceNumber" name="invoice_number" 
                                           value="<?php echo $invoice_settings ? $invoice_settings['invoice_number'] : '001'; ?>" readonly>
                                    <label for="invoiceNumber">Invoice Number</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="invoiceDate" name="invoice_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="invoiceDate">Invoice Date</label>
                                </div>
                            </div>
                        </div>

                        <!-- Client Selection -->
                        <div class="section-title mb-4">
                            <h5 class="text-primary mb-3">Bill to Party</h5>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="clientName" name="client_name" placeholder="Start typing client name..." required>
                                    <label for="clientName">Client Name</label>
                                    <input type="hidden" id="clientId" name="client_id">
                                </div>
                                <div id="clientSuggestions" class="autocomplete-suggestions"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="clientEmail" name="client_email" placeholder="Client Email">
                                    <label for="clientEmail">Client Email (Optional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="clientGSTIN" name="client_gstin" placeholder="Client GSTIN">
                                    <label for="clientGSTIN">GSTIN</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="clientAddress" name="client_address" style="height: 100px" required></textarea>
                                    <label for="clientAddress">Client Address</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="clientState" name="client_state" required>
                                    <label for="clientState">State</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-success h-100" id="saveNewClient">
                                    <i class="fas fa-user-plus me-2"></i>Save New Client
                                </button>
                            </div>
                        </div>

                        <!-- Invoice Items -->
                        <div class="section-title mb-4">
                            <h5 class="text-primary mb-3">Invoice Items</h5>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="invoiceItems">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">S.No</th>
                                        <th width="35%">Product Description</th>
                                        <th width="12%">Amount (Rs.)</th>
                                        <th width="12%">Taxable Value(Rs.)</th>
                                        <th width="8%">Rate</th>
                                        <th width="13%">GST Amount (Rs.)</th>
                                        <th width="15%">Total (Rs.)</th>
                                        <th width="5%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td>1</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm description" name="items[0][description]" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm amount" name="items[0][amount]" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm taxable-value" readonly>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm tax-rate" name="items[0][tax_rate]">
                                                <?php foreach ($tax_rates as $tax): ?>
                                                <option value="<?php echo $tax['tax']; ?>" <?php echo $tax['is_default'] ? 'selected' : ''; ?>>
                                                    <?php echo $tax['tax']; ?>% <?php echo $tax['description'] ? '- ' . $tax['description'] : ''; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm gst-amount" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm total" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm delete-row">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="8">
                                            <button type="button" class="btn btn-success btn-sm" id="addRow">
                                                <i class="fas fa-plus me-2"></i>Add Row
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>Total</strong></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="subTotal" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="totalTaxableValue" readonly>
                                        </td>
                                        <td></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="totalGST" readonly>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="grandTotal" readonly>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Tax Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="amountInWords" name="amount_in_words" placeholder="Enter amount in words" required>
                                    <label for="amountInWords">Amount in Words (Please type)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Details Preview -->
                        <div class="bank-info bg-light p-3 rounded mb-4">
                            <h6 class="mb-3">Bank Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 small"><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank['bank_name']); ?></p>
                                    <p class="mb-1 small"><strong>Account Name:</strong> <?php echo htmlspecialchars($bank['account_name']); ?></p>
                                    <p class="mb-1 small"><strong>Account Number:</strong> <?php echo htmlspecialchars($bank['account_number']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 small"><strong>IFSC Code:</strong> <?php echo htmlspecialchars($bank['ifsc']); ?></p>
                                    <p class="mb-1 small"><strong>Branch:</strong> <?php echo htmlspecialchars($bank['branch_name']); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-light me-2" onclick="window.history.back();">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Invoice
                            </button>
                        </div>
                    </form>

                    <!-- Hidden form for saving new client -->
                    <form id="saveClientForm" method="POST" action="">
                        <input type="hidden" name="save_new_client" value="1">
                        <input type="hidden" name="client_name" id="save_client_name">
                        <input type="hidden" name="client_address" id="save_client_address">
                        <input type="hidden" name="client_gstin" id="save_client_gstin">
                        <input type="hidden" name="client_state" id="save_client_state">
                        <input type="hidden" name="client_email" id="save_client_email">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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

.invoice-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-floating > .form-control,
.form-floating > .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), #0d6efd);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
}

.company-info,
.bank-info {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
}

.form-control-sm,
.form-select-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.delete-row {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

#invoiceItems tbody tr {
    transition: all 0.3s ease;
}

#invoiceItems tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

/* Autocomplete Styles */
.autocomplete-suggestions {
    border: 1px solid #ddd;
    background: #fff;
    overflow: auto;
    max-height: 200px;
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    border-radius: 0 0 8px 8px;
    display: none;
    position: absolute;
    z-index: 1000;
    width: calc(100% - 24px);
}

.autocomplete-suggestion {
    padding: 10px 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.autocomplete-suggestion:hover {
    background-color: #f5f5f5;
}

.autocomplete-selected {
    background-color: #e9ecef;
}

/* Add these styles for the invoice table */
#invoiceItems th,
#invoiceItems td {
    padding: 8px;
    vertical-align: middle;
}

#invoiceItems .form-control-sm {
    width: 100%;
}

#invoiceItems td:nth-child(7) .form-control-sm {  /* Total column */
    min-width: 100px;
}

#invoiceItems tfoot td:nth-child(7) .form-control-sm {
    min-width: 100px;
}

/* Add these styles for the grand total highlight */
#invoiceItems tfoot td:nth-child(7) {
    background-color: #e8f4ff;
}

#invoiceItems tfoot td:nth-child(7) .form-control-sm {
    min-width: 100px;
    font-weight: bold;
    font-size: 1rem;
    color: #0d6efd;
    background-color: #e8f4ff;
}

/* Style for amount in words input */
#amountInWords {
    font-weight: 500;
    color: #495057;
    text-transform: uppercase;
}

#amountInWords:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
// Define sendInvoice function in global scope
function sendInvoice(invoiceId, userId) {
    // Disable the send button
    const sendButton = document.querySelector('#sendInvoiceBtn');
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    // Create form data
    const formData = new FormData();
    formData.append('invoice_id', invoiceId);
    formData.append('user_id', userId);

    // Send the request
    fetch('send_invoice.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin' // This ensures cookies/session are sent
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Invoice sent successfully!'
            });
        } else {
            throw new Error(data.message || 'Failed to send invoice');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.message
        });
    })
    .finally(() => {
        // Re-enable the send button
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Send Invoice';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Client data for autocomplete
    const clientsData = <?php echo json_encode($clients); ?>;
    
    // Setup client autocomplete
    const clientNameInput = document.getElementById('clientName');
    const clientSuggestions = document.getElementById('clientSuggestions');
    const clientIdInput = document.getElementById('clientId');
    const clientAddressInput = document.getElementById('clientAddress');
    const clientGSTINInput = document.getElementById('clientGSTIN');
    const clientStateInput = document.getElementById('clientState');
    const clientEmailInput = document.getElementById('clientEmail');
    
    clientNameInput.addEventListener('input', function() {
        const inputVal = this.value.toLowerCase();
        
        // Clear suggestions
        clientSuggestions.innerHTML = '';
        clientSuggestions.style.display = 'none';
        
        if (inputVal.length < 2) return;
        
        // Filter clients that match input
        const matches = clientsData.filter(client => 
            client.name.toLowerCase().includes(inputVal)
        );
        
        if (matches.length > 0) {
            clientSuggestions.style.display = 'block';
            
            // Create suggestion elements
            matches.forEach(client => {
                const suggestion = document.createElement('div');
                suggestion.className = 'autocomplete-suggestion';
                suggestion.textContent = client.name;
                suggestion.addEventListener('click', function() {
                    // Fill client data
                    clientNameInput.value = client.name;
                    clientIdInput.value = client.client_id;
                    clientAddressInput.value = client.address || '';
                    clientGSTINInput.value = client.gst || '';
                    clientStateInput.value = client.state || '';
                    clientEmailInput.value = client.email || '';
                    
                    // Hide suggestions
                    clientSuggestions.style.display = 'none';
                });
                clientSuggestions.appendChild(suggestion);
            });
        }
    });
    
    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== clientNameInput && e.target !== clientSuggestions) {
            clientSuggestions.style.display = 'none';
        }
    });
    
    // Save client button
    document.getElementById('saveNewClient').addEventListener('click', function() {
        // Validate client data
        if (!clientNameInput.value.trim()) {
            alert('Please enter client name');
            clientNameInput.focus();
            return;
        }
        
        if (!clientAddressInput.value.trim()) {
            alert('Please enter client address');
            clientAddressInput.focus();
            return;
        }
        
        if (!clientStateInput.value.trim()) {
            alert('Please enter client state');
            clientStateInput.focus();
            return;
        }
        
        // Populate hidden form data
        document.getElementById('save_client_name').value = clientNameInput.value;
        document.getElementById('save_client_address').value = clientAddressInput.value;
        document.getElementById('save_client_gstin').value = clientGSTINInput.value;
        document.getElementById('save_client_state').value = clientStateInput.value;
        document.getElementById('save_client_email').value = clientEmailInput.value;
        
        // Submit form
        document.getElementById('saveClientForm').submit();
    });

    // Add new row
    document.getElementById('addRow').addEventListener('click', function() {
        const tbody = document.querySelector('#invoiceItems tbody');
        const newRow = tbody.querySelector('tr').cloneNode(true);
        const rowCount = tbody.querySelectorAll('tr').length;
        
        // Update row number
        newRow.querySelector('td:first-child').textContent = rowCount + 1;
        
        // Clear inputs
        newRow.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.name = input.name.replace('[0]', `[${rowCount}]`);
        });
        
        // Update select names
        newRow.querySelectorAll('select').forEach(select => {
            select.name = select.name.replace('[0]', `[${rowCount}]`);
        });

        tbody.appendChild(newRow);
        setupRowHandlers(newRow);
    });

    // Setup handlers for existing rows
    document.querySelectorAll('#invoiceItems tbody tr').forEach(setupRowHandlers);

    // Function to setup row handlers
    function setupRowHandlers(row) {
        const amountInput = row.querySelector('.amount');
        const taxRateSelect = row.querySelector('.tax-rate');
        const deleteBtn = row.querySelector('.delete-row');

        // Calculate values when amount or tax rate changes
        amountInput.addEventListener('input', () => calculateRow(row));
        taxRateSelect.addEventListener('change', () => calculateRow(row));

        // Delete row
        deleteBtn.addEventListener('click', function() {
            if (document.querySelectorAll('#invoiceItems tbody tr').length > 1) {
                row.remove();
                updateRowNumbers();
                calculateTotals();
            }
        });
    }

    // Function to calculate row values
    function calculateRow(row) {
        const amount = parseFloat(row.querySelector('.amount').value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 18.00;
        
        // Calculate taxable value (18% of amount)
        const taxableValue = (amount * taxRate / 100);
        row.querySelector('.taxable-value').value = taxableValue.toFixed(2);
        
        // GST amount is same as taxable value
        const gstAmount = taxableValue;
        row.querySelector('.gst-amount').value = gstAmount.toFixed(2);
        
        // Total is amount + GST amount
        const total = amount + gstAmount;
        row.querySelector('.total').value = total.toFixed(2);
        
        calculateTotals();
    }

    // Update row numbers
    function updateRowNumbers() {
        document.querySelectorAll('#invoiceItems tbody tr').forEach((row, index) => {
            row.querySelector('td:first-child').textContent = index + 1;
            row.querySelectorAll('[name^="items["]').forEach(input => {
                input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
            });
        });
    }

    // Calculate totals
    function calculateTotals() {
        let subTotal = 0;
        let totalTaxableValue = 0;
        let totalGST = 0;
        let grandTotal = 0;

        document.querySelectorAll('#invoiceItems tbody tr').forEach(row => {
            const amount = parseFloat(row.querySelector('.amount').value) || 0;
            const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 18.00;
            const taxableValue = (amount * taxRate / 100);
            const gstAmount = taxableValue;
            
            subTotal += amount;
            totalTaxableValue += taxableValue;
            totalGST += gstAmount;
            grandTotal += (amount + gstAmount);
        });

        // Update totals with null checks
        const subTotalInput = document.getElementById('subTotal');
        const totalTaxableValueInput = document.getElementById('totalTaxableValue');
        const totalGSTInput = document.getElementById('totalGST');
        const grandTotalInput = document.getElementById('grandTotal');
        const cgstInput = document.getElementById('cgst');
        const sgstInput = document.getElementById('sgst');

        if (subTotalInput) subTotalInput.value = subTotal.toFixed(2);
        if (totalTaxableValueInput) totalTaxableValueInput.value = totalTaxableValue.toFixed(2);
        if (totalGSTInput) totalGSTInput.value = totalGST.toFixed(2);
        if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);

        // Update CGST and SGST (half of total GST each)
        const halfGST = totalGST / 2;
        if (cgstInput) cgstInput.value = halfGST.toFixed(2);
        if (sgstInput) sgstInput.value = halfGST.toFixed(2);

        // Add hidden fields for form submission
        updateHiddenFields(subTotal, totalTaxableValue, totalGST, grandTotal, halfGST);
    }

    function updateHiddenFields(subTotal, totalTaxableValue, totalGST, grandTotal, halfGST) {
        // Remove any existing hidden fields
        document.querySelectorAll('input[type="hidden"][data-total]').forEach(el => el.remove());

        // Add new hidden fields
        const form = document.getElementById('invoiceForm');
        if (!form) return;

        const totals = {
            'subTotal': subTotal.toFixed(2),
            'totalTaxableValue': totalTaxableValue.toFixed(2),
            'totalGST': totalGST.toFixed(2),
            'grandTotal': grandTotal.toFixed(2),
            'cgst': halfGST.toFixed(2),
            'sgst': halfGST.toFixed(2)
        };

        // Get amount in words if the element exists
        const amountInWordsInput = document.getElementById('amountInWords');
        if (amountInWordsInput) {
            totals['amount_in_words'] = amountInWordsInput.value;
        }

        for (const [key, value] of Object.entries(totals)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            input.dataset.total = 'true';
            form.appendChild(input);
        }
    }

    // Handle form submission
    $('#invoiceForm').submit(function(e) {
        e.preventDefault();
        
        const amountInWordsInput = document.getElementById('amountInWords');
        
        // Validate amount in words
        if (amountInWordsInput && !amountInWordsInput.value.trim()) {
            alert('Please enter the amount in words');
            amountInWordsInput.focus();
            return;
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');
        
        // Update hidden fields before submission
        calculateTotals();
        
        // Add save_invoice parameter
        const formData = new FormData(this);
        formData.append('save_invoice', '1');
        
        $.ajax({
            url: window.location.href,  // Use current URL
            type: 'POST',
            data: new URLSearchParams(formData).toString(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message and template selection modal for first-time users
                    const checkTemplatePreference = function() {
                        // Check if user has already selected a template preference
                        $.ajax({
                            url: 'check_template_preference.php',
                            type: 'GET',
                            dataType: 'json',
                            success: function(templateData) {
                                if (!templateData.has_preference) {
                                    // Show template selection modal
                                    const templateHtml = `
                                        <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true" data-bs-backdrop="static">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="templateModalLabel">Choose Invoice Template</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-3">Select a template for your invoice. You can change this later in Invoice Settings.</p>
                                                        <div class="row">
                                                            ${templateData.templates.map((template, index) => `
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="card template-card h-100 ${index === 0 ? 'border-primary' : ''}" data-template-id="${template.template_id}">
                                                                        <div class="card-body p-2">
                                                                            <h6>${template.name}</h6>
                                                                            <p class="small text-muted mb-2">${template.description}</p>
                                                                            <div class="template-preview bg-light text-center p-3 mb-2" style="height: 120px; overflow: hidden;">
                                                                                ${template.preview_image ? 
                                                                                    `<img src="${template.preview_image}" class="img-fluid" alt="${template.name}" style="max-height: 100%;">` : 
                                                                                    `<div class="d-flex h-100 align-items-center justify-content-center">
                                                                                        <div>
                                                                                            <i class="fas fa-file-invoice fa-2x mb-2 text-primary"></i>
                                                                                            <p class="small mb-0">${template.name}</p>
                                                                                        </div>
                                                                                    </div>`
                                                                                }
                                                                            </div>
                                                                            <a href="generate_pdf.php?invoice_id=${response.invoice_id}&template=${template.template_id}" 
                                                                               class="btn btn-sm btn-outline-primary w-100" target="_blank">
                                                                                <i class="fas fa-eye me-1"></i> Preview
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            `).join('')}
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" id="saveTemplateBtn" class="btn btn-primary">
                                                            <i class="fas fa-check me-1"></i> Select Template
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                                    
                                    // Append modal to body
                                    $('body').append(templateHtml);
                                    
                                    // Initialize the modal
                                    const templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
                                    templateModal.show();
                                    
                                    // Handle template card clicks
                                    $('.template-card').on('click', function() {
                                        $('.template-card').removeClass('border-primary');
                                        $(this).addClass('border-primary');
                                    });
                                    
                                    // Handle save button click
                                    $('#saveTemplateBtn').on('click', function() {
                                        const selectedTemplateId = $('.template-card.border-primary').data('template-id');
                                        
                                        $.ajax({
                                            url: 'save_template_preference.php',
                                            type: 'POST',
                                            data: {
                                                template_id: selectedTemplateId
                                            },
                                            dataType: 'json',
                                            success: function(saveResponse) {
                                                templateModal.hide();
                                                showInvoiceSuccessUI();
                                            },
                                            error: function() {
                                                templateModal.hide();
                                                showInvoiceSuccessUI();
                                            }
                                        });
                                    });
                                } else {
                                    // User already has a template preference, just show success UI
                                    showInvoiceSuccessUI();
                                }
                            },
                            error: function() {
                                // On error, fall back to showing the regular success UI
                                showInvoiceSuccessUI();
                            }
                        });
                    };
                    
                    // Function to show the invoice success UI
                    const showInvoiceSuccessUI = function() {
                        const successHtml = `
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-check-circle me-2"></i>Invoice saved successfully! (ID: ${response.invoice_id})
                            </div>
                            <div class="text-center mt-3">
                                <a href="generate_pdf.php?invoice_id=${response.invoice_id}&download=1" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Download PDF
                                </a>
                                <button type="button" class="btn btn-success ms-2" id="sendInvoiceBtn" onclick="sendInvoice(${response.invoice_id}, <?php echo $user_id; ?>)">
                                    <i class="fas fa-envelope me-2"></i>Send Invoice
                                </button>
                                <a href="create_invoice.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-plus me-2"></i>Create New Invoice
                                </a>
                            </div>
                        `;
                        $('#invoiceForm').html(successHtml);
                        
                        // Create a hidden field with the invoice_id
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.id = 'invoice_id';
                        hiddenField.value = response.invoice_id;
                        document.body.appendChild(hiddenField);
                        
                        // Create a hidden field with the user_id
                        const userIdField = document.createElement('input');
                        userIdField.type = 'hidden';
                        userIdField.id = 'user_id';
                        userIdField.value = `<?php echo $user_id; ?>`;
                        document.body.appendChild(userIdField);
                    };
                    
                    // Check template preference
                    checkTemplatePreference();
                } else {
                    // Show error message
                    const errorHtml = `
                        <div class="alert alert-danger mt-3">
                            <i class="fas fa-exclamation-circle me-2"></i>${response.message || 'An error occurred while saving the invoice.'}
                        </div>
                    `;
                    $('#invoiceForm').prepend(errorHtml);
                    submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save Invoice');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                const errorHtml = `
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>An error occurred while saving the invoice. Please try again.
                    </div>
                `;
                $('#invoiceForm').prepend(errorHtml);
                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save Invoice');
            }
        });
    });
});
</script>

<?php
// Use the appropriate footer based on whether it's a mobile app view
if ($from_mobile_app) {
    include 'includes/footer_minimal.php';
} else {
    include 'includes/footer.php';
} 