<?php
session_start();
$error_message = "";

//requiring my database file in this code
$mysqli = require __DIR__ . "/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_SESSION["user_email"];
    $password = $_POST["password"];
    $_SESSION['verified']=true;
    // selecting password stored in hashed form in my database
    $sql = "SELECT password_hash FROM user_credentials WHERE email = ?";
    $stmt = $mysqli->stmt_init();
    if (!$stmt->prepare($sql)) {
        die("SQL error: " . $mysqli->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashed_password = $user["password_hash"];
        
        // checking if the user entered password matches with hashed password
        if (password_verify($password, $hashed_password)) {
            // if the password matches
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.rtl.min.css">
    <title>Enter Password</title>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center">Enter Password</h1>
            <hr class="mb-4">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
                <hr class="mb-4">
                <button type="submit" class="btn btn-primary w-100">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
