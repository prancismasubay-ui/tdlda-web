<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$contributors = $pdo->query("
    SELECT COALESCE(NULLIF(contributor,''), 'Unspecified') AS contributor, COUNT(*) AS total
    FROM words
    GROUP BY contributor
    ORDER BY total DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contributors - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Contributors</h1>
        <p class="subtitle">Native speakers, elders, and community members who contributed dictionary entries</p>
      </div>
    </div>

    <div class="card-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Contributor</th>
            <th>Entries Submitted</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($contributors) === 0): ?>
            <tr><td colspan="2"><div class="empty-state">No contributor data yet.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($contributors as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['contributor']) ?></strong></td>
            <td><span class="pill"><?= $c['total'] ?> entries</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
