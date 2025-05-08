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

// Get quotation ID
$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$quotation_id) {
    header("Location: list_quotations.php");
    exit();
}

// Get quotation details with client information
$stmt = $conn->prepare("
    SELECT q.*, 
           c.name as client_name, 
           c.email,
           c.address,
           cm.name as company_name,
           cm.email as company_email,
           cm.address as company_address,
           cm.phone as company_phone,
           DATE_ADD(q.quotation_date, INTERVAL q.validity_days DAY) as valid_until
    FROM quotations q
    JOIN client_master c ON q.client_id = c.client_id
    JOIN company_master cm ON q.company_id = cm.company_id
    WHERE q.quotation_id = ?
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();
$quotation = $result->fetch_assoc();

if (!$quotation) {
    die("Quotation not found");
}

// Set default values if not present
$quotation['notes'] = $quotation['notes'] ?? '';

// Get quotation items with proper tax rate
$stmt = $conn->prepare("
    SELECT 
        item_id,
        description,
        quantity,
        unit_price,
        tax_rate,
        tax_amount,
        total_amount
    FROM quotation_items 
    WHERE quotation_id = ?
    ORDER BY item_id ASC
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    if (in_array($new_status, ['pending', 'accepted', 'rejected'])) {
        try {
            $conn->begin_transaction();
            
            // Update quotation status
            $stmt = $conn->prepare("UPDATE quotations SET status = ? WHERE quotation_id = ?");
            $stmt->bind_param("si", $new_status, $quotation_id);
            $stmt->execute();
            
            // If accepted, create invoice
            if ($new_status === 'accepted') {
                // Get the next invoice number
                $result = $conn->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 4) AS UNSIGNED)) as last_num FROM invoice WHERE company_id = " . $quotation['company_id']);
                $row = $result->fetch_assoc();
                $next_num = ($row['last_num'] ?? 0) + 1;
                $invoice_number = 'INV' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
                
                // Insert invoice
                $stmt = $conn->prepare("
                    INSERT INTO invoice (
                        company_id, client_id, invoice_number, invoice_date,
                        net_total, total_tax_amount, grand_total, status
                    ) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iisddd", 
                    $quotation['company_id'],
                    $quotation['client_id'],
                    $invoice_number,
                    $quotation['total_amount'],
                    $quotation['total_tax_amount'],
                    $quotation['grand_total']
                );
                $stmt->execute();
                $invoice_id = $conn->insert_id;
                
                // Insert invoice items
                $stmt = $conn->prepare("
                    INSERT INTO invoice_description (
                        invoice_id, description, amount,
                        tax_rate, tax_value
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($items as $item) {
                    $stmt->bind_param("isddd",
                        $invoice_id,
                        $item['description'],
                        $item['unit_price'] * $item['quantity'],
                        $item['tax_rate'],
                        $item['tax_amount']
                    );
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            header("Location: view_quotation.php?id=" . $quotation_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            die("Error updating status: " . $e->getMessage());
        }
    }
}

// Fetch status history
$stmt = $conn->prepare("
    SELECT * FROM quotation_history 
    WHERE quotation_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Quotation - <?= htmlspecialchars($quotation['quotation_number']) ?></title>
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

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #e3e6f0;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: #4e73df;
            display: flex;
            align-items: center;
        }

        .card-header h5 i {
            margin-right: 0.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e3e6f0;
        }

        .table td {
            vertical-align: middle;
            border-color: #e3e6f0;
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        .btn {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-group .btn {
            padding: 0.375rem 0.75rem;
        }

        .company-details, .client-details {
            background: #fff;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .company-details h6, .client-details h6 {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .quotation-meta {
            background: #fff;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .meta-item {
            text-align: center;
            padding: 0.5rem;
        }

        .meta-item .label {
            font-size: 0.8rem;
            color: #858796;
            margin-bottom: 0.25rem;
        }

        .meta-item .value {
            font-weight: 600;
            color: #4e73df;
        }

        .totals-section {
            background: #fff;
            padding: 1.5rem;
            border-radius: var(--border-radius);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e3e6f0;
        }

        .total-row:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #4e73df;
        }

        .status-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .status-item {
            padding: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .nav-link {
            color: #6e707e;
            font-weight: 500;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
        }

        .nav-link i {
            margin-right: 0.5rem;
            width: 1.25rem;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            color: #4e73df;
            background-color: rgba(78, 115, 223, 0.1);
        }

        .sidebar-brand {
            height: 4.375rem;
            display: flex;
            align-items: center;
            padding: 1.5rem 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            color: #4e73df;
            border-bottom: 1px solid #e3e6f0;
        }

        .sidebar-heading {
            padding: 0.75rem 1rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #b7b9cc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
                <a href="list_quotations.php" class="nav-link">
                    <i class="fas fa-list"></i> All Quotations
                </a>
                
                <div class="sidebar-heading">OTHER</div>
                <a href="quotation_settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Main Dashboard
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Top Actions -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Quotation #<?= htmlspecialchars($quotation['quotation_number']) ?></h4>
                <div class="btn-group">
                    <?php if ($quotation['status'] === 'pending'): ?>
                    <a href="edit_quotation.php?id=<?= $quotation_id ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php endif; ?>
                    <a href="send_quotation.php?id=<?= $quotation_id ?>" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Send
                    </a>
                    <a href="download_quotation.php?id=<?= $quotation_id ?>" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Company & Client Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="company-details">
                                <h6><i class="fas fa-building me-2"></i>From</h6>
                                <strong><?= htmlspecialchars($quotation['company_name']) ?></strong><br>
                                <?= nl2br(htmlspecialchars($quotation['company_address'])) ?><br>
                                <i class="fas fa-phone me-2"></i><?= htmlspecialchars($quotation['company_phone']) ?><br>
                                <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($quotation['company_email']) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="client-details">
                                <h6><i class="fas fa-user me-2"></i>To</h6>
                                <strong><?= htmlspecialchars($quotation['client_name']) ?></strong><br>
                                <?= nl2br(htmlspecialchars($quotation['address'])) ?><br>
                                <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($quotation['email']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quotation Meta -->
                    <div class="quotation-meta">
                        <div class="meta-item">
                            <div class="label">Created Date</div>
                            <div class="value"><?= date('d M Y', strtotime($quotation['created_at'])) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Valid Until</div>
                            <div class="value"><?= date('d M Y', strtotime($quotation['valid_until'])) ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Status</div>
                            <div class="value">
                                <span class="badge bg-<?= 
                                    $quotation['status'] === 'accepted' ? 'success' : 
                                    ($quotation['status'] === 'rejected' ? 'danger' : 'warning') 
                                ?>">
                                    <?= ucfirst($quotation['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> Items</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Tax</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td class="text-end"><?= number_format($item['quantity']) ?></td>
                                        <td class="text-end">₹<?= number_format($item['unit_price'], 2) ?></td>
                                        <td class="text-end"><?= number_format($item['tax_rate'], 1) ?>%</td>
                                        <td class="text-end">₹<?= number_format($item['total_amount'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals Section -->
                    <div class="totals-section mt-4">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>₹<?= number_format($quotation['total_amount'], 2) ?></span>
                        </div>
                        <?php if ($quotation['tax_amount'] > 0): ?>
                        <div class="total-row">
                            <span>Tax Amount</span>
                            <span>₹<?= number_format($quotation['tax_amount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($quotation['discount_amount'] > 0): ?>
                        <div class="total-row">
                            <span>Discount</span>
                            <span>-₹<?= number_format($quotation['discount_amount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="total-row">
                            <span>Grand Total</span>
                            <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
                        </div>
                    </div>

                    <!-- Notes & Terms -->
                    <?php if (!empty($quotation['notes'])): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-sticky-note"></i> Notes</h5>
                        </div>
                        <div class="card-body">
                            <?= nl2br(htmlspecialchars($quotation['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($quotation['terms_conditions'])): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5><i class="fas fa-file-contract"></i> Terms & Conditions</h5>
                        </div>
                        <div class="card-body">
                            <?= nl2br(htmlspecialchars($quotation['terms_conditions'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <!-- Status Update Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-edit"></i> Update Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="pending" <?= $quotation['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="accepted" <?= $quotation['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                        <option value="rejected" <?= $quotation['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i> Update Status
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Status History Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Status History</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="status-history">
                                <?php foreach ($history as $entry): ?>
                                <div class="status-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= 
                                            $entry['status'] === 'accepted' ? 'success' : 
                                            ($entry['status'] === 'rejected' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($entry['status']) ?>
                                        </span>
                                        <small class="text-muted">
                                            <?= date('d M Y H:i', strtotime($entry['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php if ($entry['notes']): ?>
                                    <small class="d-block mt-2"><?= nl2br(htmlspecialchars($entry['notes'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($history)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="fas fa-history mb-2"></i>
                                    <p class="mb-0">No status updates yet</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 