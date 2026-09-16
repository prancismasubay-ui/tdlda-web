<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$isLoggedIn = !empty($_SESSION['user_id']);

$search     = trim($_GET['q'] ?? '');
$categoryId = $_GET['category'] ?? '';
$dialectId  = $_GET['dialect'] ?? '';

$dialects = $pdo->query("SELECT * FROM dialects WHERE status = 'Active' ORDER BY name")->fetchAll();

// Default to the first active dialect if none chosen, so the switcher always has a value
if ($dialectId === '' && count($dialects) > 0) {
    $dialectId = $dialects[0]['id'];
}

$categories = $pdo->query("
    SELECT c.* FROM categories c
    INNER JOIN words w ON w.category_id = c.id AND w.status = 'Published'
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$sql = "SELECT w.*, c.name AS category_name, d.name AS dialect_name, p.name AS pos_name
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN dialects d ON w.dialect_id = d.id
        LEFT JOIN parts_of_speech p ON w.part_of_speech_id = p.id
        WHERE w.status = 'Published'";
$params = [];
if ($search !== '') {
    $sql .= " AND (w.dialect_term LIKE ? OR EXISTS (
                SELECT 1 FROM word_definitions wd WHERE wd.word_id = w.id AND wd.definition_english LIKE ?
              ) OR EXISTS (
                SELECT 1 FROM word_synonyms ws WHERE ws.word_id = w.id AND ws.synonym_term LIKE ?
              ))";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categoryId !== '') { $sql .= " AND w.category_id = ?"; $params[] = $categoryId; }
if ($dialectId !== '')  { $sql .= " AND w.dialect_id = ?"; $params[] = $dialectId; }
$sql .= " ORDER BY w.dialect_term ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$words = $stmt->fetchAll();

$defStmt = $pdo->prepare("SELECT * FROM word_definitions WHERE word_id = ? ORDER BY sort_order, id");
$synStmt = $pdo->prepare("SELECT synonym_term FROM word_synonyms WHERE word_id = ? ORDER BY id");
foreach ($words as &$w) {
    $defStmt->execute([$w['id']]);
    $w['definitions'] = $defStmt->fetchAll();
    $synStmt->execute([$w['id']]);
    $w['synonyms'] = array_column($synStmt->fetchAll(), 'synonym_term');
}
unset($w);

// Log searches that returned nothing, for the admin's "failed searches" report
if ($search !== '' && count($words) === 0) {
    $log = $pdo->prepare("INSERT INTO failed_searches (search_term, search_count) VALUES (?, 1)
                           ON DUPLICATE KEY UPDATE search_count = search_count + 1, last_searched_at = CURRENT_TIMESTAMP");
    $log->execute([$search]);
}

$currentDialectName = 'Kalinga';
foreach ($dialects as $d) { if ((string)$d['id'] === (string)$dialectId) { $currentDialectName = $d['name']; break; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kalinga Dialects Learning Dictionary</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="A free, open dictionary for Kalinga's indigenous dialects — search words, hear pronunciations, and see example sentences.">
<meta name="theme-color" content="#2f6b2f">
<link rel="manifest" href="manifest.json">
<link rel="icon" href="assets/images/logo.svg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="public-topbar">
  <div class="brand">
    <img src="assets/images/logo.svg" alt="Logo">
    <div class="title">Kalinga Dialects<br>Learning Dictionary</div>
  </div>
  <?php if ($isLoggedIn): ?>
    <a href="dashboard.php" class="btn-login">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Admin Dashboard
    </a>
  <?php else: ?>
    <button class="btn-login" onclick="openLoginModal()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>
      Login
    </button>
  <?php endif; ?>
</div>

<div class="public-hero">
  <h1>Kalinga Dialects Learning Dictionary</h1>
  <p>A free, open dictionary preserving the languages of Kalinga. Search a word, hear how it's pronounced, and see it used in a real sentence &mdash; no account needed.</p>

  <form method="GET" class="search-bar-wrap">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search in English or <?= htmlspecialchars($currentDialectName) ?>...">
    <?php if ($categoryId !== ''): ?><input type="hidden" name="category" value="<?= htmlspecialchars($categoryId) ?>"><?php endif; ?>
    <?php if ($dialectId !== ''): ?><input type="hidden" name="dialect" value="<?= htmlspecialchars($dialectId) ?>"><?php endif; ?>
  </form>

  <?php if (count($dialects) > 0): ?>
  <div class="dialect-switcher">
    <label for="dialectSwitch">🌐 Dialect:</label>
    <select id="dialectSwitch" onchange="switchDialect(this.value)">
      <?php foreach ($dialects as $d): ?>
        <option value="<?= $d['id'] ?>" <?= (string)$dialectId === (string)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>
</div>

<div class="public-body">

  <div class="filter-row">
    <a class="chip <?= $categoryId === '' ? 'active' : '' ?>" href="?<?= http_build_query(array_filter(['q'=>$search,'dialect'=>$dialectId])) ?>">All Categories</a>
    <?php foreach ($categories as $c): ?>
      <a class="chip <?= (string)$categoryId === (string)$c['id'] ? 'active' : '' ?>"
         href="?<?= http_build_query(array_filter(['q'=>$search,'dialect'=>$dialectId,'category'=>$c['id']])) ?>">
        <?= htmlspecialchars($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="result-count"><?= count($words) ?> <?= count($words) === 1 ? 'word' : 'words' ?> found<?= $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : '' ?></div>

  <?php if (count($words) === 0): ?>
    <div class="empty-state">
      No entries match your search yet. Try a different word, or check back soon &mdash; new entries are added regularly.
    </div>
  <?php else: ?>
    <div class="word-grid">
      <?php foreach ($words as $w): ?>
        <div class="word-card word-card-clickable" tabindex="0" role="button" onclick="openWordFeedback(<?= (int)$w['id'] ?>)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openWordFeedback(<?= (int)$w['id'] ?>)}">
          <div class="wc-top">
            <div>
              <div class="wc-term"><?= htmlspecialchars($w['dialect_term']) ?></div>
              <?php if ($w['pos_name']): ?><div class="wc-pos"><?= htmlspecialchars($w['pos_name']) ?></div><?php endif; ?>
            </div>
            <?php if (!empty($w['audio_path'])): ?>
              <button class="audio-btn" onclick="event.stopPropagation(); document.getElementById('aud-<?= $w['id'] ?>').play()" title="Play pronunciation">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                Play
              </button>
              <audio id="aud-<?= $w['id'] ?>" src="uploads/audio/<?= htmlspecialchars($w['audio_path']) ?>"></audio>
            <?php endif; ?>
          </div>

          <div class="wc-meta">
            <span class="pill"><?= htmlspecialchars($w['dialect_name'] ?? '') ?></span>
            <?php if ($w['category_name']): ?><span class="pill"><?= htmlspecialchars($w['category_name']) ?></span><?php endif; ?>
          </div>

          <?php foreach ($w['definitions'] as $i => $d): ?>
            <div class="wc-definition">
              <?php if (count($w['definitions']) > 1): ?><span class="wc-def-num"><?= $i + 1 ?></span><?php endif; ?>
              <span class="wc-def-text"><?= htmlspecialchars($d['definition_english']) ?></span>
              <?php if (!empty($d['example_sentence_dialect']) || !empty($d['example_sentence_english'])): ?>
                <div class="wc-example">
                  <?php if (!empty($d['example_sentence_dialect'])): ?><strong><?= htmlspecialchars($w['dialect_name']) ?>:</strong> <?= htmlspecialchars($d['example_sentence_dialect']) ?><br><?php endif; ?>
                  <?php if (!empty($d['example_sentence_english'])): ?><strong>English:</strong> <?= htmlspecialchars($d['example_sentence_english']) ?><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if (!empty($w['synonyms'])): ?>
            <div class="wc-synonyms"><strong>Synonyms:</strong> <?= htmlspecialchars(implode(', ', $w['synonyms'])) ?></div>
          <?php endif; ?>

          <?php if (!empty($w['etymology'])): ?>
            <div class="wc-etymology"><em><?= htmlspecialchars($w['etymology']) ?></em></div>
          <?php endif; ?>

          <button type="button" class="word-feedback-btn" onclick="event.stopPropagation(); openWordFeedback(<?= (int)$w['id'] ?>)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-9 8.5 9.4 9.4 0 0 1-4-.9L3 21l1.9-4.4A8.38 8.38 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5Z"/><path d="M8 11h8M8 15h5"/></svg>
            Give feedback on this word
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<div class="public-footer">
  Kalinga Dialects Learning Dictionary &mdash; a capstone project developed with the Kalinga Provincial Tourism Office.<br>
  Documenting and preserving Kalinga's indigenous dialects, one word at a time.
</div>

<!-- Word Feedback Modal -->
<div class="modal-overlay" id="wordFeedbackModal" aria-hidden="true">
  <div class="modal-box word-feedback-modal" role="dialog" aria-modal="true" aria-labelledby="wordFeedbackTitle">
    <button class="close-x" type="button" onclick="closeWordFeedback()" aria-label="Close feedback">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
    </button>
    <div class="feedback-word-heading">
      <div class="feedback-word-term" id="feedbackWordTerm">Word</div>
      <div class="feedback-word-meta" id="feedbackWordMeta"></div>
    </div>
    <h3 id="wordFeedbackTitle">Feedback on this word</h3>
    <p class="feedback-intro">Help us improve this dictionary entry. Your feedback will be reviewed by the administrator.</p>

    <div class="error-msg" id="wordFeedbackError"></div>
    <div class="alert alert-success" id="wordFeedbackSuccess" style="display:none;"></div>

    <form id="wordFeedbackForm">
      <input type="hidden" name="word_id" id="feedbackWordId">
      <div class="form-group">
        <label>How would you like to comment?</label>
        <select name="feedback_type" required>
          <option value="Comment">General comment</option>
          <option value="Spelling">Spelling may be incorrect</option>
          <option value="Definition">Definition may be incorrect</option>
          <option value="Example">Example sentence needs correction</option>
          <option value="Suggestion">Suggestion for this entry</option>
        </select>
      </div>
      <div class="form-group">
        <label>Rating</label>
        <div class="rating-stars" role="radiogroup" aria-label="Rate this dictionary entry">
          <?php for ($r = 1; $r <= 5; $r++): ?>
            <button type="button" class="rating-star" data-rating="<?= $r ?>" aria-label="<?= $r ?> star<?= $r > 1 ? 's' : '' ?>">☆</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="feedbackRating" value="5">
        <div class="rating-label" id="ratingLabel">5 stars</div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Your name <span class="optional">(optional)</span></label>
          <input type="text" name="respondent_name" maxlength="100" placeholder="Anonymous">
        </div>
        <div class="form-group">
          <label>Respondent type</label>
          <select name="respondent_type">
            <option value="Other">Other</option>
            <option value="Tourist">Tourist</option>
            <option value="Non-Kalinga Speaker">Non-Kalinga Speaker</option>
            <option value="Native Speaker">Native Speaker</option>
            <option value="Student">Student</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Comments</label>
        <textarea name="comments" maxlength="2000" required placeholder="What should we know about this word?"></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeWordFeedback()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="wordFeedbackSubmit">Submit Feedback</button>
      </div>
    </form>
  </div>
</div>

<!-- Login Modal -->
<div class="modal-overlay" id="loginModal">
  <div class="modal-box login-modal-box">
    <button class="close-x" onclick="closeLoginModal()">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
    </button>
    <img src="assets/images/logo.svg" alt="Logo">
    <h3>Kalinga Dialects Learning Dictionary</h3>
    <p class="sub">Kalinga Provincial Tourism Office &mdash; Admin Panel</p>

    <div class="error-msg" id="loginModalError"></div>

    <form id="loginForm">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" id="lm_username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" id="lm_password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px;">Sign In</button>
    </form>
  </div>
</div>

<script>
let activeFeedbackWordId = null;

function openWordFeedback(wordId) {
  activeFeedbackWordId = Number(wordId);
  const card = document.querySelector('.word-card[onclick*="openWordFeedback(' + activeFeedbackWordId + ')"]');
  const modal = document.getElementById('wordFeedbackModal');
  const form = document.getElementById('wordFeedbackForm');
  const errorBox = document.getElementById('wordFeedbackError');
  const successBox = document.getElementById('wordFeedbackSuccess');
  const wordIdInput = document.getElementById('feedbackWordId');
  const term = card ? card.querySelector('.wc-term')?.textContent.trim() : 'Dictionary word';
  const meta = card ? Array.from(card.querySelectorAll('.wc-meta .pill')).map(el => el.textContent.trim()).join(' • ') : '';
  document.getElementById('feedbackWordTerm').textContent = term;
  document.getElementById('feedbackWordMeta').textContent = meta;
  wordIdInput.value = activeFeedbackWordId;
  form.reset();
  wordIdInput.value = activeFeedbackWordId;
  document.getElementById('feedbackRating').value = '5';
  updateRatingStars(5);
  errorBox.style.display = 'none';
  successBox.style.display = 'none';
  modal.classList.add('show');
  modal.setAttribute('aria-hidden', 'false');
  setTimeout(() => form.querySelector('select[name="feedback_type"]').focus(), 50);
}

function closeWordFeedback() {
  const modal = document.getElementById('wordFeedbackModal');
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden', 'true');
  activeFeedbackWordId = null;
}

function updateRatingStars(rating) {
  document.querySelectorAll('.rating-star').forEach(btn => {
    const value = Number(btn.dataset.rating);
    btn.textContent = value <= rating ? '★' : '☆';
    btn.classList.toggle('selected', value <= rating);
  });
  document.getElementById('ratingLabel').textContent = rating + (rating === 1 ? ' star' : ' stars');
}

document.querySelectorAll('.rating-star').forEach(btn => {
  btn.addEventListener('click', () => {
    const rating = Number(btn.dataset.rating);
    document.getElementById('feedbackRating').value = String(rating);
    updateRatingStars(rating);
  });
});

document.getElementById('wordFeedbackModal').addEventListener('click', function (e) {
  if (e.target === this) closeWordFeedback();
});

document.getElementById('wordFeedbackForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const form = this;
  const submitBtn = document.getElementById('wordFeedbackSubmit');
  const errorBox = document.getElementById('wordFeedbackError');
  const successBox = document.getElementById('wordFeedbackSuccess');
  errorBox.style.display = 'none';
  successBox.style.display = 'none';
  submitBtn.disabled = true;
  submitBtn.textContent = 'Submitting...';
  try {
    const res = await fetch('process/feedback_save.php', { method: 'POST', body: new FormData(form) });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || 'Unable to submit feedback.');
    successBox.textContent = data.message || 'Thank you for your feedback!';
    successBox.style.display = 'block';
    form.reset();
    document.getElementById('feedbackWordId').value = activeFeedbackWordId;
    document.getElementById('feedbackRating').value = '5';
    updateRatingStars(5);
    submitBtn.textContent = 'Submitted';
    setTimeout(closeWordFeedback, 1600);
  } catch (err) {
    errorBox.textContent = err.message || 'Something went wrong. Please try again.';
    errorBox.style.display = 'block';
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit Feedback';
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && document.getElementById('wordFeedbackModal').classList.contains('show')) closeWordFeedback();
});

function switchDialect(dialectId) {
  const url = new URL(window.location);
  url.searchParams.set('dialect', dialectId);
  url.searchParams.delete('category');
  window.location = url.toString();
}

function openLoginModal() {
  document.getElementById('loginModal').classList.add('show');
  document.getElementById('loginModalError').style.display = 'none';
  document.getElementById('lm_username').focus();
}
function closeLoginModal() {
  document.getElementById('loginModal').classList.remove('show');
}
document.getElementById('loginModal').addEventListener('click', function (e) {
  if (e.target === this) closeLoginModal();
});

document.getElementById('loginForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const errBox = document.getElementById('loginModalError');
  const submitBtn = this.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Signing in...';

  try {
    const formData = new FormData(this);
    const res = await fetch('process/login_ajax.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect;
    } else {
      errBox.textContent = data.message || 'Login failed.';
      errBox.style.display = 'block';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Sign In';
    }
  } catch (err) {
    errBox.textContent = 'Something went wrong. Please try again.';
    errBox.style.display = 'block';
    submitBtn.disabled = false;
    submitBtn.textContent = 'Sign In';
  }
});

<?php if (isset($_GET['show_login'])): ?>
openLoginModal();
<?php endif; ?>

// Register the service worker for basic offline access to previously viewed pages
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('service-worker.js').catch(() => {});
  });
}
</script>
</body>
</html>
