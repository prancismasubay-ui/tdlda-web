<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Feedback - Kalinga Dialects Learning Dictionary</title>
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
        <h1>User Feedback</h1>
        <p class="subtitle">Responses collected from in-app and QR-linked satisfaction surveys</p>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="card-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Respondent</th>
            <th>Type</th>
            <th>Rating</th>
            <th>Comments</th>
            <th>Date</th>
            <th style="width:50px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($feedback) === 0): ?>
            <tr><td colspan="6"><div class="empty-state">No feedback submitted yet.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($feedback as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['respondent_name'] ?: 'Anonymous') ?></td>
            <td><span class="pill"><?= htmlspecialchars($f['respondent_type']) ?></span></td>
            <td><?= str_repeat('★', (int)$f['rating']) . str_repeat('☆', 5 - (int)$f['rating']) ?></td>
            <td><?= htmlspecialchars($f['comments'] ?: '—') ?></td>
            <td><?= date('M d, Y', strtotime($f['created_at'])) ?></td>
            <td>
              <div class="action-icons">
                <a class="del" title="Delete" href="process/feedback_delete.php?id=<?= $f['id'] ?>" onclick="return confirm('Delete this feedback entry?')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
