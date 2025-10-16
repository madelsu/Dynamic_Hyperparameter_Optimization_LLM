<?php
require_once 'auth.php';
require_once 'db.php';

// Get table columns
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}
$res->free();

// Exclude auto-increment ID
$insertCols = array_filter($cols, fn($c) => $c !== 'id');

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placeholders = implode(",", array_fill(0, count($insertCols), "?"));
    $columns = implode(",", array_map(fn($c) => "`$c`", $insertCols));

    $sql = "INSERT INTO icsr_assessment_import ($columns) VALUES ($placeholders)";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("SQL Prepare Failed: " . $mysqli->error);
    }

    // Collect values in correct order
    $values = [];
    foreach ($insertCols as $c) {
        $values[] = $_POST[$c] ?? null;
    }

    // All params as strings
    $types = str_repeat("s", count($values));
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        $message = "✅ Record added successfully!";
    } else {
        $message = "❌ Insert failed: " . $stmt->error;
    }
    $stmt->close();
}

function pretty_label($s) {
    return ucwords(str_replace('_', ' ', $s));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add New Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container my-4">
  <h3>Add New Record</h3>
  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3">
    <?php foreach ($insertCols as $c): ?>
      <div class="col-md-6">
        <label class="form-label"><?= htmlspecialchars(pretty_label($c)) ?></label>
        <?php if ($c === 'case_narrative'): ?>
          <textarea class="form-control" name="<?= htmlspecialchars($c) ?>" rows="5"><?= htmlspecialchars($_POST[$c] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" class="form-control" name="<?= htmlspecialchars($c) ?>" value="<?= htmlspecialchars($_POST[$c] ?? '') ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="col-12">
      <button class="btn btn-success" type="submit">➕ Save Record</button>
      <a class="btn btn-secondary" href="index.php">⬅ Back to Records</a>
    </div>
  </form>
</div>
</body>
</html>
