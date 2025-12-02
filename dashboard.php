<?php
$dataFile = '/data/attempts.json';

if (file_exists($dataFile)) {
    $raw = file_get_contents($dataFile);
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];
} else {
    $data = [];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login Attempt Dashboard</title>
<style>
body { font-family: Arial; padding: 20px; background: #f7f7f7; }
table { border-collapse: collapse; width: 100%; background: #fff; }
th { background: #333; color: #fff; }
td, th { padding: 10px; border: 1px solid #ccc; font-size: 14px; }
tr:nth-child(even) { background: #f2f2f2; }
</style>
</head>
<body>

<h2>Login Attempt Dashboard</h2>

<table>
<tr>
    <th>Email</th>
    <th>Names Tried</th>
    <th>Total Attempts</th>
    <th>IP Address</th>
    <th>Location</th>
</tr>

<?php if (empty($data)): ?>
<tr>
    <td colspan="5" style="text-align:center; padding:20px; color:#666;">
        No attempts logged yet.
    </td>
</tr>
<?php endif; ?>

<?php foreach ($data as $email => $row): ?>
<tr>
    <td><?= htmlspecialchars($email) ?></td>
    <td><?= htmlspecialchars(implode(", ", $row["names"])) ?></td>
    <td><?= htmlspecialchars($row["count"]) ?></td>
    <td><?= htmlspecialchars($row["ip"]) ?></td>
    <td><?= htmlspecialchars($row["location"]) ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
