<?php
$dataFile = '/data/attempts.json';

if (file_exists($dataFile)) {
    $raw = file_get_contents($dataFile);
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];
} else {
    $data = [];
}

/* -------------------------------------------
   Add timestamps if missing + sort by recent
--------------------------------------------*/
foreach ($data as $email => $row) {
    // Backward compatibility: add missing time
    if (!isset($data[$email]['time'])) {
        $data[$email]['time'] = date('Y-m-d H:i:s');
    }
}

// Sort by newest timestamp
uasort($data, function($a, $b) {
    return strtotime($b['time']) <=> strtotime($a['time']);
});
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
.clear-btn {
    display: inline-block;
    margin-bottom: 12px;
    padding: 8px 14px;
    background: #c62828;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
}
.clear-btn:hover {
    background: #a72222;
}
</style>
</head>
<body>

<h2>Login Attempt Dashboard</h2>

<a href="clear-log.php" class="clear-btn"
   onclick="return confirm('Are you sure you want to CLEAR all log records?');">
   Clear Logs
</a>

<table>
<tr>
    <th>Email</th>
    <th>Names Tried</th>
    <th>Total Attempts</th>
    <th>IP Address</th>
    <th>Location</th>
    <th>Last Updated</th>
</tr>

<?php if (empty($data)): ?>
<tr>
    <td colspan="6" style="text-align:center; padding:20px; color:#666;">
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
    <td><?= htmlspecialchars($row["time"]) ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
