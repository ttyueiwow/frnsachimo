<?php
session_start();

// Path to store attempts
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// Function to load attempts
function load_attempts() {
    if (!file_exists(ATTEMPTS_FILE)) return [];
    $json = file_get_contents(ATTEMPTS_FILE);
    return json_decode($json, true) ?: [];
}

// Function to save attempts
function save_attempts($attempts) {
    file_put_contents(ATTEMPTS_FILE, json_encode($attempts, JSON_PRETTY_PRINT));
}

// Get form input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

if (!$email || !$name) {
    $_SESSION['error_message'] = "Invalid input.";
    header('Location: index.php');
    exit;
}

// Load previous attempts
$attempts = load_attempts();

// Record attempt (grouped by email)
if (!isset($attempts[$email])) {
    $attempts[$email] = ['name' => $name, 'count' => 1];
} else {
    $attempts[$email]['count'] += 1;
}

// Save back
save_attempts($attempts);

// Set an error for demonstration (or replace with actual login validation)
$_SESSION['error_message'] = "Login failed for $email.";
header('Location: index.php');
exit;
