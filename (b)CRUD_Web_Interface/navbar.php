<?php
// navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">ICSR CRUD</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php">Records</a></li>
      </ul>
      <span class="navbar-text text-light">
        <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
      </span>
      <a class="btn btn-outline-light ms-2 btn-sm" href="logout.php">Logout</a>
    </div>
  </div>
</nav>
