<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

$stmt = $mysqli->prepare("DELETE FROM icsr_assessment_import WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
header("Location: index.php");
exit;
