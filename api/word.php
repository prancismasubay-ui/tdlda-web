<?php
/**
 * GET /api/word.php?id=123
 */
require_once __DIR__ . '/_bootstrap.php';

$id = $_GET['id'] ?? '';
if ($id === '' || !ctype_digit((string) $id)) {
    api_error('A numeric "id" parameter is required.', 400);
}

$stmt = $pdo->prepare("
    SELECT w.id, w.dialect_term, w.etymology, w.audio_path, w.contributor, w.status,
           d.id AS dialect_id, d.name AS dialect_name,
           c.id AS category_id, c.name AS category_name,
           p.id AS part_of_speech_id, p.name AS part_of_speech_name
    FROM words w
    LEFT JOIN dialects d ON w.dialect_id = d.id
    LEFT JOIN categories c ON w.category_id = c.id
    LEFT JOIN parts_of_speech p ON w.part_of_speech_id = p.id
    WHERE w.id = ? AND w.status = 'Published'
");
$stmt->execute([$id]);
$word = $stmt->fetch();

if (!$word) {
    api_error('Word not found.', 404);
}

$defStmt = $pdo->prepare("SELECT definition_english, example_sentence_dialect, example_sentence_english FROM word_definitions WHERE word_id = ? ORDER BY sort_order, id");
$defStmt->execute([$id]);
$word['definitions'] = $defStmt->fetchAll();

$synStmt = $pdo->prepare("SELECT synonym_term FROM word_synonyms WHERE word_id = ? ORDER BY id");
$synStmt->execute([$id]);
$word['synonyms'] = array_column($synStmt->fetchAll(), 'synonym_term');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$word['audio_url'] = $word['audio_path'] ? "{$scheme}://{$host}{$base}/uploads/audio/{$word['audio_path']}" : null;
unset($word['audio_path']);

api_respond(['data' => $word]);
