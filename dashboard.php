<?php
session_start();

// OPTIONAL: Add a password to view dashboard
$admin_pass = 'secret123';
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Dashboard"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
} elseif ($_SERVER['PHP_AUTH_PW'] !== $admin_pass) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Forbidden';
    exit;
}

define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

$attempts = [];
if (file_exists(ATTEMPTS_FILE)) {
    $attempts = json_decode(file_get_contents(ATTEMPTS_FILE), true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Login Attempts</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Login Attempts Dashboard</h1>
    <table>
        <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Attempts</th>
        </tr>
        <?php foreach ($attempts as $email => $data): ?>
            <tr>
                <td><?= htmlspecialchars($email) ?></td>
                <td><?= htmlspecialchars($data['name']) ?></td>
                <td><?= $data['count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
