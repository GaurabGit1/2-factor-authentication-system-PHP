<?php
session_start();
$mysqli = require __DIR__ . "/db.php";

// Redirect to login page if the user is not logged in
if (!isset($_SESSION["user_email"])) {
    header("Location: login.php");
    exit;
}

// Handle logout request
if (isset($_GET['logout'])) {
    session_unset(); // Clear all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to login page
    exit;
}
$_SESSION['verified']=null;
// Fetch user details if logged in
$user = null;
if (isset($_SESSION["user_email"])) {
    $sql = "SELECT name, email FROM user_credentials WHERE email = ?";
    $stmt = $mysqli->stmt_init();
    if (!$stmt->prepare($sql)) {
        die("SQL error: " . $mysqli->error);
    }
    $stmt->bind_param("s", $_SESSION["user_email"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Debugging: Check if user is fetched correctly
    if (!$user) {
        error_log("No user found with email: " . $_SESSION["user_email"]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.rtl.min.css">
    <title>Home</title>
    <style>
        body {
            padding-top: 56px; /* Height of the taskbar */
        }
        .taskbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #007bff; /* Blue color */
            color: white;
            padding: 10px;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .taskbar .btn {
            color: white;
            margin: 0 5px;
        }
        .taskbar .logout-button {
            background-color: #007bff; /* Blue color */
            border: none;
        }
        .taskbar .logout-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
        .taskbar .btn-light {
            background-color: transparent;
            border-color: transparent;
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        .card-header {
            background-color: #f7f7f7;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .profile-info {
            margin-bottom: 15px;
        }
        .thank-you {
            margin-top: 20px;
            font-size: 1.2em;
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body class="bg-light">
    <div class="taskbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <a href="index.php" class="btn btn-light">Home</a>
                <a href="your_info.php" class="btn btn-light">Your Info</a>
                <a href="media.php" class="btn btn-light">Media</a>
                <a href="settings.php" class="btn btn-light">Settings</a>
            </div>
            <div>
                <a href="?logout=true" class="btn logout-button">Log Out</a>
            </div>
        </div>
    </div>
    <div class="container mt-5 pt-5">
        <?php if ($user): ?>
            <div class="card p-4 shadow-sm">
                <div class="card-body text-center">
                    <img src="https://via.placeholder.com/100" alt="Profile Picture" class="profile-pic">
                    <h4 class="mb-2"><?= htmlspecialchars($user["name"]) ?></h4>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user["email"]) ?></p>
                    <p class="thank-you">Thank you for joining our app! We are excited to help you enhance your coding skills and find great internship opportunities.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center">
                <p>You are not logged in.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
