<?php
require_once 'auth.php';
require_once 'db.php';

// total records
$total = $mysqli->query("SELECT COUNT(*) AS c FROM icsr_assessment_import")->fetch_assoc()['c'];

// outcome breakdown
$outcome_data = [];
$res = $mysqli->query("SELECT outcome, COUNT(*) as c FROM icsr_assessment_import GROUP BY outcome ORDER BY c DESC");
while ($r = $res->fetch_assoc()) {
    $outcome_data[$r['outcome'] ?: 'Unknown'] = (int)$r['c'];
}
$res->free();

// top 10 PT terms
$pt_data = [];
$res = $mysqli->query("SELECT pt, COUNT(*) as c FROM icsr_assessment_import GROUP BY pt ORDER BY c DESC LIMIT 10");
while ($r = $res->fetch_assoc()) {
    $pt_data[$r['pt'] ?: 'Unknown'] = (int)$r['c'];
}
$res->free();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-3">
  <h4 class="mb-3">Dashboard</h4>

  <div class="row mb-3">
    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <h5>Total Records</h5>
          <p class="display-6"><?= $total ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body">
          <h5>Outcome Distribution</h5>
          <canvas id="outcomeChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body">
          <h5>Top 10 PT Terms</h5>
          <canvas id="ptChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const outcomeCtx = document.getElementById('outcomeChart').getContext('2d');
new Chart(outcomeCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($outcome_data)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($outcome_data)) ?>,
            backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6c757d','#17a2b8','#6610f2']
        }]
    }
});

const ptCtx = document.getElementById('ptChart').getContext('2d');
new Chart(ptCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($pt_data)) ?>,
        datasets: [{
            label: 'Frequency',
            data: <?= json_encode(array_values($pt_data)) ?>,
            backgroundColor: '#007bff'
        }]
    },
    options: {
        indexAxis: 'y',
        scales: {
            x: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
