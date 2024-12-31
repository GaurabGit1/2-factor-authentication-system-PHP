<?php
session_start();
$error_message = "";
$success_message = "";

// Set the timezone to Kathmandu, Nepal
date_default_timezone_set('Asia/Kathmandu');

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate OTP
function generateOTP() {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
}

// Function to send OTP via email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gaurabbajgain84@gmail.com';
        $mail->Password   = 'mbwl ejuv vkqw opkv'; // Replace with your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipient
        $mail->setFrom('gaurabbajgain84@gmail.com', 'Gaurab');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>. The code is valid for 2 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mysqli = require __DIR__ . "/db.php";

    if (isset($_POST["resend_otp"])) {
        // Resending OTP case
        $email = $_POST["email"];
        
        // Generate a new OTP and store it
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 minutes'));

        // Insert or update OTP in the database
        $sql = "INSERT INTO otp_requests (email, otp, expires_at) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE otp=?, expires_at=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            die("SQL error: " . $mysqli->error);
        }
        $stmt->bind_param("sssss", $email, $otp, $expires_at, $otp, $expires_at);
        $stmt->execute();

        // Send the OTP via email
        if (sendOTPEmail($email, $otp)) {
            $success_message = "A new OTP has been sent to your email.";
        } else {
            $error_message = "Failed to send OTP. Please try again.";
        }
    } else {
        // OTP verification case
        $email = $_POST["email"];
        $otp = $_POST["otp"];

        // Ensure that the database connection is fresh for this query
        $mysqli->close();
        $mysqli = require __DIR__ . "/db.php";

        // Validate OTP against the database
        $sql = "SELECT * FROM otp_requests WHERE email = ? AND otp = ? AND expires_at >= NOW() 
                ORDER BY expires_at DESC LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            die("SQL error: " . $mysqli->error);
        }
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // OTP is valid
            $_SESSION["user_email"] = $email;
            header("Location: enter_pass.php");
            exit;
        } else {
            $error_message = "Invalid or expired OTP.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.rtl.min.css">
    <title>Verify OTP</title>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center">Verify OTP</h1>
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
                <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email']) ?>">
                <div class="mb-3">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input class="form-control" type="text" id="otp" name="otp" required>
                </div>
                <hr class="mb-4">
                <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
            </form>
            <form method="post" class="mt-3">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_GET['email']) ?>">
                <button type="submit" name="resend_otp" class="btn btn-secondary w-100">Resend OTP</button>
            </form>
        </div>
    </div>
</body>
</html>
