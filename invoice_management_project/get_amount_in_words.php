<?php
require_once 'includes/amount_to_words.php';

// Get amount from GET parameter
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

// Convert to words
$words = amountInWords($amount);

// Return the result
echo $words;
?> 