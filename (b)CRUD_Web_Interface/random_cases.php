<?php
// File: random_cases.php
// Read-only JSON API: return N random cases where ALL key columns are filled.

require_once __DIR__ . '/db.php';  // db.php is in the same folder
header('Content-Type: application/json; charset=UTF-8');

// --- Token protection (pass ?token=YOUR_TOKEN) ---
$EXPECTED = 'CHANGE_ME_TOKEN';     // ← set your secret and use the same in Python
$token = $_GET['token'] ?? '';
if ($token !== $EXPECTED) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- How many to return (safe cap 10) ---
$limit = isset($_GET['limit']) ? max(1, min(10, intval($_GET['limit']))) : 2;

// 1) Read actual columns from the table (to fail early if names differ)
$table = 'icsr_assessment_import';
$existing = [];
$res = $mysqli->query("SHOW COLUMNS FROM `$table`");
if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read table columns', 'mysqli_error' => $mysqli->error]);
    exit;
}
while ($r = $res->fetch_assoc()) {
    $existing[strtolower($r['Field'])] = $r['Field']; // preserve real case
}
$res->free();

// 2) Define the exact set of required columns based on your CRUD UI
$required = [
    'id', 'case', 'case_id', 'pt', 'drug_name',
    // Q1..Q10 scores + reasonings
];
for ($i = 1; $i <= 10; $i++) {
    $required[] = "q{$i}_score";
    $required[] = "q{$i}_reasoning";
}
$required = array_merge($required, ['final_score', 'outcome', 'description', 'case_narrative']);

// 3) Verify they exist; if any are missing, return a helpful error
$missing = [];
foreach ($required as $col) {
    if (!isset($existing[strtolower($col)])) {
        $missing[] = $col;
    }
}
if (!empty($missing)) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Some expected columns were not found in the table.',
        'missing' => $missing,
        'hint'    => 'Adjust this list to match your exact schema or rename columns in the DB.'
    ]);
    exit;
}

// 4) Build SELECT (alias reserved word `case` → `case_label`)
$selects = [];
$selects[] = "`{$existing['id']}` AS id";
$selects[] = "`{$existing['case']}` AS case_label";
$selects[] = "`{$existing['case_id']}` AS case_id";
$selects[] = "`{$existing['pt']}` AS pt";
$selects[] = "`{$existing['drug_name']}` AS drug_name";

for ($i = 1; $i <= 10; $i++) {
    $selects[] = "`{$existing["q{$i}_score"]}` AS q{$i}_score";
    $selects[] = "`{$existing["q{$i}_reasoning"]}` AS q{$i}_reasoning";
}
$selects[] = "`{$existing['final_score']}` AS final_score";
$selects[] = "`{$existing['outcome']}` AS outcome";
$selects[] = "`{$existing['description']}` AS description";
$selects[] = "`{$existing['case_narrative']}` AS case_narrative";

// 5) WHERE: every required column must be non-NULL and non-empty after TRIM()
//    (works for text; for numeric cols, empty strings are rare, but harmless here)
$whereParts = [];
foreach ($required as $col) {
    $real = $existing[strtolower($col)];
    $whereParts[] = "`$real` IS NOT NULL AND TRIM(`$real`) <> ''";
}
$where = implode(" AND ", $whereParts);

// 6) Final query
$sql = "SELECT " . implode(", ", $selects) . "
        FROM `$table`
        WHERE $where
        ORDER BY RAND()
        LIMIT ?";

try {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $limit);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    echo json_encode(['count' => count($rows), 'rows' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Query failed',
        'message' => $e->getMessage(),
        'sql' => $sql
    ], JSON_UNESCAPED_UNICODE);
}

