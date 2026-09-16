<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$totalWords    = (int) $pdo->query("SELECT COUNT(*) FROM words")->fetchColumn();
$totalFeedback = (int) $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avgRating     = round((float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM feedback")->fetchColumn(), 2);

$topCategories = $pdo->query("
    SELECT c.name, COUNT(w.id) AS total
    FROM categories c LEFT JOIN words w ON w.category_id = c.id
    GROUP BY c.id ORDER BY total DESC LIMIT 5
")->fetchAll();

$typeBreakdown = $pdo->query("
    SELECT respondent_type, COUNT(*) AS total FROM feedback GROUP BY respondent_type ORDER BY total DESC
")->fetchAll();

$failedSearches = $pdo->query("
    SELECT search_term, search_count, last_searched_at FROM failed_searches
    ORDER BY search_count DESC, last_searched_at DESC LIMIT 15
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Results - Kalinga Dialects Learning Dictionary</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main">
    <div class="page-header">
      <div>
        <h1>Results</h1>
        <p class="subtitle">Summary of dictionary content growth and usability evaluation</p>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card blue">
        <div class="row"><span class="label">Total Words Documented</span></div>
        <div class="value"><?= $totalWords ?></div>
      </div>
      <div class="stat-card green">
        <div class="row"><span class="label">Survey Responses</span></div>
        <div class="value"><?= $totalFeedback ?></div>
      </div>
      <div class="stat-card orange">
        <div class="row"><span class="label">Average Rating (/5)</span></div>
        <div class="value"><?= $totalFeedback > 0 ? $avgRating : '—' ?></div>
      </div>
    </div>

    <div class="panel-grid">
      <div class="panel">
        <div class="panel-head">
          <h2>Top Categories by Entry Count</h2>
        </div>
        <div class="card-table" style="border:none;">
          <table class="data-table">
            <thead><tr><th>Category</th><th>Entries</th></tr></thead>
            <tbody>
              <?php foreach ($topCategories as $c): ?>
              <tr><td><?= htmlspecialchars($c['name']) ?></td><td><span class="pill"><?= $c['total'] ?></span></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <h2>Respondents by Type</h2>
        </div>
        <div class="card-table" style="border:none;">
          <table class="data-table">
            <thead><tr><th>Type</th><th>Count</th></tr></thead>
            <tbody>
              <?php if (count($typeBreakdown) === 0): ?>
                <tr><td colspan="2"><div class="empty-state">No survey data yet.</div></td></tr>
              <?php endif; ?>
              <?php foreach ($typeBreakdown as $t): ?>
              <tr><td><?= htmlspecialchars($t['respondent_type']) ?></td><td><span class="pill"><?= $t['total'] ?></span></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:20px;">
      <div class="panel-head">
        <h2>Failed Searches</h2>
        <span>Words visitors looked for but couldn't find &mdash; use this to prioritize new entries</span>
      </div>
      <div class="card-table" style="border:none;">
        <table class="data-table">
          <thead><tr><th>Search Term</th><th>Times Searched</th><th>Last Searched</th></tr></thead>
          <tbody>
            <?php if (count($failedSearches) === 0): ?>
              <tr><td colspan="3"><div class="empty-state">No unmatched searches yet.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($failedSearches as $f): ?>
            <tr>
              <td><strong><?= htmlspecialchars($f['search_term']) ?></strong></td>
              <td><span class="pill"><?= $f['search_count'] ?>&times;</span></td>
              <td><?= date('M d, Y', strtotime($f['last_searched_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
