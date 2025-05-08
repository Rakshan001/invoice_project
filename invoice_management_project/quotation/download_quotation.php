<?php
// Prevent any output before PDF generation
ob_start();

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once('../config/database.php');
require_once('../vendor/autoload.php');

// Clear any output buffers
ob_clean();

class MYPDF extends TCPDF {
    // Page header with border
    public function Header() {
        // Define border colors
        $this->SetDrawColor(70, 130, 180); // Steel blue
        
        // Draw border around the page
        $this->Rect(5, 5, $this->getPageWidth()-10, $this->getPageHeight()-10, 'D');
        
        // Draw a second inner border with a different color
        $this->SetDrawColor(65, 105, 225); // Royal blue
        $this->Rect(7, 7, $this->getPageWidth()-14, $this->getPageHeight()-14, 'D');
    }
    
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-20);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Footer line
        $this->SetDrawColor(70, 130, 180);
        $this->Line(15, $this->GetY(), $this->getPageWidth()-15, $this->GetY());
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

// Get quotation ID
$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$quotation_id) {
    die("Invalid quotation ID");
}

try {
    // Get quotation details with bank details
    $stmt = $conn->prepare("
        SELECT q.*, 
               c.name as client_name, 
               c.email,
               c.address,
               cm.name as company_name,
               cm.email as company_email,
               cm.address as company_address,
               cm.phone as company_phone,
               cm.logo as company_logo,
               b.bank_name,
               b.account_name,
               b.account_number,
               b.branch_name,
               b.ifsc
        FROM quotations q
        JOIN client_master c ON q.client_id = c.client_id
        JOIN company_master cm ON q.company_id = cm.company_id
        LEFT JOIN company_bank b ON cm.company_id = b.company_id
        WHERE q.quotation_id = ?
    ");
    $stmt->execute([$quotation_id]);
    $quotation = $stmt->fetch();

    if (!$quotation) {
        throw new Exception("Quotation not found");
    }

    // Get quotation items
    $stmt = $conn->prepare("
        SELECT * FROM quotation_items 
        WHERE quotation_id = ?
        ORDER BY item_id ASC
    ");
    $stmt->execute([$quotation_id]);
    $items = $stmt->fetchAll();

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($quotation['company_name']);
    $pdf->SetTitle('Quotation #' . $quotation['quotation_number']);

    // Set margins
    $pdf->SetMargins(20, 25, 20);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Set default font
    $pdf->SetFont('helvetica', '', 11);

    // Add a page
    $pdf->AddPage();

    // Define colors
    $headerBgColor = array(65, 105, 225); // Royal Blue
    $subheaderBgColor = array(100, 149, 237); // Cornflower Blue
    $accentColor = array(30, 144, 255); // Dodger Blue
    $textColor = array(0, 0, 0); // Black
    $highlightTextColor = array(0, 51, 102); // Dark Blue

    // Company Header with Logo
    $pdf->SetFillColor(248, 249, 252); // Very light blue background
    $pdf->Rect(20, 25, $pdf->getPageWidth()-40, 40, 'F');
    
    // Check if logo exists and add it
    if (!empty($quotation['company_logo'])) {
        $logoPath = '../uploads/logos/' . $quotation['company_logo'];
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 25, 30, 30, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $companyInfoX = 60;
        } else {
            $companyInfoX = 25;
        }
    } else {
        $companyInfoX = 25;
    }
    
    // Company name and details
    $pdf->SetXY($companyInfoX, 30);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor($highlightTextColor[0], $highlightTextColor[1], $highlightTextColor[2]);
    $pdf->Cell(100, 8, $quotation['company_name'], 0, 1, 'L');
    
    $pdf->SetXY($companyInfoX, 38);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->MultiCell(100, 5, $quotation['company_address'], 0, 'L');
    
    $pdf->SetXY($companyInfoX, $pdf->GetY()+1);
    $pdf->Cell(100, 5, 'Phone: ' . $quotation['company_phone'], 0, 1, 'L');
    $pdf->SetXY($companyInfoX, $pdf->GetY());
    $pdf->Cell(100, 5, 'Email: ' . $quotation['company_email'], 0, 1, 'L');
    
    // Horizontal line after header
    $pdf->SetDrawColor($accentColor[0], $accentColor[1], $accentColor[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 70, $pdf->getPageWidth()-20, 70);
    $pdf->SetLineWidth(0.2);
    
    // Date and Place - Right aligned
    $pdf->SetXY(20, 75);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(($pdf->getPageWidth()-40)/2, 6, 'QUOTATION', 0, 0, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(($pdf->getPageWidth()-40)/2, 6, 'Date: ' . date('d F Y', strtotime($quotation['created_at'])), 0, 1, 'R');
    
    // Extract city from address for place
    $address_parts = explode(',', $quotation['company_address']);
    $place = trim(end($address_parts));
    $pdf->SetX(($pdf->getPageWidth()-40)/2 + 20);
    $pdf->Cell(($pdf->getPageWidth()-40)/2, 6, 'Place: ' . $place, 0, 1, 'R');
    
    $pdf->Ln(5);

    // To Section
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(20, 6, 'To:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $quotation['client_name'], 0, 1, 'L');
    $pdf->MultiCell(0, 6, $quotation['address'], 0, 'L');
    $pdf->Cell(0, 6, 'Email: ' . $quotation['email'], 0, 1, 'L');
    
    $pdf->Ln(5);

    // Quotation Number and Title
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Quotation #: ' . $quotation['quotation_number'], 0, 1, 'L');
    
    // Quotation title if exists in the database
    if (!empty($quotation['title'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Re: ' . $quotation['title'], 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    // Items Table with colored header
    $pdf->SetFillColor($headerBgColor[0], $headerBgColor[1], $headerBgColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    // Colorful table header
    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(85, 8, 'Description', 1, 0, 'L', true);
    $pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Unit Price', 1, 0, 'R', true);
    $pdf->Cell(15, 8, 'Tax %', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Total', 1, 1, 'R', true);

    // Reset text color
    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
    $pdf->SetFillColor(248, 248, 248); // Light gray for alternate rows
    
    // Table Content
    $pdf->SetFont('helvetica', '', 10);
    $subtotal = 0;
    $fill = false;
    
    foreach ($items as $index => $item) {
        $pdf->Cell(10, 8, ($index + 1), 1, 0, 'C', $fill);
        $pdf->Cell(85, 8, $item['description'], 1, 0, 'L', $fill);
        $pdf->Cell(15, 8, number_format($item['quantity'], 2), 1, 0, 'C', $fill);
        $pdf->Cell(25, 8, number_format($item['unit_price'], 2), 1, 0, 'R', $fill);
        $pdf->Cell(15, 8, number_format($item['tax_rate'], 2) . '%', 1, 0, 'C', $fill);
        $pdf->Cell(25, 8, number_format($item['total_amount'], 2), 1, 1, 'R', $fill);
        $subtotal += ($item['quantity'] * $item['unit_price']);
        $fill = !$fill; // Alternate row colors
    }

    // Summary with styled background
    $pdf->SetFillColor(240, 248, 255); // Alice blue for summary
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(150, 8, 'Subtotal:', 1, 0, 'R', true);
    $pdf->Cell(25, 8, number_format($quotation['subtotal'], 2), 1, 1, 'R', true);
    
    $pdf->Cell(150, 8, 'Tax Amount:', 1, 0, 'R', true);
    $pdf->Cell(25, 8, number_format($quotation['tax_amount'], 2), 1, 1, 'R', true);
    
    if ($quotation['discount_amount'] > 0) {
        $pdf->Cell(150, 8, 'Discount (' . number_format($quotation['discount_rate'], 2) . '%):', 1, 0, 'R', true);
        $pdf->Cell(25, 8, number_format($quotation['discount_amount'], 2), 1, 1, 'R', true);
    }
    
    // Total with different color
    $pdf->SetFillColor($subheaderBgColor[0], $subheaderBgColor[1], $subheaderBgColor[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(150, 10, 'Total Amount:', 1, 0, 'R', true);
    $pdf->Cell(25, 10, number_format($quotation['total_amount'], 2), 1, 1, 'R', true);
    
    // Reset text color
    $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

    // Start new page for scope and payment details
    $pdf->AddPage();

    // Heading style for sections
    $sectionHeadingStyle = function($pdf, $title) use ($accentColor, $textColor) {
        $pdf->SetFillColor($accentColor[0], $accentColor[1], $accentColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, $title, 0, 1, 'L', true);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Ln(3);
    };

    // Scope Section
    $sectionHeadingStyle($pdf, '  SCOPE [ASSUMPTIONS & DEPENDENCIES]');
    
    if (!empty($quotation['scope'])) {
        $scopeText = $quotation['scope'];
    } else {
        $scopeText = "• Customer enquiries from contact us page, feedback page etc. must be managed by client itself\n• 10 working days to complete the project once the requirement is freeze.";
    }
    
    // Process bullet points
    $scopeLines = explode("\n", $scopeText);
    foreach ($scopeLines as $line) {
        $pdf->MultiCell(0, 7, $line, 0, 'L');
    }
    
    $pdf->Ln(10);

    // Mode of Payment
    $sectionHeadingStyle($pdf, '  MODE OF PAYMENT');
    
    if (!empty($quotation['payment_terms'])) {
        $paymentText = $quotation['payment_terms'];
    } else {
        $paymentText = "• 50% Advance - against purchase order\n• 50% Balance payment - against delivery\n• All the payments must be made in Cheque/DD in the name of " . $quotation['company_name'] . ".";
    }
    
    // Process bullet points
    $paymentLines = explode("\n", $paymentText);
    foreach ($paymentLines as $line) {
        $pdf->MultiCell(0, 7, $line, 0, 'L');
    }
    
    $pdf->Ln(10);

    // Notes section if available
    if (!empty($quotation['notes'])) {
        $sectionHeadingStyle($pdf, '  NOTES');
        $pdf->MultiCell(0, 7, $quotation['notes'], 0, 'L');
        $pdf->Ln(10);
    }

    // Terms & Conditions if available
    if (!empty($quotation['terms_conditions'])) {
        $sectionHeadingStyle($pdf, '  TERMS & CONDITIONS');
        $pdf->MultiCell(0, 7, $quotation['terms_conditions'], 0, 'L');
        $pdf->Ln(10);
    }

    // Account Details with styled box
    if (!empty($quotation['account_name']) && !empty($quotation['account_number'])) {
        $pdf->SetFillColor(248, 249, 252); // Very light blue background
        $pdf->Rect(20, $pdf->GetY(), $pdf->getPageWidth()-40, 45, 'F');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor($highlightTextColor[0], $highlightTextColor[1], $highlightTextColor[2]);
        $pdf->Cell(0, 10, 'Account Details:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->Cell(40, 7, 'A/C Name:', 0, 0, 'L');
        $pdf->Cell(0, 7, $quotation['account_name'], 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'Bank Name:', 0, 0, 'L');
        $pdf->Cell(0, 7, $quotation['bank_name'], 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'A/C No:', 0, 0, 'L');
        $pdf->Cell(0, 7, $quotation['account_number'], 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'Branch:', 0, 0, 'L');
        $pdf->Cell(0, 7, $quotation['branch_name'], 0, 1, 'L');
        
        $pdf->Cell(40, 7, 'IFSC Code:', 0, 0, 'L');
        $pdf->Cell(0, 7, $quotation['ifsc'], 0, 1, 'L');
    }
    
    $pdf->Ln(10);

    // Signature box at the bottom
    $pdf->SetY($pdf->GetY() + 10);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'For ' . $quotation['company_name'], 0, 1, 'R');
    $pdf->Ln(15);
    $pdf->Cell(0, 8, 'Managing Director', 0, 1, 'R');

    // Output the PDF
    ob_end_clean();
    $pdf->Output('Quotation_' . $quotation['quotation_number'] . '.pdf', 'D');
    exit();
    
} catch (Exception $e) {
    ob_end_clean();
    die("Error generating PDF: " . $e->getMessage());
} 