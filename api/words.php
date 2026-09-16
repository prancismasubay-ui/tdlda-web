<?php
/**
 * GET /api/words.php
 * Query params (all optional):
 *   q          - search text (matches term or any definition)
 *   dialect    - dialect id
 *   category   - category id
 *   pos        - part_of_speech id
 *   limit      - default 50, max 200
 *   offset     - default 0
 *
 * Only Published entries are returned.
 */
require_once __DIR__ . '/_bootstrap.php';

function api_url_base(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    return "{$scheme}://{$host}{$path}/";
}

$search     = trim($_GET['q'] ?? '');
$dialectId  = $_GET['dialect'] ?? '';
$categoryId = $_GET['category'] ?? '';
$posId      = $_GET['pos'] ?? '';
$limit      = min(max((int) ($_GET['limit'] ?? 50), 1), 200);
$offset     = max((int) ($_GET['offset'] ?? 0), 0);

// Shared FROM/JOIN/WHERE clause, reused for both the count query and the page query
$fromWhere = "FROM words w
              LEFT JOIN dialects d ON w.dialect_id = d.id
              LEFT JOIN categories c ON w.category_id = c.id
              LEFT JOIN parts_of_speech p ON w.part_of_speech_id = p.id
              WHERE w.status = 'Published'";
$params = [];

if ($search !== '') {
    $fromWhere .= " AND (w.dialect_term LIKE ? OR EXISTS (
                SELECT 1 FROM word_definitions wd
                WHERE wd.word_id = w.id AND wd.definition_english LIKE ?
              ))";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dialectId !== '')  { $fromWhere .= " AND w.dialect_id = ?"; $params[] = $dialectId; }
if ($categoryId !== '') { $fromWhere .= " AND w.category_id = ?"; $params[] = $categoryId; }
if ($posId !== '')      { $fromWhere .= " AND w.part_of_speech_id = ?"; $params[] = $posId; }

$countStmt = $pdo->prepare("SELECT COUNT(*) $fromWhere");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$selectSql = "SELECT w.id, w.dialect_term, w.etymology, w.audio_path, w.contributor,
               d.id AS dialect_id, d.name AS dialect_name,
               c.id AS category_id, c.name AS category_name,
               p.id AS part_of_speech_id, p.name AS part_of_speech_name
               $fromWhere
               ORDER BY w.dialect_term ASC
               LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($selectSql);
$stmt->execute($params);
$words = $stmt->fetchAll();

$defStmt = $pdo->prepare("SELECT definition_english, example_sentence_dialect, example_sentence_english FROM word_definitions WHERE word_id = ? ORDER BY sort_order, id");
$synStmt = $pdo->prepare("SELECT synonym_term FROM word_synonyms WHERE word_id = ? ORDER BY id");

$base = api_url_base();
foreach ($words as &$w) {
    $defStmt->execute([$w['id']]);
    $w['definitions'] = $defStmt->fetchAll();
    $synStmt->execute([$w['id']]);
    $w['synonyms'] = array_column($synStmt->fetchAll(), 'synonym_term');
    $w['audio_url'] = $w['audio_path'] ? $base . 'uploads/audio/' . $w['audio_path'] : null;
    unset($w['audio_path']);
}
unset($w);

api_respond([
    'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
    'data' => $words,
]);
