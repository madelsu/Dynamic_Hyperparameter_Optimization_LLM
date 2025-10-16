<?php
// create_admin.php  (run once, then delete)
require_once 'db.php';

$admin_user = 'admin';
$admin_pass = 'admin'; // replace here if you want a different password before running

// Check if user exists
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $admin_user);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Admin user already exists. Delete this file after use.";
    $stmt->close();
    exit;
}
$stmt->close();

$hash = password_hash($admin_pass, PASSWORD_BCRYPT);
$stmt = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $admin_user, $hash);
if ($stmt->execute()) {
    echo "Admin user created. Username: " . htmlspecialchars($admin_user) . "<br>";
    echo "Now delete create_admin.php for security.";
} else {
    echo "Error creating admin: " . htmlspecialchars($mysqli->error);
}
$stmt->close();
