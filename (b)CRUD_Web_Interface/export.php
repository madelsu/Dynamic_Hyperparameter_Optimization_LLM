<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'SimpleXLSXGen.php';

$message = "";

// Get table columns
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}
$res->free();

// Exclude auto-increment ID if desired
$exportCols = array_filter($cols, fn($c) => $c !== 'id');

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $limit = (int)($_POST['limit'] ?? 100); // default limit 100
    $sql = "SELECT * FROM icsr_assessment_import ORDER BY id DESC LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($rows)) {
        $data = [];
        $data[] = $exportCols; // headers

        foreach ($rows as $row) {
            $line = [];
            foreach ($exportCols as $col) {
                $line[] = $row[$col];
            }
            $data[] = $line;
        }

        $xlsx = new \Shuchkin\SimpleXLSXGen();
        $xlsx->addSheet($data, 'ICSR Records');
        $xlsx->downloadAs("icsr_export_" . date('Y-m-d_H-i-s') . ".xlsx");
        exit;
    } else {
        $message = "❌ No records found to export.";
    }
}

function pretty_label($s) {
    return ucwords(str_replace('_', ' ', $s));
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Export Records to Excel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container my-4">
  <h3>Export Records to Excel</h3>

  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Number of records to export:</label>
      <input type="number" name="limit" class="form-control" value="100" min="1" max="10000">
    </div>
    <button type="submit" name="export_excel" class="btn btn-success">Export to Excel</button>
    <a href="index.php" class="btn btn-secondary">⬅ Back to Records</a>
  </form>

  <div class="mt-4">
    <p><strong>Note:</strong> Only the latest records (ordered by ID descending) will be exported. Adjust the limit as needed.</p>
  </div>
</div>
</body>
</html>
