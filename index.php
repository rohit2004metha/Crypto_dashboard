<?php
require_once 'functions.php';

$message = '';
$show_welcome = false;
$verified_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle registration email submission
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode();
            // Store code in a temp file for verification
            file_put_contents(__DIR__ . "/verification_code_{$email}.txt", $code);
            if (sendVerificationEmail($email, $code)) {
                $message = "A verification code has been sent to your email.";
            } else {
                $message = "Failed to send verification code. Please try again.";
            }
        } else {
            $message = "Invalid email address.";
        }
    }
    
    // Handle verification code submission
    if (isset($_POST['email']) && isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        $code = trim($_POST['verification_code']);
        $stored_code_file = __DIR__ . "/verification_code_{$email}.txt";
        if (file_exists($stored_code_file)) {
            $stored_code = file_get_contents($stored_code_file);
            if ($code === $stored_code) {
                if (registerEmail($email)) {
                    $message = "Your email has been verified and registered successfully!";
                    $show_welcome = true;
                    $verified_email = $email;
                } else {
                    $message = "This email is already registered.";
                }
                unlink($stored_code_file);
            } else {
                $message = "Invalid verification code.";
            }
        } else {
            $message = "No verification code found for this email. Please request again.";
        }
    }
}

// Handle unsubscription success message
if (isset($_GET['message']) && $_GET['message'] === 'unsubscribed') {
    $message = "You have been successfully unsubscribed from XKCD comics.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>XKCD Email Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        .alert.hide {
            opacity: 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($show_welcome): ?>
            <div class="welcome-section">
                <h2>Welcome to XKCD Comics!</h2>
                <p>Thank you for subscribing, <?php echo htmlspecialchars($verified_email); ?>!</p>
                <p>You will now receive a random XKCD comic every day.</p>
                <a href="unsubscribe.php" class="unsubscribe-link">Unsubscribe from XKCD Comics</a>
            </div>
        <?php else: ?>
            <h2>Register for XKCD Comics</h2>
            <?php if ($message): ?>
                <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?> alert">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>
            
            <form method="POST">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
                <button id="submit-email">Submit</button>
            </form>
            
            <br>
            <h3>Enter Verification Code</h3>
            <form method="POST">
                <label for="verify_email">Email:</label>
                <input type="email" name="email" id="verify_email" required>
                <label for="verification_code">Verification Code:</label>
                <input type="text" name="verification_code" id="verification_code" maxlength="6" required>
                <button id="submit-verification">Verify</button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        // Function to hide alerts after 3 seconds
        function hideAlerts() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('hide');
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }, 3000);
            });
        }

        // Run the function when the page loads
        document.addEventListener('DOMContentLoaded', hideAlerts);
    </script>
</body>
</html> 