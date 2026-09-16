<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$categories = $pdo->query("
    SELECT c.*, COUNT(w.id) AS word_count
    FROM categories c
    LEFT JOIN words w ON w.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Categories - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Categories</h1>
        <p class="subtitle">Organize dictionary entries into topical groups</p>
      </div>
      <div class="actions">
        <button class="btn btn-primary" onclick="openAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Add Category
        </button>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <div class="card-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Category Name</th>
            <th>Description</th>
            <th>Entries</th>
            <th style="width:90px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($categories) === 0): ?>
            <tr><td colspan="4"><div class="empty-state">No categories yet.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($categories as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
            <td><?= htmlspecialchars($c['description'] ?? '') ?></td>
            <td><span class="pill"><?= $c['word_count'] ?> words</span></td>
            <td>
              <div class="action-icons">
                <button title="Edit" onclick='openEditModal(<?= json_encode($c) ?>)'>
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </button>
                <a class="del" title="Delete" href="process/category_delete.php?id=<?= $c['id'] ?>" onclick="return confirm('Delete this category? Words in it will become Uncategorized.')">
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

<div class="modal-overlay" id="catModal">
  <div class="modal-box" style="width:480px;">
    <h3 id="modalTitle">Add Category</h3>
    <form method="POST" action="process/category_save.php">
      <input type="hidden" name="id" id="f_id">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Category Name</label>
        <input type="text" name="name" id="f_name" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="f_description"></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add Category';
  document.getElementById('f_id').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_description').value = '';
  document.getElementById('catModal').classList.add('show');
}
function openEditModal(c) {
  document.getElementById('modalTitle').textContent = 'Edit Category';
  document.getElementById('f_id').value = c.id;
  document.getElementById('f_name').value = c.name;
  document.getElementById('f_description').value = c.description || '';
  document.getElementById('catModal').classList.add('show');
}
function closeModal() {
  document.getElementById('catModal').classList.remove('show');
}
</script>
</body>
</html>
