<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (empty($_POST["name"])) {
    die("Name is required");
}
if (empty($_POST["email"])) {
    die("E-mail address is required");
}
if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    die("Invalid E-mail Address");
}
if (strlen($_POST["password"]) < 8) {
    die("Password must be at least 8 characters");
}
if (!preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}
if (!preg_match("/[0-9]/i", $_POST["password"])) {
    die("Password must contain at least one number");
}
if ($_POST["password"] !== $_POST["password_confirmation"]) {
    die("Password doesn't match");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$mysqli = require __DIR__ . "/db.php";

$sql = "INSERT INTO user_credentials (name, email, password_hash) VALUES (?, ?, ?)";

$stmt = $mysqli->stmt_init();

if (!$stmt->prepare($sql)) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("sss", $_POST["name"], $_POST["email"], $password_hash);

try {
    $stmt->execute();
    // Send OTP
    $otp = generateOTP();
    $expires_at = date("Y-m-d H:i:s", strtotime("+2 minutes"));
    
    $otp_sql = "INSERT INTO otp_requests (email, otp, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp=?, expires_at=?";
    $otp_stmt = $mysqli->stmt_init();
    if (!$otp_stmt->prepare($otp_sql)) {
        die("SQL error: " . $mysqli->error);
    }
    $otp_stmt->bind_param("sssss", $_POST["email"], $otp, $expires_at, $otp, $expires_at);
    $otp_stmt->execute();
    
    sendOTPEmail($_POST["email"], $otp);
    
    header("Location: verify_otp.php?email=" . urlencode($_POST["email"]));
    exit;
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) { // Duplicate entry error code
        header("Location: signup.php?error=email_exists");
        exit;
    } else {
        die($e->getMessage());
    }
}

function generateOTP() {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
}

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = 'horrorstoryteller47@gmail.com'; // SMTP username
        $mail->Password = 'bhoot@123'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('horrorstoryteller47@gmail.com', 'Your Name');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>";
        $mail->send();
    } catch (Exception $e) {
        die("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>
