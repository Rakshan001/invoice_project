<?php
function convertNumberToWords($number) {
    $words = array(
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 
        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 
        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty', 
        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
    );

    if ($number < 20) {
        return $words[$number];
    } elseif ($number < 100) {
        return $words[10 * floor($number / 10)] . ($number % 10 ? ' ' . $words[$number % 10] : '');
    } elseif ($number < 1000) {
        return $words[floor($number / 100)] . ' hundred' . ($number % 100 ? ' ' . convertNumberToWords($number % 100) : '');
    } elseif ($number < 100000) {
        return convertNumberToWords(floor($number / 1000)) . ' thousand' . ($number % 1000 ? ' ' . convertNumberToWords($number % 1000) : '');
    } elseif ($number < 10000000) {
        return convertNumberToWords(floor($number / 100000)) . ' lakh' . ($number % 100000 ? ' ' . convertNumberToWords($number % 100000) : '');
    } elseif ($number < 1000000000) {
        return convertNumberToWords(floor($number / 10000000)) . ' crore' . ($number % 10000000 ? ' ' . convertNumberToWords($number % 10000000) : '');
    }
    return '';
}

function amountInWords($amount) {
    // Split amount into main number and decimal parts
    $parts = explode('.', (string)$amount);
    $rupees = (int)$parts[0];
    $paise = isset($parts[1]) ? (int)$parts[1] : 0;
    
    if ($paise > 0) {
        // If the decimal part is a single digit, multiply by 10
        if ($paise < 10) {
            $paise *= 10;
        } else if ($paise > 99) {
            // If more than 2 digits, take only first 2
            $paise = (int)substr($paise, 0, 2);
        }
        
        $words = convertNumberToWords($rupees) . ' rupees and ' . convertNumberToWords($paise) . ' paise only';
    } else {
        $words = convertNumberToWords($rupees) . ' rupees only';
    }
    
    // Capitalize the first letter of each word
    return ucwords($words);
} 