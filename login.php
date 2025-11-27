<?php
session_start();

// Path to attempts file
define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

// Ensure attempts.json exists and is valid
if (!file_exists(ATTEMPTS_FILE)) {
    file_put_contents(ATTEMPTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}

$attempts_data = json_decode(file_get_contents(ATTEMPTS_FILE), true);
if (!is_array($attempts_data)) $attempts_data = [];

// Sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$name  = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$email || !$name) {
    $_SESSION['error_message'] = "Invalid input provided.";
    header("Location: index.php");
    exit;
}

// Initialize data for new email
if (!isset($attempts_data[$email])) {
    $attempts_data[$email] = [
        "names" => [$name],   // array of attempted names
        "count" => 1
    ];
} else {
    $attempts_data[$email]["names"][] = $name;
    $attempts_data[$email]["count"] += 1;
}

// Save updated attempts
file_put_contents(ATTEMPTS_FILE, json_encode($attempts_data, JSON_PRETTY_PRINT));

// Determine attempt number
$attempt_number = $attempts_data[$email]["count"];

// Replace with the *correct name*
$correct_name = "John Doe";

// Logic for incorrect attempts
if ($name !== $correct_name && $attempt_number < 3) {
    $_SESSION['error_message'] = "Incorrect name: $name";
    header("Location: index.php");
    exit;
}

if ($name !== $correct_name && $attempt_number >= 3) {
    header("Location: https://example.com/blocked"); // change URL
    exit;
}

// Success: correct name entered
header("Location: dashboard.php");
exit;
