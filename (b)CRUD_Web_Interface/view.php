<?php
require_once 'auth.php';
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

// fetch row
$stmt = $mysqli->prepare("SELECT * FROM icsr_assessment_import WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
if (!$row) { header("Location: index.php"); exit; }

// get columns
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
$res->free();

function pretty_label($s) {
    $s = str_replace('_', ' ', $s);
    return ucwords($s);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>View Record <?= htmlspecialchars($row['id']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Record #<?= htmlspecialchars($row['id']) ?></h3>
    <div>
      <a href="edit.php?id=<?= urlencode($row['id']) ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
      <a href="index.php" class="btn btn-secondary btn-sm">Back to list</a>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <?php foreach ($cols as $c): ?>
        <div class="mb-3">
          <strong><?= htmlspecialchars(pretty_label($c)) ?>:</strong>
          <?php if ($c === 'case_narrative'): ?>
            <pre style="white-space:pre-wrap; background:#f8f9fa; padding:12px; border-radius:6px;"><?= htmlspecialchars($row[$c]) ?></pre>
          <?php else: ?>
            <div><?= nl2br(htmlspecialchars($row[$c])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</body>
</html>
