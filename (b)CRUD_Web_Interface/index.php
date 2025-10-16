<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'SimpleXLSX.php';      // for import
require_once 'SimpleXLSXGen.php';   // for export

use Shuchkin\SimpleXLSX;

// Get table columns
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) {
    $cols[] = $r['Field'];
}
$res->free();

// Columns for add/import (exclude auto-increment ID)
$insertCols = array_filter($cols, fn($c) => $c !== 'id');

// Columns for table view (exclude case_narrative)
$show_in_index = array_filter($cols, fn($c) => $c !== 'case_narrative');

$message = "";

// ---------- HANDLE ADD RECORD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $placeholders = implode(",", array_fill(0, count($insertCols), "?"));
    $columns = implode(",", array_map(fn($c) => "`$c`", $insertCols));

    $sql = "INSERT INTO icsr_assessment_import ($columns) VALUES ($placeholders)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) die("SQL Prepare Failed: " . $mysqli->error);

    $values = [];
    foreach ($insertCols as $c) $values[] = $_POST[$c] ?? null;

    $types = str_repeat("s", count($values));
    $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) $message = "✅ Record added successfully!";
    else $message = "❌ Insert failed: " . $stmt->error;
    $stmt->close();
}

// ---------- HANDLE IMPORT ----------
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
                    if ($stmt->execute()) $inserted++;
                    else $errors++;
                    $stmt->close();
                } else $errors++;
            }

            $message = "✅ Imported $inserted rows. ❌ $errors errors.";
        } else {
            $message = "❌ Error reading Excel file: " . SimpleXLSX::parseError();
        }
    } else $message = "❌ No file uploaded.";
}

// ---------- HANDLE EXPORT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_excel'])) {
    $limit = (int)($_POST['limit'] ?? 100);
    $sql = "SELECT * FROM icsr_assessment_import ORDER BY id DESC LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($rows)) {
        $data = [];
        $data[] = $insertCols; // headers
        foreach ($rows as $row) {
            $line = [];
            foreach ($insertCols as $col) $line[] = $row[$col];
            $data[] = $line;
        }
        $xlsx = new \Shuchkin\SimpleXLSXGen();
        $xlsx->addSheet($data, 'ICSR Records');
        $xlsx->downloadAs("icsr_export_" . date('Y-m-d_H-i-s') . ".xlsx");
        exit;
    } else $message = "❌ No records found to export.";
}

// ---------- FETCH RECORDS FOR TABLE (APPLY FILTER IF SEARCH) ----------
$whereParts = [];
$params = [];
$types = "";
foreach ($cols as $c) {
    if (!empty($_GET[$c])) {
        $like = "%" . trim($_GET[$c]) . "%";
        $whereParts[] = "`$c` LIKE ?";
        $params[] = $like;
        $types .= "s";
    }
}
$where = !empty($whereParts) ? "WHERE " . implode(" AND ", $whereParts) : "";

$sql = "SELECT * FROM icsr_assessment_import $where ORDER BY id DESC";
$stmt = $mysqli->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function pretty_label($s) {
    return ucwords(str_replace('_', ' ', $s));
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>ICSR Records</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.wrap-cell { max-width: 300px; white-space: normal; word-wrap: break-word; }
table { font-size: 0.85rem; }
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container-fluid my-4">

<h3>ICSR Records Management</h3>

<?php if ($message): ?>
<div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs" id="recordTabs" role="tablist">
  <li class="nav-item">
    <button class="nav-link active" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button">Records</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button">Search</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button">Add Record</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="import-tab" data-bs-toggle="tab" data-bs-target="#import" type="button">Import Excel</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" id="export-tab" data-bs-toggle="tab" data-bs-target="#export" type="button">Export Excel</button>
  </li>
</ul>

<div class="tab-content mt-3">

  <!-- Records Table -->
  <div class="tab-pane fade show active" id="records">
    <div class="table-responsive" style="max-height:65vh; overflow:auto;">
      <table class="table table-bordered table-sm table-striped">
        <thead class="table-dark">
          <tr>
            <?php foreach($show_in_index as $c): ?>
              <th><?= htmlspecialchars(pretty_label($c)) ?></th>
            <?php endforeach; ?>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($show_in_index)+1 ?>" class="text-center">No records found</td></tr>
          <?php else: foreach($rows as $r): ?>
            <tr>
              <?php foreach($show_in_index as $c): ?>
                <td class="wrap-cell"><?= nl2br(htmlspecialchars($r[$c])) ?></td>
              <?php endforeach; ?>
              <td style="white-space:nowrap">
                <a class="btn btn-sm btn-primary" href="view.php?id=<?= urlencode($r['id']) ?>">View</a>
                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= urlencode($r['id']) ?>">Edit</a>
                <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete record <?= htmlspecialchars($r['id']) ?>?');">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Search Tab -->
  <div class="tab-pane fade" id="search">
    <form class="row g-2 mb-3" method="get" action="index.php">
      <?php foreach ($cols as $c): ?>
        <div class="col-md-3 filter-box">
          <label class="form-label"><?= htmlspecialchars(pretty_label($c)) ?></label>
          <input type="text" class="form-control form-control-sm" 
                 name="<?= htmlspecialchars($c) ?>" 
                 value="<?= htmlspecialchars($_GET[$c] ?? '') ?>">
        </div>
      <?php endforeach; ?>
      <div class="col-12">
        <button class="btn btn-primary btn-sm" type="submit">Search</button>
        <a class="btn btn-secondary btn-sm" href="index.php">Reset</a>
      </div>
    </form>
  </div>

  <!-- Add Record -->
  <div class="tab-pane fade" id="add">
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
        <button class="btn btn-success" name="add_record" type="submit">➕ Save Record</button>
      </div>
    </form>
  </div>

  <!-- Import Excel -->
  <div class="tab-pane fade" id="import">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Select Excel file (.xlsx)</label>
        <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
      </div>
      <button type="submit" name="import_excel" class="btn btn-primary">Import</button>
    </form>
  </div>

  <!-- Export Excel -->
  <div class="tab-pane fade" id="export">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Number of records to export:</label>
        <input type="number" name="limit" class="form-control" value="100" min="1" max="10000">
      </div>
      <button type="submit" name="export_excel" class="btn btn-success">Export to Excel</button>
    </form>
  </div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
