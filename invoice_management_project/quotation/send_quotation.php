<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once('../config/database.php');
require_once('../includes/tcpdf/tcpdf.php');
require_once('../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user_id'];

// Fetch company details
$stmt = $conn->prepare("SELECT * FROM company_master WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

// Get quotation ID
$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$quotation_id) {
    header("Location: list_quotations.php");
    exit();
}

// Fetch quotation details
$stmt = $conn->prepare("
    SELECT q.*, c.name as client_name, c.email, c.phone, c.address, c.gst_number
    FROM quotations q 
    JOIN client_master c ON q.client_id = c.client_id 
    WHERE q.quotation_id = ? AND q.company_id = ?
");
$stmt->execute([$quotation_id, $company['company_id']]);
$quotation = $stmt->fetch();

if (!$quotation) {
    header("Location: list_quotations.php");
    exit();
}

// Fetch quotation items
$stmt = $conn->prepare("
    SELECT * FROM quotation_items 
    WHERE quotation_id = ?
    ORDER BY item_id
");
$stmt->execute([$quotation_id]);
$items = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($company['name']);
        $pdf->SetTitle('Quotation - ' . $quotation['quotation_number']);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Company logo
        if ($company['logo'] && file_exists('../uploads/logos/' . $company['logo'])) {
            $pdf->Image('../uploads/logos/' . $company['logo'], 15, 15, 50);
            $pdf->Ln(20);
        }

        // Company and client details
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'QUOTATION', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Ln(10);
        
        // From section
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(95, 5, 'From:', 0, 0);
        $pdf->Cell(95, 5, 'To:', 0, 1);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(95, 5, $company['name'] . "\n" . $company['address'] . 
            ($company['gst_number'] ? "\nGST: " . $company['gst_number'] : "") . 
            "\nPhone: " . $company['phone'] . "\nEmail: " . $company['email'], 0, 'L');
        
        // Reset Y position for client details
        $pdf->SetY($pdf->GetY() - 25);
        $pdf->SetX(110);
        
        // To section
        $pdf->MultiCell(95, 5, $quotation['client_name'] . "\n" . $quotation['address'] . 
            ($quotation['gst_number'] ? "\nGST: " . $quotation['gst_number'] : "") . 
            "\nPhone: " . $quotation['phone'] . "\nEmail: " . $quotation['email'], 0, 'L');
        
        $pdf->Ln(10);
        
        // Quotation details
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(47.5, 5, 'Quotation Number:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(47.5, 5, $quotation['quotation_number'], 0, 0);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(47.5, 5, 'Created Date:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(47.5, 5, date('d M Y', strtotime($quotation['created_at'])), 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(47.5, 5, 'Valid Until:', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(47.5, 5, date('d M Y', strtotime($quotation['valid_until'])), 0, 1);
        
        $pdf->Ln(10);
        
        // Items table
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(80, 7, 'Description', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Quantity', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'Unit Price', 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'Tax', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'Total', 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 10);
        foreach ($items as $item) {
            $pdf->MultiCell(80, 7, $item['description'], 1, 'L', false, 0);
            $pdf->Cell(25, 7, number_format($item['quantity'], 2), 1, 0, 'R');
            $pdf->Cell(30, 7, '₹' . number_format($item['unit_price'], 2), 1, 0, 'R');
            $pdf->Cell(25, 7, number_format($item['tax_rate'], 2) . '%', 1, 0, 'R');
            $pdf->Cell(30, 7, '₹' . number_format($item['total_amount'], 2), 1, 1, 'R');
        }
        
        // Totals
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(160, 7, 'Subtotal:', 1, 0, 'R');
        $pdf->Cell(30, 7, '₹' . number_format($quotation['subtotal'], 2), 1, 1, 'R');
        
        if ($quotation['tax_amount'] > 0) {
            $pdf->Cell(160, 7, 'Tax (' . number_format($quotation['tax_rate'], 2) . '%):', 1, 0, 'R');
            $pdf->Cell(30, 7, '₹' . number_format($quotation['tax_amount'], 2), 1, 1, 'R');
        }
        
        if ($quotation['discount_amount'] > 0) {
            $pdf->Cell(160, 7, 'Discount (' . number_format($quotation['discount_rate'], 2) . '%):', 1, 0, 'R');
            $pdf->Cell(30, 7, '-₹' . number_format($quotation['discount_amount'], 2), 1, 1, 'R');
        }
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(160, 7, 'Total Amount:', 1, 0, 'R');
        $pdf->Cell(30, 7, '₹' . number_format($quotation['total_amount'], 2), 1, 1, 'R');
        
        $pdf->Ln(10);
        
        // Notes and terms
        if ($quotation['notes']) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Notes:', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, $quotation['notes'], 0, 'L');
            $pdf->Ln(5);
        }
        
        if ($quotation['terms_conditions']) {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Terms & Conditions:', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, $quotation['terms_conditions'], 0, 'L');
        }
        
        // Save PDF to temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'quotation_');
        $pdf->Output($temp_file, 'F');

        // Send email
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $company['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $company['smtp_username'];
        $mail->Password = $company['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom($company['email'], $company['name']);
        $mail->addAddress($quotation['email'], $quotation['client_name']);
        if ($_POST['cc_email']) {
            $mail->addCC($_POST['cc_email']);
        }
        
        // Attachments
        $mail->addAttachment($temp_file, $quotation['quotation_number'] . '.pdf');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $_POST['subject'];
        $mail->Body = nl2br(htmlspecialchars($_POST['message']));
        $mail->AltBody = strip_tags($_POST['message']);
        
        $mail->send();
        unlink($temp_file);

        // Update quotation status if needed
        if (isset($_POST['update_status']) && $_POST['update_status'] === '1') {
            $stmt = $conn->prepare("
                UPDATE quotations 
                SET status = 'pending' 
                WHERE quotation_id = ? AND company_id = ? AND status = 'draft'
            ");
            $stmt->execute([$quotation_id, $company['company_id']]);
        }

        header("Location: view_quotation.php?id=" . $quotation_id . "&sent=1");
        exit();
    } catch (Exception $e) {
        if (isset($temp_file) && file_exists($temp_file)) {
            unlink($temp_file);
        }
        $error = "Error sending quotation: " . $e->getMessage();
    }
}

// Get default email template
$stmt = $conn->prepare("
    SELECT template_content 
    FROM email_templates 
    WHERE company_id = ? AND template_type = 'quotation'
");
$stmt->execute([$company['company_id']]);
$template = $stmt->fetch();

$default_subject = "Quotation #" . $quotation['quotation_number'] . " from " . $company['name'];
$default_message = $template ? $template['template_content'] : "Dear {$quotation['client_name']},\n\n" .
    "Please find attached our quotation #{$quotation['quotation_number']} for your review.\n\n" .
    "Total Amount: ₹" . number_format($quotation['total_amount'], 2) . "\n" .
    "Valid Until: " . date('d M Y', strtotime($quotation['valid_until'])) . "\n\n" .
    "If you have any questions, please don't hesitate to contact us.\n\n" .
    "Best regards,\n" . $company['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Quotation - <?= htmlspecialchars($quotation['quotation_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/quotation.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Send Quotation</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_quotation.php">Create Quotation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="list_quotations.php">All Quotations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">Main Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Send Quotation #<?= htmlspecialchars($quotation['quotation_number']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">To</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($quotation['client_name']) ?> <<?= htmlspecialchars($quotation['email']) ?>>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">CC</label>
                                <input type="email" name="cc_email" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($default_subject) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="10" required><?= htmlspecialchars($default_message) ?></textarea>
                            </div>

                            <?php if ($quotation['status'] === 'draft'): ?>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="update_status" value="1" class="form-check-input" id="updateStatus" checked>
                                    <label class="form-check-label" for="updateStatus">
                                        Update quotation status to Pending after sending
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between">
                                <a href="view_quotation.php?id=<?= $quotation_id ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Quotation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted">Client</label>
                            <p class="mb-1"><?= htmlspecialchars($quotation['client_name']) ?></p>
                            <p class="mb-0 small"><?= htmlspecialchars($quotation['email']) ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Amount</label>
                            <p class="mb-0">₹<?= number_format($quotation['total_amount'], 2) ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Valid Until</label>
                            <p class="mb-0"><?= date('d M Y', strtotime($quotation['valid_until'])) ?></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted">Items</label>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($items as $item): ?>
                                <li class="small">
                                    <?= htmlspecialchars($item['description']) ?>
                                    <span class="float-end">₹<?= number_format($item['total_amount'], 2) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div>
                            <label class="form-label text-muted">Attachments</label>
                            <p class="mb-0 small">
                                <i class="fas fa-file-pdf text-danger"></i>
                                <?= htmlspecialchars($quotation['quotation_number']) ?>.pdf
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 