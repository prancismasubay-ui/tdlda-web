<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$posList = $pdo->query("
    SELECT p.*, COUNT(w.id) AS word_count
    FROM parts_of_speech p
    LEFT JOIN words w ON w.part_of_speech_id = p.id
    GROUP BY p.id ORDER BY p.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parts of Speech - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Parts of Speech</h1>
        <p class="subtitle">Grammatical tags available when adding a dictionary entry</p>
      </div>
      <div class="actions">
        <button class="btn btn-primary" onclick="openAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Add
        </button>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['err'])): ?><div class="alert alert-error"><?= htmlspecialchars($_GET['err']) ?></div><?php endif; ?>

    <div class="card-table">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Entries</th><th style="width:90px;">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($posList as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td><span class="pill"><?= $p['word_count'] ?> words</span></td>
            <td>
              <div class="action-icons">
                <a class="del" title="Delete" href="process/pos_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Delete this tag? It will be cleared from any words using it.')">
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

<div class="modal-overlay" id="posModal">
  <div class="modal-box" style="width:420px;">
    <h3>Add Part of Speech</h3>
    <form method="POST" action="process/pos_save.php">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" placeholder="e.g. Conjunction" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function openAddModal() { document.getElementById('posModal').classList.add('show'); }
function closeModal() { document.getElementById('posModal').classList.remove('show'); }
</script>
</body>
</html>
