<?php
require_once 'vendor/autoload.php';

// PHPMailer libraries
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Mail configuration constants
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'shrishamayyav23@gmail.com'); // Change this to your email
define('SMTP_PASSWORD', 'naon tpii qitg rban'); // Change this to your app password
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', 'Invoice System');

// Function to send verification OTP
function sendVerificationOTP($email, $otp) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #4e54c8;'>Email Verification</h2>
                <p>Your verification code is:</p>
                <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                    {$otp}
                </div>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
                <p>Best regards,<br>Invoice System Team</p>
            </div>
        ";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Generate a random 6-digit OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Store OTP in database
function storeOTP($conn, $email, $otp) {
    try {
        // Check if verification_code column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
        
        // If verification_code column doesn't exist, add it along with verification_expiry
        if ($checkColumn->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) NULL, ADD COLUMN verification_expiry DATETIME NULL");
        }
        
        // Set expiry time to 10 minutes from now
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update the user record with the new OTP and expiry
        $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $otp, $expiry, $email);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error storing OTP: " . $e->getMessage());
        return false;
    }
}

// Verify OTP
function verifyOTP($conn, $email, $otp) {
    try {
        // Check if email_verified column exists
        $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
        
        // If email_verified column doesn't exist, add it
        if ($checkColumn->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        }
        
        $now = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_code = ? AND verification_expiry > ?");
        $stmt->bind_param("sss", $email, $otp, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mark email as verified
            $updateStmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expiry = NULL WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            $updateStmt->execute();
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error verifying OTP: " . $e->getMessage());
        return false;
    }
} 