<?php
require_once 'auth.php';
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

// get columns (exclude id)
$cols = [];
$res = $mysqli->query("SHOW COLUMNS FROM icsr_assessment_import");
while ($r = $res->fetch_assoc()) {
    if (strtolower($r['Field']) === 'id') continue;
    $cols[] = $r;
}
$res->free();

// fetch existing row
$stmt = $mysqli->prepare("SELECT * FROM icsr_assessment_import WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
if (!$row) { header("Location: index.php"); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    foreach ($cols as $c) {
        $name = $c['Field'];
        $values[$name] = isset($_POST[$name]) ? $_POST[$name] : null;
    }

    // basic validation
    if (empty($values['case_id'] ?? '')) $errors[] = 'case_id required';
    if (empty($values['pt'] ?? '')) $errors[] = 'pt required';
    if (empty($values['drug_name'] ?? '')) $errors[] = 'drug_name required';

    if (empty($errors)) {
        $setparts = [];
        $types = '';
        $bind_values = [];

        foreach ($values as $n => $v) {
            // Escape column names to handle reserved keywords like "case"
            $setparts[] = "`$n` = ?";

            $colmeta = array_values(array_filter($cols, function($cm) use ($n){ return $cm['Field'] === $n; }))[0];
            $type = (stripos($colmeta['Type'], 'int') !== false || stripos($colmeta['Type'], 'decimal') !== false) ? 'i' : 's';
            $types .= $type;
            $bind_values[] = $v;
        }

        $sql = "UPDATE icsr_assessment_import SET " . implode(',', $setparts) . " WHERE id = ?";
        $stmt = $mysqli->prepare($sql);

        // bind params (types + values + id)
        $types_with_id = $types . 'i';
        $refs = [];
        $refs[] = & $types_with_id;
        foreach ($bind_values as $k => $v) $refs[] = & $bind_values[$k];
        $refs[] = & $id;
        call_user_func_array([$stmt, 'bind_param'], $refs);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: view.php?id=" . urlencode($id));
            exit;
        } else {
            $errors[] = "Update error: " . $mysqli->error;
        }
        $stmt->close();
    }
}

function pretty_label($s){ return ucwords(str_replace('_',' ',$s)); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Record <?= htmlspecialchars($id) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3">
  <h3>Edit Record #<?= htmlspecialchars($id) ?></h3>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul>
        <?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <?php foreach ($cols as $c):
      $name = $c['Field'];
      $is_textarea = stripos($c['Type'],'text') !== false || $name === 'case_narrative';
      $value = $_POST[$name] ?? $row[$name] ?? '';
    ?>
      <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(pretty_label($name)) ?></label>
        <?php if ($is_textarea): ?>
          <textarea name="<?= htmlspecialchars($name) ?>" class="form-control" rows="6"><?= htmlspecialchars($value) ?></textarea>
        <?php else: ?>
          <input name="<?= htmlspecialchars($name) ?>" class="form-control" value="<?= htmlspecialchars($value) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-secondary" href="view.php?id=<?= urlencode($id) ?>">Cancel</a>
  </form>
</div>
</body>
</html>
