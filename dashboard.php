<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$totalWords      = (int) $pdo->query("SELECT COUNT(*) FROM words")->fetchColumn();
$totalCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalDialects   = (int) $pdo->query("SELECT COUNT(*) FROM dialects")->fetchColumn();
$activeDialects  = (int) $pdo->query("SELECT COUNT(*) FROM dialects WHERE status='Active'")->fetchColumn();
$avgRating       = (float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM feedback")->fetchColumn();

$ratingLabelMap = [1 => 'Very Dissatisfied', 2 => 'Dissatisfied', 3 => 'Neutral', 4 => 'Satisfied', 5 => 'Very Satisfied'];
$ratingLabel = $ratingLabelMap[(int) round($avgRating)] ?? 'No Ratings Yet';

// Words per category (bar chart data)
$catStmt = $pdo->query("
    SELECT c.name, COUNT(w.id) AS total
    FROM categories c
    LEFT JOIN words w ON w.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY total DESC
");
$categoryCounts = $catStmt->fetchAll();

// Words per dialect (bar chart data)
$dialectStmt = $pdo->query("
    SELECT d.name, COUNT(w.id) AS total
    FROM dialects d
    LEFT JOIN words w ON w.dialect_id = d.id
    GROUP BY d.id, d.name
    ORDER BY total DESC
");
$dialectCounts = $dialectStmt->fetchAll();

// Feedback rating distribution (donut chart data)
$ratingStmt = $pdo->query("SELECT rating, COUNT(*) AS total FROM feedback GROUP BY rating");
$ratingRows = $ratingStmt->fetchAll();
$ratingDist = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($ratingRows as $r) { $ratingDist[(int)$r['rating']] = (int)$r['total']; }
$totalFeedback = array_sum($ratingDist);

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Kalinga Dialects Learning Dictionary</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main">
    <div class="page-header">
      <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Overview of dictionary content and user feedback</p>
      </div>
      <div class="actions">
        <select class="term-select" disabled>
          <option>All Categories</option>
        </select>
        <button class="btn btn-primary" onclick="window.print()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print
        </button>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card blue">
        <div class="row">
          <span class="label">Total Dictionary Entries</span>
          <span class="icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </span>
        </div>
        <div class="value"><?= $totalWords ?></div>
      </div>

      <div class="stat-card green">
        <div class="row">
          <span class="label">Categories</span>
          <span class="icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          </span>
        </div>
        <div class="value"><?= $totalCategories ?></div>
      </div>

      <div class="stat-card orange">
        <div class="row">
          <span class="label">Dialects (Active)</span>
          <span class="icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"/><path d="M2 12h20"/></svg>
          </span>
        </div>
        <div class="value"><?= $activeDialects ?> / <?= $totalDialects ?></div>
      </div>

      <div class="stat-card red">
        <div class="row">
          <span class="label">Avg. User Rating</span>
          <span class="icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.9-6.2-3.3-6.2 3.3 1.2-6.9-5-4.9 6.9-1z"/></svg>
          </span>
        </div>
        <div class="value"><?= $totalFeedback > 0 ? $ratingLabel : 'No Data' ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="panel">
        <div class="panel-head">
          <h2>Words by Category</h2>
          <span>Dictionary entries distribution</span>
        </div>
        <canvas id="categoryBarChart" height="230"></canvas>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Feedback Overview</h2>
          <span>User satisfaction ratings</span>
        </div>
        <canvas id="ratingDonutChart" height="230"></canvas>
        <div class="legend">
          <div class="item"><span class="dot" style="background:#dc2626"></span>1 Star</div>
          <div class="item"><span class="dot" style="background:#ea580c"></span>2 Star</div>
          <div class="item"><span class="dot" style="background:#eab308"></span>3 Star</div>
          <div class="item"><span class="dot" style="background:#16a34a"></span>4 Star</div>
          <div class="item"><span class="dot" style="background:#2563eb"></span>5 Star</div>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:20px;">
      <div class="panel-head">
        <h2>Words by Dialect</h2>
        <span>Province-wide coverage progress</span>
      </div>
      <canvas id="dialectBarChart" height="110"></canvas>
    </div>
  </div>
</div>

<script>
const catLabels = <?= json_encode(array_column($categoryCounts, 'name')) ?>;
const catData   = <?= json_encode(array_map('intval', array_column($categoryCounts, 'total'))) ?>;

new Chart(document.getElementById('categoryBarChart'), {
  type: 'bar',
  data: {
    labels: catLabels,
    datasets: [{
      label: 'Entries',
      data: catData,
      backgroundColor: '#2f6b2f',
      borderRadius: 4,
      maxBarThickness: 46
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

const dialectLabels = <?= json_encode(array_column($dialectCounts, 'name')) ?>;
const dialectData   = <?= json_encode(array_map('intval', array_column($dialectCounts, 'total'))) ?>;

new Chart(document.getElementById('dialectBarChart'), {
  type: 'bar',
  data: {
    labels: dialectLabels,
    datasets: [{
      label: 'Entries',
      data: dialectData,
      backgroundColor: '#2563eb',
      borderRadius: 4,
      maxBarThickness: 60
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

const ratingDist = <?= json_encode(array_values($ratingDist)) ?>;
new Chart(document.getElementById('ratingDonutChart'), {
  type: 'doughnut',
  data: {
    labels: ['1 Star', '2 Star', '3 Star', '4 Star', '5 Star'],
    datasets: [{
      data: ratingDist,
      backgroundColor: ['#dc2626', '#ea580c', '#eab308', '#16a34a', '#2563eb'],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    cutout: '65%',
    plugins: { legend: { display: false } }
  }
});
</script>
</body>
</html>
