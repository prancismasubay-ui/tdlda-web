<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$dialects = $pdo->query("
    SELECT d.*, COUNT(w.id) AS word_count
    FROM dialects d
    LEFT JOIN words w ON w.dialect_id = d.id
    GROUP BY d.id
    ORDER BY d.status = 'Active' DESC, d.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dialects - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Dialects</h1>
        <p class="subtitle">Kalinga's municipal dialects covered by the dictionary. Tinglayan is the current capstone pilot &mdash; add more as the project expands province-wide.</p>
      </div>
      <div class="actions">
        <button class="btn btn-primary" onclick="openAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Add Dialect
        </button>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['err'])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <div class="card-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Dialect</th>
            <th>Municipality</th>
            <th>Description</th>
            <th>Entries</th>
            <th>Status</th>
            <th style="width:90px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($dialects) === 0): ?>
            <tr><td colspan="6"><div class="empty-state">No dialects added yet.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($dialects as $d): ?>
          <tr>
            <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
            <td><?= htmlspecialchars($d['municipality'] ?? '—') ?></td>
            <td><?= htmlspecialchars($d['description'] ?? '') ?></td>
            <td><span class="pill"><?= $d['word_count'] ?> words</span></td>
            <td><span class="badge <?= $d['status'] === 'Active' ? 'published' : 'draft' ?>"><?= $d['status'] ?></span></td>
            <td>
              <div class="action-icons">
                <button title="Edit" onclick='openEditModal(<?= json_encode($d) ?>)'>
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </button>
                <a class="del" title="Delete" href="process/dialect_delete.php?id=<?= $d['id'] ?>" onclick="return confirm('Delete this dialect? All of its dictionary entries will also be deleted.')">
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

<div class="modal-overlay" id="dialectModal">
  <div class="modal-box" style="width:520px;">
    <h3 id="modalTitle">Add Dialect</h3>
    <form method="POST" action="process/dialect_save.php">
      <input type="hidden" name="id" id="f_id">
      <div class="form-grid">
        <div class="form-group">
          <label>Dialect Name</label>
          <input type="text" name="name" id="f_name" placeholder="e.g. Lubuagan" required>
        </div>
        <div class="form-group">
          <label>Municipality</label>
          <input type="text" name="municipality" id="f_municipality" placeholder="e.g. Lubuagan">
        </div>
        <div class="form-group full">
          <label>Description</label>
          <textarea name="description" id="f_description"></textarea>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="f_status">
            <option value="Active">Active</option>
            <option value="Planned">Planned</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Dialect</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add Dialect';
  document.getElementById('f_id').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_municipality').value = '';
  document.getElementById('f_description').value = '';
  document.getElementById('f_status').value = 'Active';
  document.getElementById('dialectModal').classList.add('show');
}
function openEditModal(d) {
  document.getElementById('modalTitle').textContent = 'Edit Dialect';
  document.getElementById('f_id').value = d.id;
  document.getElementById('f_name').value = d.name;
  document.getElementById('f_municipality').value = d.municipality || '';
  document.getElementById('f_description').value = d.description || '';
  document.getElementById('f_status').value = d.status;
  document.getElementById('dialectModal').classList.add('show');
}
function closeModal() {
  document.getElementById('dialectModal').classList.remove('show');
}
</script>
</body>
</html>
