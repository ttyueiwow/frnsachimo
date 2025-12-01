<?php
session_start();

define("ATTEMPTS_FILE", __DIR__ . "/attempts.json");
define("BANNED_FILE", __DIR__ . "/banned.json");
define("WHITELIST_FILE", __DIR__ . "/papa.txt");

// Telegram
$telegram_bot = "7657571386:AAHH3XWbHBENZBzBul6cfevzAoIiftu-TVQ";
$telegram_chat = "6915371044";

// Turnstile secret
$turnstile_secret = "0x4AAAAAACEAdSoSffFlw4Y93xBl0UFbgsc";

// Ensure files exist
if (!file_exists(ATTEMPTS_FILE)) file_put_contents(ATTEMPTS_FILE, "{}");
if (!file_exists(BANNED_FILE))    file_put_contents(BANNED_FILE, "{}");

// Load JSON
$attempts = json_decode(file_get_contents(ATTEMPTS_FILE), true);
$banned   = json_decode(file_get_contents(BANNED_FILE), true);

// Get IP
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
      $_SERVER['REMOTE_ADDR'] ??
      'UNKNOWN';

// ---- Soft Ban Logic ----
if (isset($banned[$ip])) {
    if (time() < $banned[$ip]['expire']) {
        header("Location: https://example.com/blocked");
        exit;
    } else {
        unset($banned[$ip]);
        file_put_contents(BANNED_FILE, json_encode($banned, JSON_PRETTY_PRINT));
    }
}

// --- Turnstile Validation ---
$token = $_POST["cf-turnstile-response"] ?? "";

$verify = curl_init();
curl_setopt($verify, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
curl_setopt($verify, CURLOPT_POST, true);
curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
    "secret" => $turnstile_secret,
    "response" => $token,
    "remoteip" => $ip
]));
$response = json_decode(curl_exec($verify), true);

if (empty($response["success"])) {
    $_SESSION["error_message"] = "Security validation failed.";
    header("Location: index.php");
    exit;
}

// --- Email Whitelist ---
$email = strtolower(trim($_POST["email"] ?? ""));
$allowed = array_map("trim", file(WHITELIST_FILE));

if (!in_array($email, $allowed)) {
    $_SESSION["error_message"] = "Email not recognized.";
    header("Location: index.php");
    exit;
}

// Sanitize name
$name = trim($_POST["name"] ?? "");

// Save previous email
$_SESSION["old_email"] = $email;

// Initialize or update attempts
if (!isset($attempts[$email])) {
    $attempts[$email] = [
        "count" => 0,
        "names" => [],
        "ip" => $ip,
        "history" => [],
    ];
}

$attempts[$email]["count"]++;
$attempts[$email]["names"][] = $name;
$attempts[$email]["history"][] = [
    "ip" => $ip,
    "name" => $name,
    "time" => date("Y-m-d H:i:s")
];

$count = $attempts[$email]["count"];

// ---- IP Region Lookup ----
$geo = json_decode(@file_get_contents("http://ip-api.com/json/$ip"), true);
$country = $geo["country"] ?? "Unknown";
$city = $geo["city"] ?? "Unknown";

// ---- Abuse Score (Soft Ban Trigger) ----
$abuse = 0;
if ($count > 5) $abuse += 2;
if ($response["score"] ?? 1 < 0.5) $abuse += 2;
if ($ip !== ($attempts[$email]["ip"] ?? $ip)) $abuse += 1;

if ($abuse >= 3) {
    // Soft ban for 30 minutes
    $banned[$ip] = [
        "reason" => "Suspicious activity",
        "expire" => time() + 1800
    ];
    file_put_contents(BANNED_FILE, json_encode($banned, JSON_PRETTY_PRINT));
}

// --- Telegram notification ---
$message  = "Login attempt:\n";
$message .= "Email: $email\n";
$message .= "Names tried: " . implode(", ", $attempts[$email]["names"]) . "\n";
$message .= "Attempts: $count\n";
$message .= "IP: $ip\n";
$message .= "Location: $city, $country\n";
$message .= "Abuse Score: $abuse\n";

file_get_contents("https://api.telegram.org/bot$telegram_bot/sendMessage?chat_id=$telegram_chat&text=" . urlencode($message));

// Save attempts
file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));

// ---- Compare name ----
$correct_name = "John Doe"; // your real correct value

if ($name !== $correct_name) {
    $_SESSION["error_message"] = "Incorrect name.";
    header("Location: index.php");
    exit;
}

// Success
unset($_SESSION['old_email']);
header("Location: dashboard.php");
exit;
