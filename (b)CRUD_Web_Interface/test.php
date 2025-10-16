<?php
require_once 'db.php';

echo "<h3>Testing users table...</h3>";

$res = $mysqli->query("SELECT username, password FROM users");
if (!$res) {
    echo "Error querying users: " . $mysqli->error;
    exit;
}

if ($res->num_rows === 0) {
    echo "❌ No users found in users table.";
} else {
    echo "✅ Found users:<br><pre>";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}
?>
