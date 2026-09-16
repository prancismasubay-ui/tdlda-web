<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$format = $_GET['format'] ?? 'csv';

$search    = trim($_GET['q'] ?? '');
$catFilter = $_GET['category'] ?? '';
$dialectFilter = $_GET['dialect'] ?? '';

$sql = "SELECT w.*, c.name AS category_name, d.name AS dialect_name, p.name AS pos_name
        FROM words w
        LEFT JOIN categories c ON w.category_id = c.id
        LEFT JOIN dialects d ON w.dialect_id = d.id
        LEFT JOIN parts_of_speech p ON w.part_of_speech_id = p.id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND w.dialect_term LIKE ?";
    $params[] = "%$search%";
}
if ($catFilter !== '') { $sql .= " AND w.category_id = ?"; $params[] = $catFilter; }
if ($dialectFilter !== '') { $sql .= " AND w.dialect_id = ?"; $params[] = $dialectFilter; }
$sql .= " ORDER BY w.dialect_term";
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

$timestamp = date('Ymd_His');

if ($format === 'json') {
    $out = array_map(function ($w) {
        return [
            'dialect'      => $w['dialect_name'],
            'category'     => $w['category_name'],
            'part_of_speech' => $w['pos_name'],
            'term'         => $w['dialect_term'],
            'etymology'    => $w['etymology'],
            'contributor'  => $w['contributor'],
            'status'       => $w['status'],
            'synonyms'     => $w['synonyms'],
            'definitions'  => array_map(function ($d) {
                return [
                    'definition'      => $d['definition_english'],
                    'example_dialect' => $d['example_sentence_dialect'],
                    'example_english' => $d['example_sentence_english'],
                ];
            }, $w['definitions']),
        ];
    }, $words);

    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename=kalinga_dictionary_export_{$timestamp}.json");
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// CSV: one row per definition (a word with 2 definitions produces 2 rows sharing the same term)
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=kalinga_dictionary_export_{$timestamp}.csv");

$out = fopen('php://output', 'w');
fputcsv($out, ['dialect', 'category', 'part_of_speech', 'term', 'definition', 'example_dialect', 'example_english', 'etymology', 'synonyms', 'contributor', 'status']);

foreach ($words as $w) {
    $synonymStr = implode('; ', $w['synonyms']);
    if (count($w['definitions']) === 0) {
        fputcsv($out, [$w['dialect_name'], $w['category_name'], $w['pos_name'], $w['dialect_term'], '', '', '', $w['etymology'], $synonymStr, $w['contributor'], $w['status']]);
    }
    foreach ($w['definitions'] as $d) {
        fputcsv($out, [
            $w['dialect_name'], $w['category_name'], $w['pos_name'], $w['dialect_term'],
            $d['definition_english'], $d['example_sentence_dialect'], $d['example_sentence_english'],
            $w['etymology'], $synonymStr, $w['contributor'], $w['status'],
        ]);
    }
}
fclose($out);
exit;
