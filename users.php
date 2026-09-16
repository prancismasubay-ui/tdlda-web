<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$users = $pdo->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Users - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Admin Users</h1>
        <p class="subtitle">Manage accounts with access to this admin panel</p>
      </div>
      <div class="actions">
        <button class="btn btn-primary" onclick="openAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Add User
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
            <th>Full Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th style="width:90px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><span class="pill"><?= htmlspecialchars($u['role']) ?></span></td>
            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <div class="action-icons">
                <?php if ($u['id'] != current_user()['id']): ?>
                <a class="del" title="Delete" href="process/user_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Delete this user?')">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal-overlay" id="userModal">
  <div class="modal-box" style="width:480px;">
    <h3>Add Admin User</h3>
    <form method="POST" action="process/user_save.php">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Full Name</label>
        <input type="text" name="full_name" required>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="form-group" style="margin-bottom:14px;">
        <label>Password</label>
        <input type="password" name="password" required minlength="6">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="Editor">Editor</option>
          <option value="Administrator">Administrator</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save User</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() { document.getElementById('userModal').classList.add('show'); }
function closeModal() { document.getElementById('userModal').classList.remove('show'); }
</script>
</body>
</html>
