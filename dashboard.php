<?php
session_start();

define('ATTEMPTS_FILE', __DIR__ . '/attempts.json');

$attempts = [];
if (file_exists(ATTEMPTS_FILE)) {
    $data = json_decode(file_get_contents(ATTEMPTS_FILE), true);
    if (is_array($data)) $attempts = $data;
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
        <th>Attempted Names</th>
        <th>Attempts Count</th>
    </tr>
    <?php if (!empty($attempts)): ?>
        <?php foreach ($attempts as $email => $data): ?>
            <tr>
                <td><?= htmlspecialchars($email) ?></td>
                <td><?= htmlspecialchars(implode(", ", $data['names'])) ?></td>
                <td><?= $data['count'] ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="3">No attempts recorded yet.</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
