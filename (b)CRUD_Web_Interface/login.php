<?php
// login.php
require_once 'db.php';
session_start();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = "Please provide username and password.";
    } else {
        $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($uid, $hash);
        if ($stmt->fetch()) {
            if (password_verify($password, $hash)) {
                // success
                $_SESSION['user_id'] = $uid;
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit;
            } else {
                $err = "Invalid credentials.";
            }
        } else {
            $err = "Invalid credentials.";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">Login</h3>
          <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input name="password" type="password" class="form-control">
            </div>
            <button class="btn btn-primary" type="submit">Login</button>
          </form>
        </div>
      </div>
      <p class="mt-3 text-muted">Use the admin account created with create_admin.php</p>
    </div>
  </div>
</div>
</body>
</html>
