<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$dialects   = $pdo->query("SELECT * FROM dialects ORDER BY status = 'Active' DESC, name")->fetchAll();
$posList    = $pdo->query("SELECT * FROM parts_of_speech ORDER BY name")->fetchAll();

$search        = trim($_GET['q'] ?? '');
$catFilter     = $_GET['category'] ?? '';
$dialectFilter = $_GET['dialect'] ?? '';

$sql = "SELECT w.*, c.name AS category_name, d.name AS dialect_name, p.name AS pos_name
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN dialects d ON w.dialect_id = d.id
        LEFT JOIN parts_of_speech p ON w.part_of_speech_id = p.id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (w.dialect_term LIKE ? OR EXISTS (SELECT 1 FROM word_definitions wd WHERE wd.word_id = w.id AND wd.definition_english LIKE ?))";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter !== '') { $sql .= " AND w.category_id = ?"; $params[] = $catFilter; }
if ($dialectFilter !== '') { $sql .= " AND w.dialect_id = ?"; $params[] = $dialectFilter; }
$sql .= " ORDER BY w.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$words = $stmt->fetchAll();

// Attach definitions + synonyms to each word for the edit modal / display
$defStmt = $pdo->prepare("SELECT * FROM word_definitions WHERE word_id = ? ORDER BY sort_order, id");
$synStmt = $pdo->prepare("SELECT synonym_term FROM word_synonyms WHERE word_id = ? ORDER BY id");
foreach ($words as &$w) {
    $defStmt->execute([$w['id']]);
    $w['definitions'] = $defStmt->fetchAll();
    $synStmt->execute([$w['id']]);
    $w['synonyms'] = implode(', ', array_column($synStmt->fetchAll(), 'synonym_term'));
}
unset($w);

$defaultDialectId = $dialects[0]['id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dictionary Entries - Kalinga Dialects Learning Dictionary</title>
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
        <h1>Dictionary Entries</h1>
        <p class="subtitle">Words, multiple definitions, part of speech, etymology, synonyms, and audio pronunciation</p>
      </div>
      <div class="actions">
        <a class="btn btn-outline" href="words_import.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M5 3h14"/></svg>
          Import
        </a>
        <a class="btn btn-outline" href="process/word_export.php?format=csv&<?= http_build_query($_GET) ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
          Export CSV
        </a>
        <a class="btn btn-outline" href="process/word_export.php?format=json&<?= http_build_query($_GET) ?>">Export JSON</a>
        <button class="btn btn-primary" onclick="openAddModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
          Add Word
        </button>
      </div>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <form method="GET" style="display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search term or definition..." style="flex:1; min-width:200px; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px;">
      <select name="dialect" class="term-select" onchange="this.form.submit()">
        <option value="">All Dialects</option>
        <?php foreach ($dialects as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $dialectFilter == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="category" class="term-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline" type="submit">Search</button>
    </form>

    <div class="card-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Term</th>
            <th>Part of Speech</th>
            <th>Definitions</th>
            <th>Dialect</th>
            <th>Audio</th>
            <th>Status</th>
            <th style="width:90px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($words) === 0): ?>
            <tr><td colspan="7"><div class="empty-state">No dictionary entries found.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($words as $w): ?>
          <tr>
            <td><strong><?= htmlspecialchars($w['dialect_term']) ?></strong></td>
            <td><?= $w['pos_name'] ? '<span class="pill">' . htmlspecialchars($w['pos_name']) . '</span>' : '&mdash;' ?></td>
            <td>
              <?= htmlspecialchars($w['definitions'][0]['definition_english'] ?? '') ?>
              <?php if (count($w['definitions']) > 1): ?>
                <span class="pill">+<?= count($w['definitions']) - 1 ?> more</span>
              <?php endif; ?>
            </td>
            <td><span class="pill"><?= htmlspecialchars($w['dialect_name'] ?? 'Unassigned') ?></span></td>
            <td>
              <?php if (!empty($w['audio_path'])): ?>
                <button class="audio-btn" onclick="document.getElementById('aud-<?= $w['id'] ?>').play()">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> Play
                </button>
                <audio id="aud-<?= $w['id'] ?>" src="uploads/audio/<?= htmlspecialchars($w['audio_path']) ?>"></audio>
              <?php else: ?>
                <span style="color:#9ca3af;">&mdash;</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $w['status'] === 'Published' ? 'published' : 'draft' ?>"><?= $w['status'] ?></span></td>
            <td>
              <div class="action-icons">
                <button title="Edit" onclick='openEditModal(<?= json_encode($w) ?>)'>
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                </button>
                <a class="del" title="Delete" href="process/word_delete.php?id=<?= $w['id'] ?>" onclick="return confirm('Delete this entry and all its definitions/synonyms?')">
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

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="wordModal">
  <div class="modal-box" style="width:720px;">
    <h3 id="modalTitle">Add Dictionary Entry</h3>
    <form method="POST" action="process/word_save.php" enctype="multipart/form-data">
      <input type="hidden" name="id" id="f_id">
      <div class="form-grid">
        <div class="form-group">
          <label>Dialect</label>
          <select name="dialect_id" id="f_dialect" required>
            <?php foreach ($dialects as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Dialect Term</label>
          <input type="text" name="dialect_term" id="f_term" required>
        </div>
        <div class="form-group">
          <label>Part of Speech</label>
          <select name="part_of_speech_id" id="f_pos">
            <option value="">&mdash;</option>
            <?php foreach ($posList as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id" id="f_category">
            <option value="">Uncategorized</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group full">
          <label>Etymology / Notes</label>
          <textarea name="etymology" id="f_etymology" placeholder="Word origin, cultural notes, related roots..."></textarea>
        </div>
      </div>

      <hr style="margin:20px 0; border:none; border-top:1px solid var(--border-color);">

      <label style="font-size:13px; font-weight:700; color:#374151;">Definitions</label>
      <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:10px;">A word can have more than one meaning &mdash; add as many as needed.</p>
      <div id="definitionsWrap"></div>
      <button type="button" class="btn btn-outline btn-sm" onclick="addDefinitionRow()" style="margin-top:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Add Another Definition
      </button>

      <hr style="margin:20px 0; border:none; border-top:1px solid var(--border-color);">

      <div class="form-grid">
        <div class="form-group">
          <label>Synonyms <span style="font-weight:400; color:var(--text-muted);">(comma-separated)</span></label>
          <input type="text" name="synonyms" id="f_synonyms" placeholder="e.g. term1, term2">
        </div>
        <div class="form-group">
          <label>Contributor</label>
          <input type="text" name="contributor" id="f_contributor">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="f_status">
            <option value="Published">Published</option>
            <option value="Draft">Draft</option>
          </select>
        </div>
        <div class="form-group">
          <label>Audio Pronunciation (mp3/wav)</label>
          <input type="file" name="audio_file" accept="audio/*">
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Entry</button>
      </div>
    </form>
  </div>
</div>

<template id="definitionRowTemplate">
  <div class="def-row" style="background:#f9fafb; border-radius:8px; padding:14px; margin-bottom:10px; position:relative;">
    <button type="button" onclick="this.closest('.def-row').remove()" style="position:absolute; top:10px; right:10px; background:none; border:none; cursor:pointer; color:#9ca3af;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
    </button>
    <div class="form-group" style="margin-bottom:8px;">
      <label>Meaning (English)</label>
      <input type="text" name="def_definition[]" class="def-definition" required>
    </div>
    <div class="form-grid">
      <div class="form-group">
        <label>Example Sentence (Dialect)</label>
        <input type="text" name="def_example_dialect[]" class="def-example-dialect">
      </div>
      <div class="form-group">
        <label>Example Sentence (English)</label>
        <input type="text" name="def_example_english[]" class="def-example-english">
      </div>
    </div>
  </div>
</template>

<script>
const defaultDialectId = "<?= $defaultDialectId ?>";

function addDefinitionRow(data) {
  const tpl = document.getElementById('definitionRowTemplate').content.cloneNode(true);
  if (data) {
    tpl.querySelector('.def-definition').value = data.definition_english || '';
    tpl.querySelector('.def-example-dialect').value = data.example_sentence_dialect || '';
    tpl.querySelector('.def-example-english').value = data.example_sentence_english || '';
  }
  document.getElementById('definitionsWrap').appendChild(tpl);
}

function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add Dictionary Entry';
  document.getElementById('f_id').value = '';
  document.getElementById('f_dialect').value = defaultDialectId;
  document.getElementById('f_term').value = '';
  document.getElementById('f_pos').value = '';
  document.getElementById('f_category').value = '';
  document.getElementById('f_etymology').value = '';
  document.getElementById('f_synonyms').value = '';
  document.getElementById('f_status').value = 'Published';
  document.getElementById('f_contributor').value = '';
  document.getElementById('definitionsWrap').innerHTML = '';
  addDefinitionRow();
  document.getElementById('wordModal').classList.add('show');
}

function openEditModal(w) {
  document.getElementById('modalTitle').textContent = 'Edit Dictionary Entry';
  document.getElementById('f_id').value = w.id;
  document.getElementById('f_dialect').value = w.dialect_id;
  document.getElementById('f_term').value = w.dialect_term;
  document.getElementById('f_pos').value = w.part_of_speech_id || '';
  document.getElementById('f_category').value = w.category_id || '';
  document.getElementById('f_etymology').value = w.etymology || '';
  document.getElementById('f_synonyms').value = w.synonyms || '';
  document.getElementById('f_status').value = w.status;
  document.getElementById('f_contributor').value = w.contributor || '';
  document.getElementById('definitionsWrap').innerHTML = '';
  if (w.definitions && w.definitions.length) {
    w.definitions.forEach(d => addDefinitionRow(d));
  } else {
    addDefinitionRow();
  }
  document.getElementById('wordModal').classList.add('show');
}

function closeModal() {
  document.getElementById('wordModal').classList.remove('show');
}
</script>
</body>
</html>
