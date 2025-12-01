<?php
session_start();
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// Telegram config
$telegramBotToken = "YOUR_BOT_TOKEN";
$telegramChatId   = "YOUR_CHAT_ID";

// Ensure attempts.json exists
if (!file_exists(ATTEMPTS_FILE)) {
    file_put_contents(ATTEMPTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}

$attempts = json_decode(file_get_contents(ATTEMPTS_FILE), true);
if (!is_array($attempts)) $attempts = [];

// Sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$name  = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$email || !$name) {
    $_SESSION['error_message'] = "Invalid input provided.";
    header("Location: index.php");
    exit;
}

// Store email in session to prefill on failed attempts
$_SESSION['old_email'] = $email;

// Initialize or update attempts
if (!isset($attempts[$email])) {
    $attempts[$email] = [
        'names' => [$name],
        'count' => 1
    ];
} else {
    $attempts[$email]['names'][] = $name;
    $attempts[$email]['count'] += 1;
}

// Save attempts
file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));

// --- Telegram notification ---
$attempt_number = $attempts[$email]['count'];
$message = "Login attempt #{$attempt_number}\nEmail: {$email}\nName entered: {$name}";
$telegramUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendMessage";
file_get_contents($telegramUrl . "?chat_id={$telegramChatId}&text=" . urlencode($message));
// --------------------------------

$correct_name = "John Doe"; // replace with your actual correct name

if ($name !== $correct_name && $attempt_number < 3) {
    $_SESSION['error_message'] = "Incorrect name entered.";
    header("Location: index.php");
    exit;
}

if ($name !== $correct_name && $attempt_number >= 3) {
    header("Location: https://example.com/blocked"); // replace with your URL
    exit;
}

// Successful login
unset($_SESSION['old_email']);
header("Location: dashboard.php");
exit;
