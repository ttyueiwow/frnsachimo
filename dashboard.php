<?php
$data = file_exists("attempts.json")
    ? json_decode(file_get_contents("attempts.json"), true)
    : [];
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
    <th>Attempts</th>
    <th>IP Address</th>
    <th>Region</th>
    <th>Time</th>
</tr>

<?php foreach ($data as $row): ?>
<tr>
    <td><?= htmlspecialchars($row["email"]) ?></td>
    <td><?= htmlspecialchars(implode(", ", $row["names"])) ?></td>
    <td><?= htmlspecialchars($row["attempt"]) ?></td>
    <td><?= htmlspecialchars($row["ip"]) ?></td>
    <td><?= htmlspecialchars($row["region"]) ?></td>
    <td><?= htmlspecialchars($row["time"]) ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
