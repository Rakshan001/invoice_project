<?php
// Start with no output buffering to prevent any headers being sent
ob_start();

// Basic error handling setup
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start session but don't require it
session_start();

// Set JSON content type header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Include dependencies
require_once 'config/database.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Check if vendor directory exists and require autoload
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Vendor directory not found. Please make sure PHPMailer is installed.'
    ]);
    exit;
}

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to format large numbers
function formatLargeNumber($number) {
    if (empty($number)) return '0';
    $number = str_replace(',', '', $number);
    $number = floatval($number);
    $isNegative = $number < 0;
    $number = abs($number);
    $isWholeNumber = (floor($number) == $number);
    
    if ($isWholeNumber) {
        $wholePart = floor($number);
        $numStr = strval($wholePart);
        $len = strlen($numStr);
        
        if ($len > 3) {
            $firstPart = substr($numStr, 0, $len - 3);
            $lastPart = substr($numStr, -3);
            
            if (strlen($firstPart) > 2) {
                $formatted = preg_replace('/(\d+?)(?=(\d\d)+$)/', '$1,', $firstPart);
            } else {
                $formatted = $firstPart;
            }
            
            $formatted .= ',' . $lastPart;
        } else {
            $formatted = $numStr;
        }
    } else {
        $wholePart = floor($number);
        $decimalPart = $number - $wholePart;
        $numStr = strval($wholePart);
        $len = strlen($numStr);
        
        if ($len > 3) {
            $firstPart = substr($numStr, 0, $len - 3);
            $lastPart = substr($numStr, -3);
            
            if (strlen($firstPart) > 2) {
                $formatted = preg_replace('/(\d+?)(?=(\d\d)+$)/', '$1,', $firstPart);
            } else {
                $formatted = $firstPart;
            }
            
            $formatted .= ',' . $lastPart;
        } else {
            $formatted = $numStr;
        }
        
        if ($decimalPart > 0) {
            $formatted .= number_format($decimalPart, 1, '.', '');
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.');
        }
    }
    
    return $isNegative ? '-' . $formatted : $formatted;
}

try {
    // Get invoice_id and user_id from request
    $invoice_id = filter_var($_REQUEST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
    $user_id = filter_var($_REQUEST['user_id'] ?? $_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$invoice_id || !$user_id) {
        throw new Exception('Invalid invoice ID or user ID.');
    }

    // Get company and invoice details
    $stmt = $conn->prepare("
        SELECT 
            cm.email, cm.email_password, cm.name as company_name, cm.address as company_address,
            cm.phone as company_phone, cm.gstin as company_gstin, cm.cin as company_cin,
            cm.logo as company_logo, cm.seal as company_seal, cm.sign as company_sign,
            i.*, c.email as client_email, c.name as client_name, c.state as client_state,
            b.account_number, b.ifsc, b.bank_name, b.branch_name
        FROM company_master cm
        JOIN invoice i ON i.company_id = cm.company_id
        JOIN client_master c ON i.client_id = c.client_id
        LEFT JOIN company_bank b ON i.bank_id = b.company_bank_id
        WHERE i.invoice_id = ? AND cm.user_id = ?
    ");
    
    $stmt->execute([$invoice_id, $user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        throw new Exception('Invoice or company details not found.');
    }

    if (!$data['email'] || !$data['email_password']) {
        throw new Exception('Company email settings not configured');
    }

    if (!$data['client_email']) {
        throw new Exception('Client email not found');
    }

    // Get invoice items
    $stmt = $conn->prepare("
        SELECT 
            s_no,
            description,
            amount,
            tax_value as taxable_value,
            tax_rate,
            tax_value as gst_amount,
            amount as total_amount
        FROM invoice_description 
        WHERE invoice_id = ? 
        ORDER BY s_no
    ");
    $stmt->execute([$invoice_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception('No items found for this invoice');
    }

    // Get user's preferred template
    $stmt = $conn->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = 'invoice_template'");
    $stmt->execute([$user_id]);
    $template_preference = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($template_preference && !empty($template_preference['preference_value'])) {
        $template_id = $template_preference['preference_value'];
    } else {
        $stmt = $conn->prepare("SELECT template_id FROM invoice_templates WHERE is_default = 1 LIMIT 1");
        $stmt->execute();
        $default_template = $stmt->fetch(PDO::FETCH_ASSOC);
        $template_id = $default_template ? $default_template['template_id'] : 1;
    }

    // Create temporary directory if it doesn't exist
    $temp_dir = sys_get_temp_dir();
    $pdf_filename = 'invoice_' . $data['invoice_number'] . '_' . time() . '.pdf';
    $pdf_path = $temp_dir . DIRECTORY_SEPARATOR . $pdf_filename;

    // Calculate CGST and SGST
    $data['cgst'] = $data['total_tax_amount'] / 2;
    $data['sgst'] = $data['total_tax_amount'] / 2;

    // Generate PDF using the selected template
    try {
        $template_file = __DIR__ . '/invoice_templates/template_' . $template_id . '.php';
        if (!file_exists($template_file)) {
            $template_file = __DIR__ . '/invoice_templates/template_1.php';
        }
        
        // Include the template file to generate the PDF
        $invoice = $data;
        include $template_file;
        
        // Save the PDF
        $pdf->Output($pdf_path, 'F');

        if (!file_exists($pdf_path)) {
            throw new Exception('Error generating PDF file');
        }
    } catch (Exception $e) {
        throw new Exception('PDF Generation Error: ' . $e->getMessage());
    }

    // Get email template content
    $stmt = $conn->prepare("
        SELECT template_content, design 
        FROM email_templates 
        WHERE company_id = ? AND template_type = 'invoice'
    ");
    $stmt->execute([$data['company_id']]);
    $email_template = $stmt->fetch(PDO::FETCH_ASSOC);

    // Default template sections
    $default_sections = [
        'subject' => 'Invoice #{{INVOICE_NUMBER}} from {{COMPANY_NAME}}',
        'greeting' => 'Dear {{CLIENT_NAME}},',
        'main_content' => "Please find attached the invoice #{{INVOICE_NUMBER}} dated {{INVOICE_DATE}} for amount {{INVOICE_AMOUNT}}.\n\nIf you have any questions, please don't hesitate to contact us.",
        'signature' => "Best Regards,\n{{COMPANY_NAME}}\nPhone: {{COMPANY_PHONE}}\nEmail: {{COMPANY_EMAIL}}"
    ];

    // Default HTML design
    $default_design = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #6366f1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .invoice-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #6366f1; }
        .footer { background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; }
        .company-info { color: #4f46e5; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice #{{INVOICE_NUMBER}}</h2>
    </div>
    <div class="content">
        {{GREETING}}
        <p>{{MAIN_CONTENT}}</p>
        <div class="invoice-details">
            <p><strong>Invoice Number:</strong> {{INVOICE_NUMBER}}</p>
            <p><strong>Date:</strong> {{INVOICE_DATE}}</p>
            <p><strong>Amount:</strong> Rs. {{INVOICE_AMOUNT}}</p>
        </div>
        <div class="company-info">
            <p>For any queries regarding this invoice, please contact us:</p>
            <p><strong>{{COMPANY_NAME}}</strong></p>
            <p>Phone: {{COMPANY_PHONE}}</p>
            <p>Email: {{COMPANY_EMAIL}}</p>
        </div>
    </div>
    <div class="footer">
        &copy; {{CURRENT_YEAR}} {{COMPANY_NAME}}. All rights reserved.
    </div>
</body>
</html>';

    // Get template content and design
    if ($email_template && !empty($email_template['template_content'])) {
        $template_sections = json_decode($email_template['template_content'], true);
        $html_design = !empty($email_template['design']) ? $email_template['design'] : $default_design;
    } else {
        $template_sections = $default_sections;
        $html_design = $default_design;
    }

    // Prepare replacement variables
    $replacements = [
        '{{COMPANY_NAME}}' => $data['company_name'],
        '{{CLIENT_NAME}}' => $data['client_name'],
        '{{INVOICE_NUMBER}}' => $data['invoice_number'],
        '{{INVOICE_DATE}}' => date('d/m/Y', strtotime($data['invoice_date'])),
        '{{INVOICE_AMOUNT}}' => 'Rs. ' . formatLargeNumber($data['net_total']),
        '{{COMPANY_PHONE}}' => $data['company_phone'],
        '{{COMPANY_EMAIL}}' => $data['email'],
        '{{CURRENT_YEAR}}' => date('Y'),
        '{{DUE_DATE}}' => !empty($data['due_date']) ? date('d/m/Y', strtotime($data['due_date'])) : 'N/A',
        '{{DAYS_OVERDUE}}' => !empty($data['due_date']) ? max(0, floor((time() - strtotime($data['due_date'])) / 86400)) : 0
    ];

    // Replace variables in sections first
    foreach ($template_sections as $key => $content) {
        $template_sections[$key] = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    // Add processed sections to replacements
    $replacements['{{GREETING}}'] = $template_sections['greeting'];
    $replacements['{{MAIN_CONTENT}}'] = $template_sections['main_content'];

    // Replace variables in the HTML design
    $emailBody = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $html_design
    );

    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $data['email'];
        $mail->Password = $data['email_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($data['email'], $data['company_name']);
        $mail->addAddress($data['client_email'], $data['client_name']);
        $mail->addReplyTo($data['email'], $data['company_name']);

        $mail->isHTML(true);
        $mail->Subject = $template_sections['subject'];
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(
            $template_sections['greeting'] . "\n\n" .
            $template_sections['main_content'] . "\n\n" .
            $template_sections['signature']
        );

        // Attach PDF
        $mail->addAttachment($pdf_path, 'Invoice-' . $data['invoice_number'] . '.pdf');

        $mail->send();

        // Clean up the temporary PDF file
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }

        ob_end_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Invoice sent successfully to ' . $data['client_email']
        ]);

    } catch (Exception $e) {
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }
        throw new Exception('Email sending failed: ' . $mail->ErrorInfo);
    }

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>