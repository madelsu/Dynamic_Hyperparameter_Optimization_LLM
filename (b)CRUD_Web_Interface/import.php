<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

$message = "";

// Get table columns
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}
$res->free();

// Exclude auto-increment ID
$insertCols = array_filter($cols, fn($c) => $c !== 'id');

// Handle Excel import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel'])) {
    if (!empty($_FILES['excel_file']['tmp_name'])) {
        $filePath = $_FILES['excel_file']['tmp_name'];

        if ($xlsx = SimpleXLSX::parse($filePath)) {
            $rows = $xlsx->rows();
            $headers = array_map('trim', $rows[0]);

            $inserted = 0;
            $errors = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $values = [];
                foreach ($insertCols as $col) {
                    $index = array_search($col, $headers);
                    $values[] = ($index !== false) ? $row[$index] : null;
                }

                $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
                $columns = implode(',', array_map(fn($c) => "`$c`", $insertCols));
                $sql = "INSERT INTO icsr_assessment_import ($columns) VALUES ($placeholders)";
                $stmt = $mysqli->prepare($sql);
                if ($stmt) {
                    $types = str_repeat('s', count($values));
                    $stmt->bind_param($types, ...$values);
                    if ($stmt->execute()) {
                        $inserted++;
                    } else {
                        $errors++;
                    }
                    $stmt->close();
                } else {
                    $errors++;
                }
            }

            $message = "✅ Imported $inserted rows. ❌ $errors errors.";
        } else {
            $message = "❌ Error reading Excel file: " . SimpleXLSX::parseError();
        }
    } else {
        $message = "❌ No file uploaded.";
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
  <title>Import Records from Excel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container my-4">
  <h3>Import Records from Excel</h3>

  <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Select Excel file (.xlsx)</label>
      <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
    </div>
    <button type="submit" name="import_excel" class="btn btn-primary">Import</button>
    <a href="index.php" class="btn btn-secondary">⬅ Back to Records</a>
  </form>

  <div class="mt-4">
    <p><strong>Note:</strong> Excel headers must match database column names exactly (case-insensitive). ID column is auto-increment and will be ignored.</p>
  </div>
</div>
</body>
</html>
