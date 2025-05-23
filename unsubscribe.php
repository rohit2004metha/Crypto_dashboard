<?php
require_once 'functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle unsubscribe email submission
    if (isset($_POST['unsubscribe_email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['unsubscribe_email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $code = generateVerificationCode();
            // Store code in a temp file for verification
            file_put_contents(__DIR__ . "/unsubscribe_code_{$email}.txt", $code);
            if (sendVerificationEmail($email, $code, true)) {
                $message = "A verification code has been sent to your email.";
            } else {
                $message = "Failed to send confirmation code. Please try again.";
            }
        } else {
            $message = "Invalid email address.";
        }
    }

    // Handle verification code submission
    if (isset($_POST['verification_code']) && isset($_POST['unsubscribe_email'])) {
        $email = trim($_POST['unsubscribe_email']);
        $code = trim($_POST['verification_code']);
        $stored_code_file = __DIR__ . "/unsubscribe_code_{$email}.txt";
        
        if (file_exists($stored_code_file)) {
            $stored_code = trim(file_get_contents($stored_code_file));
            if ($code === $stored_code) {
                if (unsubscribeEmail($email)) {
                    // Clean up the verification file
                    unlink($stored_code_file);
                    // Redirect to index.php with success message
                    header("Location: index.php?message=unsubscribed");
                    exit();
                } else {
                    $message = "Unsubscription failed or email not found.";
                }
            } else {
                $message = "Invalid verification code. Please check and try again.";
            }
        } else {
            $message = "No verification code found for this email. Please request a new code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unsubscribe from XKCD Comics</title>
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
        <h2>Unsubscribe</h2>
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'sent') !== false ? 'success' : 'error'; ?> alert">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>
        
        <form method="POST">
            <label for="unsubscribe_email">Email:</label>
            <input type="email" name="unsubscribe_email" id="unsubscribe_email" required>
            <button id="submit-unsubscribe">Unsubscribe</button>
        </form>
        
        <br>
        <h3>Enter Confirmation Code</h3>
        <form method="POST">
            <label for="verify_unsubscribe_email">Email:</label>
            <input type="email" name="unsubscribe_email" id="verify_unsubscribe_email" required>
            <label for="verification_code">Verification Code:</label>
            <input type="text" name="verification_code" id="verification_code" maxlength="6" required>
            <button id="submit-verification">Verify</button>
        </form>
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