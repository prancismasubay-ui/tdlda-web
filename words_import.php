<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Import Entries - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Bulk Import</h1>
        <p class="subtitle">Upload a CSV or JSON file to add many dictionary entries at once</p>
      </div>
      <div class="actions">
        <a class="btn btn-outline" href="words.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          Back to Entries
        </a>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['err'])): ?><div class="alert alert-error"><?= htmlspecialchars($_GET['err']) ?></div><?php endif; ?>

    <div class="panel-grid">
      <div class="panel">
        <div class="panel-head"><h2>Upload File</h2></div>
        <form method="POST" action="process/word_import.php" enctype="multipart/form-data">
          <div class="form-group" style="margin-bottom:16px;">
            <label>CSV or JSON File</label>
            <input type="file" name="import_file" accept=".csv,.json" required>
          </div>
          <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:16px;">
            If a named dialect or category doesn't exist yet, it will be created automatically
            (new dialects are added as "Active"). Rows sharing the same dialect + term are
            merged as multiple definitions of one word.
          </p>
          <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
        </form>
      </div>

      <div class="panel">
        <div class="panel-head"><h2>File Format</h2></div>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">
          <strong>CSV columns</strong> (header row required):
        </p>
        <div class="card-table" style="border:none; margin-bottom:16px;">
          <table class="data-table">
            <thead><tr><th style="font-size:11px;">Column</th><th style="font-size:11px;">Required</th></tr></thead>
            <tbody>
              <tr><td>dialect</td><td>Yes</td></tr>
              <tr><td>category</td><td>No</td></tr>
              <tr><td>part_of_speech</td><td>No</td></tr>
              <tr><td>term</td><td>Yes</td></tr>
              <tr><td>definition</td><td>Yes</td></tr>
              <tr><td>example_dialect</td><td>No</td></tr>
              <tr><td>example_english</td><td>No</td></tr>
              <tr><td>etymology</td><td>No</td></tr>
              <tr><td>synonyms</td><td>No (semicolon-separated)</td></tr>
              <tr><td>contributor</td><td>No</td></tr>
              <tr><td>status</td><td>No (Published/Draft)</td></tr>
            </tbody>
          </table>
        </div>
        <a class="btn btn-outline btn-sm" href="process/word_export.php?format=csv">Download current data as a CSV template</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
