<?php
/**
 * Template 2: Professional Table
 * A clean template with table-based layout
 */

// Create PDF document if not already created
if (!isset($pdf)) {
    class MYPDF extends TCPDF {
        protected $headerCompany;
        protected $headerLogo;
        
        public function setCompanyData($company, $logo) {
            $this->headerCompany = $company;
            $this->headerLogo = $logo;
        }
    }

    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setCompanyData($invoice, $invoice['company_logo']);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($invoice['company_name']);
    $pdf->SetTitle('Invoice #' . $invoice['invoice_number']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
}

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

// Add a page
$pdf->AddPage();

// Set colors
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetFillColor(245, 245, 245);

// Company Header Section
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

// Company Details
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

// Draw table border
$pdf->Line(10, 35, 200, 35);

// Invoice Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY(10, 40);
$pdf->Cell(190, 8, 'TAX INVOICE', 0, 1, 'C');

// Invoice Details Table
$pdf->SetFont('helvetica', '', 9);
$y = 50;

// Create a table for invoice details
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('helvetica', 'B', 9);

// Invoice details in table format
$pdf->SetXY(10, $y);
$pdf->Cell(30, 6, 'Invoice No:', 1, 0, 'L', true);
$pdf->Cell(65, 6, $invoice['invoice_number'], 1, 0, 'L');
$pdf->Cell(30, 6, 'Date:', 1, 0, 'L', true);
$pdf->Cell(65, 6, date('d/m/Y', strtotime($invoice['invoice_date'])), 1, 1, 'L');

// Only show state if client state exists
if (!empty($invoice['client_state'])) {
    $pdf->SetXY(10, $y + 6);
    $pdf->Cell(30, 6, 'State:', 1, 0, 'L', true);
    $pdf->Cell(160, 6, $invoice['client_state'], 1, 1, 'L');
    $y = $pdf->GetY();
}

// Bill to Party Details - Only show if client information exists
if (!empty($invoice['client_name'])) {
    $y = $pdf->GetY() + 5;
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY(10, $y);
    $pdf->Cell(190, 6, 'Bill to Party', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(245, 245, 245);

    // Bill to details in table format
    $y = $pdf->GetY();
    $pdf->SetXY(10, $y);
    $pdf->Cell(30, 6, 'Name:', 1, 0, 'L', true);
    $pdf->Cell(160, 6, $invoice['client_name'], 1, 1, 'L');

    $pdf->SetXY(10, $y + 6);
    $pdf->Cell(30, 6, 'Address:', 1, 0, 'L', true);
    $pdf->Cell(160, 6, $invoice['client_address'], 1, 1, 'L');

    if (!empty($invoice['client_gstin'])) {
        $pdf->SetXY(10, $y + 12);
        $pdf->Cell(30, 6, 'GSTIN:', 1, 0, 'L', true);
        $pdf->Cell(65, 6, $invoice['client_gstin'], 1, 0, 'L');
        $pdf->Cell(30, 6, 'State:', 1, 0, 'L', true);
        $pdf->Cell(65, 6, $invoice['client_state'], 1, 1, 'L');
    }
} else {
    $y = $pdf->GetY() + 5;
}

// Items Table
$y = $pdf->GetY() + 5;
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(245, 245, 245);

// Table Headers
$pdf->SetXY(10, $y);
$pdf->Cell(10, 7, 'No.', 1, 0, 'C', true);
$pdf->Cell(70, 7, 'Description', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Amount', 1, 0, 'R', true);
$pdf->Cell(25, 7, 'Taxable Value', 1, 0, 'R', true);
$pdf->Cell(15, 7, 'Rate', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'GST Amt', 1, 0, 'R', true);
$pdf->Cell(25, 7, 'Total', 1, 1, 'R', true);

// Table Content
$pdf->SetFont('helvetica', '', 9);
$y = $pdf->GetY();
foreach ($items as $index => $item) {
    $pdf->SetXY(10, $y);
    $pdf->Cell(10, 6, $index + 1, 1, 0, 'C');
    $pdf->Cell(70, 6, $item['description'], 1, 0, 'L');
    $pdf->Cell(25, 6, number_format($item['amount'], 2), 1, 0, 'R');
    $pdf->Cell(25, 6, number_format($item['taxable_value'], 2), 1, 0, 'R');
    $pdf->Cell(15, 6, $item['tax_rate'] . '%', 1, 0, 'C');
    $pdf->Cell(20, 6, number_format($item['gst_amount'], 2), 1, 0, 'R');
    $pdf->Cell(25, 6, number_format($item['total_amount'], 2), 1, 1, 'R');
    $y = $pdf->GetY();
}

// Total Row
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(80, 6, 'Total', 1, 0, 'L', true);
$pdf->Cell(25, 6, number_format($invoice['total_amount'], 2), 1, 0, 'R', true);
$pdf->Cell(25, 6, number_format($invoice['taxable_value'], 2), 1, 0, 'R', true);
$pdf->Cell(15, 6, '', 1, 0, 'C', true);
$pdf->Cell(20, 6, number_format($invoice['total_tax_amount'], 2), 1, 0, 'R', true);
$pdf->Cell(25, 6, number_format($invoice['net_total'], 2), 1, 1, 'R', true);

// Amount in Words
$y = $pdf->GetY() + 5;
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(10, $y);
$pdf->Cell(190, 6, 'Amount in Words: ' . ucfirst(strtolower($invoice['rupees_in_words'])), 0, 1, 'L');

// Bank and Tax Details Tables
$y = $pdf->GetY() + 5;
$pdf->SetFont('helvetica', 'B', 9);

// Create two tables side by side
$pdf->SetXY(10, $y);
$pdf->Cell(95, 6, 'Bank Details', 1, 0, 'L', true);
$pdf->Cell(95, 6, 'Tax Details', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 8);
$y = $pdf->GetY();

// Bank Details
$pdf->SetXY(10, $y);
$pdf->Cell(30, 6, 'A/C:', 1, 0, 'L', true);
$pdf->Cell(65, 6, $invoice['account_number'], 1, 0, 'L');
$pdf->Cell(30, 6, 'CGST (' . ($invoice['tax_rate']/2) . '%)', 1, 0, 'L', true);
$pdf->Cell(65, 6, number_format($invoice['cgst'], 2), 1, 1, 'R');

$pdf->SetXY(10, $y + 6);
$pdf->Cell(30, 6, 'IFSC:', 1, 0, 'L', true);
$pdf->Cell(65, 6, $invoice['ifsc'], 1, 0, 'L');
$pdf->Cell(30, 6, 'SGST (' . ($invoice['tax_rate']/2) . '%)', 1, 0, 'L', true);
$pdf->Cell(65, 6, number_format($invoice['sgst'], 2), 1, 1, 'R');

$pdf->SetXY(10, $y + 12);
$pdf->Cell(30, 6, 'Bank:', 1, 0, 'L', true);
$pdf->Cell(65, 6, $invoice['bank_name'], 1, 0, 'L');
$pdf->Cell(30, 6, 'Total Tax', 1, 0, 'L', true);
$pdf->Cell(65, 6, number_format($invoice['total_tax_amount'], 2), 1, 1, 'R');

$pdf->SetXY(10, $y + 18);
$pdf->Cell(30, 6, 'Branch:', 1, 0, 'L', true);
$pdf->Cell(65, 6, $invoice['branch_name'], 1, 0, 'L');
$pdf->Cell(30, 6, 'Total Amount', 1, 0, 'L', true);
$pdf->Cell(65, 6, number_format($invoice['net_total'], 2), 1, 1, 'R');

// Signature Section
$y = $pdf->GetY() + 10;
$pdf->SetFont('helvetica', '', 9);

// Left side - Common Seal
$pdf->SetXY(40, $y);
$pdf->Cell(60, 5, 'Common Seal', 0, 1, 'C');

// Right side - Company name and Authorised Signatory
$pdf->SetXY($pageWidth - 100, $y);
$pdf->Cell(60, 5, 'For ' . strtoupper($invoice['company_name']), 0, 1, 'C');
$pdf->SetXY($pageWidth - 100, $y + 8);
$pdf->Cell(60, 5, 'Authorised Signatory', 0, 1, 'C');

// Add seal and signature images
if (!empty($invoice['company_seal']) && file_exists($invoice['company_seal'])) {
    $pdf->Image($invoice['company_seal'], 45, $y + 10, 40);
}

if (!empty($invoice['company_sign']) && file_exists($invoice['company_sign'])) {
    $pdf->Image($invoice['company_sign'], $pageWidth - 90, $y + 15, 40);
}
 
