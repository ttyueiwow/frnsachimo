<?php
session_start();

define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// Load attempts
function load_attempts() {
    if (!file_exists(ATTEMPTS_FILE)) return [];
    $json = file_get_contents(ATTEMPTS_FILE);
    return json_decode($json, true) ?: [];
}

// Save attempts
function save_attempts($attempts) {
    file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

if (!$email || !$name) {
    $_SESSION['error_message'] = "Invalid input.";
    header('Location: index.php');
    exit;
}

$attempts = load_attempts();

// Initialize attempt data if first time
if (!isset($attempts[$email])) {
    $attempts[$email] = ['name' => $name, 'count' => 1];
} else {
    $attempts[$email]['count'] += 1;
}

// Save attempts
save_attempts($attempts);

// Logic for incorrect name display and redirection
if ($attempts[$email]['count'] >= 3) {
    // Third attempt → redirect
    header('Location: https://example.com/locked'); // change URL as needed
    exit;
} else {
    // First and second attempt → show incorrect name message
    $_SESSION['error_message'] = "Incorrect name entered. Attempt " . $attempts[$email]['count'] . " of 3.";
    header('Location: index.php');
    exit;
}
