<?php
// Start output buffering at the very beginning
ob_start();

session_start();
require_once 'config/database.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Check if invoice_id is provided
if (!isset($_GET['invoice_id']) || !isset($_SESSION['user_id'])) {
    header("Location: invoices.php");
    exit();
}

// Check if a specific template was requested for preview
$preview_template = isset($_GET['template']) ? intval($_GET['template']) : null;

// If not in preview mode, get the user's preferred template
if (!$preview_template) {
    $stmt = $conn->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = 'invoice_template'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $template_preference = $result->fetch_assoc();
    $stmt->close();
    
    if ($template_preference && !empty($template_preference['preference_value'])) {
        $template_id = $template_preference['preference_value'];
    } else {
        // If no preference is set, get the default template
        $stmt = $conn->prepare("SELECT template_id FROM invoice_templates WHERE is_default = 1 LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $default_template = $result->fetch_assoc();
        $stmt->close();
        $template_id = $default_template ? $default_template['template_id'] : 1;
    }
} else {
    // Use the template ID from the URL for preview
    $template_id = $preview_template;
}

// Get template details
$stmt = $conn->prepare("SELECT * FROM invoice_templates WHERE template_id = ?");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$result = $stmt->get_result();
$template = $result->fetch_assoc();
$stmt->close();

// If template not found, use the default one
if (!$template) {
    $stmt = $conn->prepare("SELECT * FROM invoice_templates WHERE is_default = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    // If still no template, use template ID 1
    if (!$template) {
        $template = [
            'template_id' => 1,
            'name' => 'Modern Clean',
            'description' => 'A clean, minimalist template with subtle dividers',
            'template_type' => 'tcpdf'
        ];
    }
}

// Function to format large numbers
function formatLargeNumber($number) {
    // Remove any existing formatting and convert to float
    $number = str_replace(',', '', $number);
    $number = floatval($number);
    
    // Format for Indian numbering system
    $isNegative = $number < 0;
    $number = abs($number);
    
    // Check if number is a whole number
    $isWholeNumber = (floor($number) == $number);
    
    // For whole numbers, don't show any decimals
    if ($isWholeNumber) {
        $wholePart = floor($number);
        $numStr = strval($wholePart);
        $len = strlen($numStr);
        
        // Format according to Indian numbering system
        if ($len > 3) {
            $firstPart = substr($numStr, 0, $len - 3);
            $lastPart = substr($numStr, -3);
            
            // Add commas for lakhs and crores
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
        // Handle decimal numbers - show only necessary decimals
        $wholePart = floor($number);
        $decimalPart = $number - $wholePart;
        
        // Format whole part according to Indian numbering system
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
        
        // Add decimal part with only one decimal place
        if ($decimalPart > 0) {
            $formatted .= number_format($decimalPart, 2, '.', '');
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.');
        }
    }
    
    // Add negative sign if needed
    if ($isNegative) {
        $formatted = '-' . $formatted;
    }
    
    return $formatted;
}

// Convert number to words (Indian currency format)
function numberToWords($number) {
    $number = round($number, 2);
    $wholePart = floor($number);
    $decimalPart = round(($number - $wholePart) * 100);
    
    $digit = array(
        0 => "zero", 1 => "one", 2 => "two", 3 => "three", 4 => "four", 5 => "five",
        6 => "six", 7 => "seven", 8 => "eight", 9 => "nine", 10 => "ten",
        11 => "eleven", 12 => "twelve", 13 => "thirteen", 14 => "fourteen", 15 => "fifteen",
        16 => "sixteen", 17 => "seventeen", 18 => "eighteen", 19 => "nineteen"
    );
    
    $ten = array(
        1 => "ten", 2 => "twenty", 3 => "thirty", 4 => "forty", 5 => "fifty",
        6 => "sixty", 7 => "seventy", 8 => "eighty", 9 => "ninety"
    );
    
    $words = "";
    
    if ($wholePart <= 19) {
        $words = $digit[$wholePart];
    } else if ($wholePart <= 99) {
        $words = $ten[floor($wholePart / 10)];
        if ($wholePart % 10 > 0) {
            $words .= " " . $digit[$wholePart % 10];
        }
    } else if ($wholePart <= 999) {
        $words = $digit[floor($wholePart / 100)] . " hundred";
        if ($wholePart % 100 > 0) {
            $words .= " and " . numberToWords($wholePart % 100);
        }
    } else if ($wholePart <= 9999) {
        $words = $digit[floor($wholePart / 1000)] . " thousand";
        if ($wholePart % 1000 > 0) {
            if ($wholePart % 1000 < 100) {
                $words .= " and " . numberToWords($wholePart % 1000);
            } else {
                $words .= " " . numberToWords($wholePart % 1000);
            }
        }
    } else if ($wholePart <= 99999) {
        $words = numberToWords(floor($wholePart / 1000)) . " thousand";
        if ($wholePart % 1000 > 0) {
            if ($wholePart % 1000 < 100) {
                $words .= " and " . numberToWords($wholePart % 1000);
            } else {
                $words .= " " . numberToWords($wholePart % 1000);
            }
        }
    } else if ($wholePart <= 999999) {
        $words = numberToWords(floor($wholePart / 100000)) . " lakh";
        if ($wholePart % 100000 > 0) {
            $words .= " " . numberToWords($wholePart % 100000);
        }
    } else if ($wholePart <= 9999999) {
        $words = numberToWords(floor($wholePart / 1000000)) . " million";
        if ($wholePart % 1000000 > 0) {
            $words .= " " . numberToWords($wholePart % 1000000);
        }
    } else if ($wholePart <= 99999999) {
        $words = numberToWords(floor($wholePart / 10000000)) . " crore";
        if ($wholePart % 10000000 > 0) {
            $words .= " " . numberToWords($wholePart % 10000000);
        }
    }
    
    if ($decimalPart > 0) {
        $words .= " rupees and " . numberToWords($decimalPart) . " paise only";
    } else {
        $words .= " rupees only";
    }
    
    return $words;
}

try {
    // Fetch invoice details with proper company details
    $sql = "SELECT i.*, c.*, 
            cm.name as company_name, 
            cm.address as company_address,
            cm.phone as company_phone,
            cm.gstin as company_gstin,
            cm.cin as company_cin,
            cm.logo as company_logo,
            cm.seal as company_seal,
            cm.sign as company_sign,
            b.account_number,
            b.ifsc,
            b.bank_name,
            b.branch_name
            FROM invoice i 
            LEFT JOIN client_master c ON i.client_id = c.client_id 
            LEFT JOIN company_master cm ON i.company_id = cm.company_id 
            LEFT JOIN company_bank b ON i.bank_id = b.company_bank_id
            WHERE i.invoice_id = ? AND i.company_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $_GET['invoice_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();
    $stmt->close();

    if (!$invoice) {
        throw new Exception("Invoice not found.");
    }

    // Split total tax amount for CGST and SGST
    $invoice['cgst'] = $invoice['total_tax_amount'] / 2;
    $invoice['sgst'] = $invoice['total_tax_amount'] / 2;

    // Fetch invoice items with their exact values from invoice_description
    $sql = "SELECT 
            s_no,
            description,
            amount,
            tax_value as taxable_value,
            tax_rate,
            tax_value as gst_amount,
            amount as total_amount
            FROM invoice_description 
            WHERE invoice_id = ?
            ORDER BY s_no ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_GET['invoice_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    
    // Make sure we clear all output buffers completely before generating the PDF
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Store the last invoice ID for template preview functionality
    $_SESSION['last_invoice_id'] = $_GET['invoice_id'];

    // Load the appropriate template based on template_id
    $template_file = 'invoice_templates/template_' . $template_id . '.php';
    
    // If template file doesn't exist, use default template 1
    if (!file_exists($template_file)) {
        $template_file = 'invoice_templates/template_1.php';
    }
    
    // Start a new output buffer for template inclusion
    ob_start();
    
    // Include the template file to generate the PDF
    include $template_file;
    
    // Clean any accidentally output content
    ob_end_clean();

    // Output the PDF with no additional output
    $output_mode = isset($_GET['download']) ? 'D' : 'I';
    $pdf->Output('Invoice-' . $invoice['invoice_number'] . '.pdf', $output_mode);
    exit();

} catch (Exception $e) {
    // Clean all output buffers before redirecting
    while (ob_get_level()) {
        ob_end_clean();
    }
    $_SESSION['error'] = "Error generating PDF: " . $e->getMessage();
    header("Location: invoices.php");
    exit();
}