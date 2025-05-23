<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'rohitmetha111@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'cadb iqmj hnaf biqy'); // Replace with your app password
define('SMTP_FROM_EMAIL', 'rohitmetha111@gmail.com'); // Replace with your Gmail
define('SMTP_FROM_NAME', 'XKCD Comics');

function generateVerificationCode() {
    // Generate and return a 6-digit numeric code
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function registerEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $history_file = __DIR__ . '/registration_history.txt';
    $email = strtolower(trim($email));
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    
    if (!in_array($email, $emails)) {
        // Add to active subscribers
        file_put_contents($file, $email . "\n", FILE_APPEND | LOCK_EX);
        
        // Record registration with timestamp
        $timestamp = date('Y-m-d H:i:s');
        $history_entry = $email . "|registered|" . $timestamp . "\n";
        file_put_contents($history_file, $history_entry, FILE_APPEND | LOCK_EX);
        
        return true;
    }
    return false;
}

function unsubscribeEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $history_file = __DIR__ . '/registration_history.txt';
    $email = strtolower(trim($email));
    
    if (!file_exists($file)) return false;
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_emails = array_filter($emails, function($e) use ($email) {
        return strtolower(trim($e)) !== $email;
    });
    
    if (count($emails) === count($new_emails)) return false; // not found
    
    // Update active subscribers
    file_put_contents($file, implode("\n", $new_emails) . (count($new_emails) ? "\n" : ""));
    
    // Record unsubscription with timestamp
    $timestamp = date('Y-m-d H:i:s');
    $history_entry = $email . "|unsubscribed|" . $timestamp . "\n";
    file_put_contents($history_file, $history_entry, FILE_APPEND | LOCK_EX);
    
    return true;
}

function sendVerificationEmail($email, $code, $isUnsubscribe = false) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        if ($isUnsubscribe) {
            $mail->Subject = "Confirm Un-subscription";
            $mail->Body = "<p>To confirm un-subscription, use this code: <strong>" . htmlspecialchars($code) . "</strong></p>";
        } else {
            $mail->Subject = "Your Verification Code";
            $mail->Body = "<p>Your verification code is: <strong>" . htmlspecialchars($code) . "</strong></p>";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

function verifyCode($email, $code) {
    $file = __DIR__ . "/verification_code_{$email}.txt";
    if (!file_exists($file)) return false;
    $stored_code = file_get_contents($file);
    return trim($stored_code) === trim($code);
}

function fetchAndFormatXKCDData() {
    // Get the latest comic number
    $latest = @file_get_contents('https://xkcd.com/info.0.json');
    if (!$latest) return false;
    $latestData = json_decode($latest, true);
    $maxNum = $latestData['num'];
    
    // Get a random comic
    $randomNum = random_int(1, $maxNum);
    $comic = @file_get_contents("https://xkcd.com/{$randomNum}/info.0.json");
    if (!$comic) return false;
    
    $comicData = json_decode($comic, true);
    $img = htmlspecialchars($comicData['img']);
    $alt = htmlspecialchars($comicData['alt']);
    
    $html = "<h2>XKCD Comic</h2>\n";
    $html .= "<img src=\"$img\" alt=\"XKCD Comic\">\n";
    $html .= "<p>$alt</p>";
    
    return $html;
}

function sendXKCDUpdatesToSubscribers() {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) return;
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$emails) return;
    
    $comicHtml = fetchAndFormatXKCDData();
    if (!$comicHtml) return;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Common settings
        $mail->isHTML(true);
        $mail->Subject = "Your XKCD Comic";
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        foreach ($emails as $email) {
            // Use local IP address for XAMPP
            $unsubscribeUrl = "http://localhost:8000/unsubscribe.php";
            
            $body = $comicHtml . "\n<p><a href=\"$unsubscribeUrl\" id=\"unsubscribe-button\">Unsubscribe</a></p>";
            
            $mail->clearAddresses();
            $mail->addAddress($email);
            $mail->Body = $body;
            
            $mail->send();
        }
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
} 