<?php
session_start();
$error_message = "";
$success_message = "";

// Composer autoload to use PHPMailer
require __DIR__ . '/vendor/autoload.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set the timezone to ensure consistency in date and time operations
date_default_timezone_set('Asia/Kathmandu');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mysqli = require __DIR__ . "/db.php";
    
    $email = $_POST["email"];
    
    // Check if the email exists in the user_credentials table
    $sql = "SELECT * FROM user_credentials WHERE email = ?";
    $stmt = $mysqli->stmt_init();
    if (!$stmt->prepare($sql)) {
        die("SQL error: " . $mysqli->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email exists, proceed to generate and send OTP
        $otp = generateOTP();
        $expires_at = date("Y-m-d H:i:s", strtotime("+5 seconds"));

        // Insert or update OTP in the otp_requests table
        $otp_sql = "INSERT INTO otp_requests (email, otp, expires_at) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE otp=?, expires_at=?";
        $otp_stmt = $mysqli->stmt_init();
        if (!$otp_stmt->prepare($otp_sql)) {
            die("SQL error: " . $mysqli->error);
        }
        $otp_stmt->bind_param("sssss", $email, $otp, $expires_at, $otp, $expires_at);
        $otp_stmt->execute();

        // Send OTP via email
        if (sendOTPEmail($email, $otp)) {
            header("Location: verify_otp.php?email=" . urlencode($email));
            exit;
        } else {
            $error_message = "Unable to send OTP. Please try again.";
        }
    } else {
        $error_message = "Email not found.";
    }
}

// Function to generate a 6-character OTP
function generateOTP() {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
}

// Function to send OTP via email using PHPMailer
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gaurabbajgain84@gmail.com'; // Your email address
        $mail->Password   = 'mbwl ejuv vkqw opkv'; // Your email app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Set sender and recipient
        $mail->setFrom('gaurabbajgain84@gmail.com', 'Gaurab'); 
        $mail->addAddress($email); 
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>. The code is valid for 5 seconds.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.rtl.min.css">
    <title>Login</title>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center">Login</h1>
            <hr class="mb-4">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input class="form-control" type="email" id="email" name="email" required>
                </div>
                <hr class="mb-4">
                <button type="submit" class="btn btn-primary w-100">Send OTP</button>
            </form>
            <p class="mt-3 text-center">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </p>
        </div>
    </div>
</body>
</html>
