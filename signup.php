<?php
$error_message = "";
$success_message = "";

// Include the database connection
include 'db.php'; // Ensure that this file correctly initializes the $mysqli connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirmation = trim($_POST['password_confirmation']);

    // Validate name (only alphabets)
    if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
        $error_message = "Please enter a valid name. Only alphabets are allowed.";
    }
    // Validate email (must be a valid email format)
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    }
    // Validate password (at least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character)
    elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/", $password)) {
        $error_message = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }
    // Validate that passwords match
    elseif ($password !== $password_confirmation) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $sql = "SELECT * FROM user_credentials WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            $error_message = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Email address already exists.";
            } else {
                // Hash the password before saving it to the database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database
                $sql = "INSERT INTO user_credentials (name, email, password_hash) VALUES (?, ?, ?)";
                $stmt = $mysqli->prepare($sql);

                if (!$stmt) {
                    $error_message = "Database error: " . $mysqli->error;
                } else {
                    $stmt->bind_param("sss", $name, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        header("Location: login.php?signup=success");
                        exit();
                    } else {
                        $error_message = "Error occurred during registration.";
                    }
                }
            }

            $stmt->close();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.rtl.min.css">
    <style>
        /* Limit form width */
        .card {
            max-width: 400px;
        }

        /* Error message styles */
        .error-message {
            color: red;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            display: none; /* Hide by default */
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem; /* Add some space between form groups */
        }

        /* Adjust input field padding to create space for error messages */
        .form-group input {
            padding-right: 10px; /* Adjust padding to prevent overlap */
        }
    </style>
    <title>Sign Up</title>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center">Sign Up</h1>
            <hr class="mb-4">

            <form id="signup-form" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post" novalidate>
                <div class="mb-3 form-group">
                    <label for="name" class="form-label">Name</label>
                    <input class="form-control" type="text" id="name" name="name" pattern="[A-Za-z ]+" title="Please enter only alphabets" required>
                    <div class="error-message" id="name-error"></div>
                </div>
                <div class="mb-3 form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" required>
                    <div class="error-message" id="email-error"></div>
                </div>
                <div class="mb-3 form-group">
                    <label for="password" class="form-label">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                    <div class="error-message" id="password-error"></div>
                </div>
                <div class="mb-3 form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
                    <div class="error-message" id="password-confirmation-error"></div>
                </div>
                <hr class="mb-4">
                <button type="submit" class="btn btn-primary w-100" id="signup-button" disabled>Sign Up</button>
            </form>
            <p class="mt-3 text-center">Already have an account? <a href="login.php">Log In</a></p>
        </div>
    </div>

    <!-- JavaScript validation -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('signup-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const signupButton = document.getElementById('signup-button');

        const nameError = document.getElementById('name-error');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');
        const passwordConfirmationError = document.getElementById('password-confirmation-error');

        const namePattern = /^[a-zA-Z ]+$/;
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

        // Function to show or hide the error message for the active input field
        function validateField(input, pattern, errorElement, errorMessage) {
            if (!pattern.test(input.value.trim())) {
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        }

        // Function to validate passwords match
        function validatePasswordsMatch() {
            if (passwordInput.value.trim() !== passwordConfirmationInput.value.trim()) {
                passwordConfirmationError.textContent = 'Passwords do not match.';
                passwordConfirmationError.style.display = 'block';
            } else {
                passwordConfirmationError.style.display = 'none';
            }
        }

        // Function to check overall form validity and enable/disable signup button
        function checkFormValidity() {
            validateField(nameInput, namePattern, nameError, 'Please enter a valid name. Only alphabets are allowed.');
            validateField(emailInput, emailPattern, emailError, 'Please enter a valid email address.');
            validateField(passwordInput, passwordPattern, passwordError, 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.');
            validatePasswordsMatch();

            signupButton.disabled = nameError.style.display === 'block' ||
                                    emailError.style.display === 'block' ||
                                    passwordError.style.display === 'block' ||
                                    passwordConfirmationError.style.display === 'block';
        }

        // Event listeners to validate on input
        nameInput.addEventListener('input', checkFormValidity);
        emailInput.addEventListener('input', checkFormValidity);
        passwordInput.addEventListener('input', checkFormValidity);
        passwordConfirmationInput.addEventListener('input', checkFormValidity);
    });
    </script>
</body>
</html>
