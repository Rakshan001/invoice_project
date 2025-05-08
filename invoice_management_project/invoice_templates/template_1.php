<?php
/**
 * Template 1: Modern Clean
 * A clean, minimalist template with subtle dividers
 */

// Create PDF document if not already created
if (!isset($pdf)) {
    // Create PDF class with header support
    class MYPDF extends TCPDF {
        protected $headerCompany;
        protected $headerLogo;
        protected $pageCount = 0;
        
        public function setCompanyData($company, $logo) {
            $this->headerCompany = $company;
            $this->headerLogo = $logo;
        }
    }

    // Create new PDF document (A4 format)
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setCompanyData($invoice, $invoice['company_logo']);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($invoice['company_name']);
    $pdf->SetTitle('Invoice #' . $invoice['invoice_number']);

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
}

// Set margins - make them smaller for a more compact layout
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

// Add a page
$pdf->AddPage();

// Set colors
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetFillColor(173, 216, 230);

// ----- COMPANY HEADER SECTION -----
// Company Logo on the left
if (!empty($invoice['company_logo']) && file_exists($invoice['company_logo'])) {
    $pdf->Image($invoice['company_logo'], 10, 10, 25);
    $startY = 40;
} else {
    $startY = 25;
}

// Company Name
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(40, 10);
$pdf->Cell(160, 6, strtoupper($invoice['company_name']), 0, 1, 'L');

// Company Details - more compact
$pdf->SetFont('helvetica', '', 8);
$pdf->SetXY(40, 17);
$companyDetails = $invoice['company_address'];
if (!empty($invoice['company_phone'])) {
    $companyDetails .= "\nTel: " . $invoice['company_phone'];
}
if (!empty($invoice['company_gstin'])) {
    $companyDetails .= " | GSTIN: " . $invoice['company_gstin'];
}
if (!empty($invoice['company_cin'])) {
    $companyDetails .= " | CIN: " . $invoice['company_cin'];
}
$pdf->MultiCell(160, 4, $companyDetails, 0, 'L');

// Subtle separator line
$pdf->Line(10, 35, 200, 35);

// ----- TAX INVOICE TITLE -----
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(70, 70, 70);
$pdf->SetXY(10, 37);
$pdf->Cell(190, 8, 'INVOICE', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// ----- INVOICE DETAILS SECTION -----
$y = 47;
$pdf->SetFont('helvetica', '', 9);

// Invoice Number and Date (same row)
$pdf->SetXY(10, $y);
$pdf->Cell(25, 6, 'Invoice No:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(65, 6, $invoice['invoice_number'], 0, 0, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(25, 6, 'Date:', 0, 0, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(75, 6, date('d/m/Y', strtotime($invoice['invoice_date'])), 0, 1, 'L');

// State - only show if client state exists
if (!empty($invoice['client_state'])) {
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(10, $y + 6);
    $pdf->Cell(25, 6, 'State:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(165, 6, $invoice['client_state'], 0, 1, 'L');
    $y += 6;
}

// Separator line
$pdf->Line(10, $y + 14, 200, $y + 14);

// ----- BILL TO PARTY SECTION -----
// Only show if client information exists
if (!empty($invoice['client_name'])) {
    $y += 16;
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 6, 'Bill to Party', 0, 1, 'L');

    // Bill to details - more compact
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(10, $y + 6);
    $pdf->Cell(25, 6, 'Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(165, 6, $invoice['client_name'], 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY(10, $y + 12);
    $pdf->Cell(25, 6, 'Address:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(165, 6, $invoice['client_address'], 0, 1, 'L');

    if (!empty($invoice['client_gstin'])) {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(10, $y + 18);
        $pdf->Cell(25, 6, 'GSTIN:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(65, 6, $invoice['client_gstin'], 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(25, 6, 'State:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(75, 6, $invoice['client_state'], 0, 1, 'L');
    }

    // Separator line
    $pdf->Line(10, $y + 26, 200, $y + 26);
    $y += 28;
} else {
    $y += 16;
}

// ----- ITEMS SECTION -----
$y += 28;

// Define column widths
$colWidth = array(
    'sno' => 10,
    'desc' => 50,  // Reduced description width
    'amount' => 30, // Increased amount width
    'taxable' => 30, // Increased taxable width
    'rate' => 15,
    'gst' => 25, // Increased GST width
    'total' => 30  // Increased total width
);

// Headers
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(10, $y);
$pdf->Cell($colWidth['sno'], 6, 'S.No', 0, 0, 'C', 1);
$pdf->Cell($colWidth['desc'], 6, 'Description', 0, 0, 'L', 1);
$pdf->Cell($colWidth['amount'], 6, 'Amount', 0, 0, 'R', 1);
$pdf->Cell($colWidth['taxable'], 6, 'Taxable Value', 0, 0, 'R', 1);
$pdf->Cell($colWidth['rate'], 6, 'Rate', 0, 0, 'C', 1);
$pdf->Cell($colWidth['gst'], 6, 'GST Amt', 0, 0, 'R', 1);
$pdf->Cell($colWidth['total'], 6, 'Total', 0, 1, 'R', 1);

// Items content
$y += 6;
$pdf->SetFont('helvetica', '', 8);
foreach ($items as $index => $item) {
    $pdf->SetXY(10, $y);
    
    // S.No
    $pdf->Cell($colWidth['sno'], 6, $index + 1, 0, 0, 'C');
    
    // Description - Allow multiple lines
    $x = $pdf->GetX();
    $descHeight = $pdf->getStringHeight($colWidth['desc'], $item['description']);
    $rowHeight = max(6, $descHeight);
    $pdf->MultiCell($colWidth['desc'], $rowHeight/2, $item['description'], 0, 'L');
    
    // Reset X and Y for remaining cells
    $pdf->SetXY($x + $colWidth['desc'], $y);
    
    // Amount columns with right alignment and number formatting
    $pdf->Cell($colWidth['amount'], $rowHeight, number_format($item['amount'], 2), 0, 0, 'R');
    $pdf->Cell($colWidth['taxable'], $rowHeight, number_format($item['taxable_value'], 2), 0, 0, 'R');
    $pdf->Cell($colWidth['rate'], $rowHeight, $item['tax_rate'] . '%', 0, 0, 'C');
    $pdf->Cell($colWidth['gst'], $rowHeight, number_format($item['gst_amount'], 2), 0, 0, 'R');
    $pdf->Cell($colWidth['total'], $rowHeight, number_format($item['total_amount'], 2), 0, 1, 'R');
    
    $y += $rowHeight;
}

// Total row
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetXY(10, $y);
$pdf->Cell($colWidth['sno'] + $colWidth['desc'], 6, 'Total', 0, 0, 'L', 1);
$pdf->Cell($colWidth['amount'], 6, number_format($invoice['total_amount'], 2), 0, 0, 'R', 1);
$pdf->Cell($colWidth['taxable'], 6, number_format($invoice['taxable_value'], 2), 0, 0, 'R', 1);
$pdf->Cell($colWidth['rate'], 6, '', 0, 0, 'C', 1);
$pdf->Cell($colWidth['gst'], 6, number_format($invoice['total_tax_amount'], 2), 0, 0, 'R', 1);
$pdf->Cell($colWidth['total'], 6, number_format($invoice['net_total'], 2), 0, 1, 'R', 1);

// Amount in words
$y += 10; // Increased gap
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(10, $y);
$pdf->Cell(190, 6, 'Amount in Words: ' . ucfirst(strtolower($invoice['rupees_in_words'])) . ' Only', 0, 1, 'L');

// Bank and Tax Details with increased gap
$y += 12; // Increased gap
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(10, $y);
$pdf->Cell(95, 6, 'Bank Details', 0, 0, 'L');
$pdf->Cell(95, 6, 'Tax Details', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$y += 6;

// Bank Details - use values from company_bank table
$pdf->SetXY(10, $y);
$pdf->Cell(95, 5, 'A/C: ' . $invoice['account_number'], 0, 0, 'L');
$pdf->Cell(30, 5, 'CGST (' . ($invoice['tax_rate']/2) . '%)', 0, 0, 'L');
$pdf->Cell(65, 5, number_format($invoice['cgst'], 2), 0, 1, 'R');

$pdf->SetXY(10, $y + 5);
$pdf->Cell(95, 5, 'IFSC: ' . $invoice['ifsc'], 0, 0, 'L');
$pdf->Cell(30, 5, 'SGST (' . ($invoice['tax_rate']/2) . '%)', 0, 0, 'L');
$pdf->Cell(65, 5, number_format($invoice['sgst'], 2), 0, 1, 'R');

$pdf->SetXY(10, $y + 10);
$pdf->Cell(95, 5, 'Bank: ' . $invoice['bank_name'], 0, 0, 'L');
$pdf->Cell(30, 5, 'Total Tax', 0, 0, 'L');
$pdf->Cell(65, 5, number_format($invoice['total_tax_amount'], 2), 0, 1, 'R');

$pdf->SetXY(10, $y + 15);
$pdf->Cell(95, 5, 'Branch: ' . $invoice['branch_name'], 0, 0, 'L');
$pdf->Cell(30, 5, 'Total Amount', 0, 0, 'L');
$pdf->Cell(65, 5, number_format($invoice['net_total'], 2), 0, 1, 'R');

// Increased gap before signature section
$y += 35;

// Calculate center positions for seal and signature
$pageWidth = $pdf->getPageWidth();
$sealX = 40;  // Left side center
$signX = $pageWidth - 70;  // Right side center
$imageWidth = 45;

// Left side - Common Seal
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY($sealX - 30, $y);
$pdf->Cell(60, 5, 'Common Seal', 0, 1, 'C');

// Right side - Company name and Authorised Signatory
$pdf->SetXY($signX - 60, $y);
$pdf->Cell(120, 5, 'For ' . strtoupper($invoice['company_name']), 0, 1, 'R');

$pdf->SetXY($signX - 30, $y + 8);
$pdf->Cell(60, 5, 'Authorised Signatory', 0, 1, 'C');

// Add seal and signature images
if (!empty($invoice['company_seal']) && file_exists($invoice['company_seal'])) {
    $pdf->Image($invoice['company_seal'], $sealX - ($imageWidth/2), $y + 15, $imageWidth);
}

if (!empty($invoice['company_sign']) && file_exists($invoice['company_sign'])) {
    $pdf->Image($invoice['company_sign'], $signX - ($imageWidth/2), $y + 15, $imageWidth);
}