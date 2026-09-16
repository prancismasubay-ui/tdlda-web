<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['import_file']['name'])) {
    header('Location: ../words_import.php?err=' . urlencode('Please choose a file to upload.'));
    exit;
}

$tmpPath = $_FILES['import_file']['tmp_name'];
$ext     = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'json'])) {
    header('Location: ../words_import.php?err=' . urlencode('Only .csv or .json files are supported.'));
    exit;
}

// Normalize every row to a common shape regardless of source format
$rows = [];

if ($ext === 'json') {
    $decoded = json_decode(file_get_contents($tmpPath), true);
    if (!is_array($decoded)) {
        header('Location: ../words_import.php?err=' . urlencode('Could not parse that JSON file.'));
        exit;
    }
    foreach ($decoded as $item) {
        $definitions = $item['definitions'] ?? [];
        if (empty($definitions) && !empty($item['definition'])) {
            $definitions = [[
                'definition'      => $item['definition'],
                'example_dialect' => $item['example_dialect'] ?? null,
                'example_english' => $item['example_english'] ?? null,
            ]];
        }
        foreach ($definitions as $d) {
            $rows[] = [
                'dialect'         => $item['dialect'] ?? '',
                'category'        => $item['category'] ?? '',
                'part_of_speech'  => $item['part_of_speech'] ?? '',
                'term'            => $item['term'] ?? '',
                'definition'      => $d['definition'] ?? '',
                'example_dialect' => $d['example_dialect'] ?? '',
                'example_english' => $d['example_english'] ?? '',
                'etymology'       => $item['etymology'] ?? '',
                'synonyms'        => is_array($item['synonyms'] ?? null) ? implode(';', $item['synonyms']) : ($item['synonyms'] ?? ''),
                'contributor'     => $item['contributor'] ?? '',
                'status'          => $item['status'] ?? 'Published',
            ];
        }
    }
} else {
    if (($handle = fopen($tmpPath, 'r')) !== false) {
        $header = fgetcsv($handle);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($header, $data);
            if ($row === false) continue;
            $rows[] = [
                'dialect'         => $row['dialect'] ?? '',
                'category'        => $row['category'] ?? '',
                'part_of_speech'  => $row['part_of_speech'] ?? '',
                'term'            => $row['term'] ?? '',
                'definition'      => $row['definition'] ?? '',
                'example_dialect' => $row['example_dialect'] ?? '',
                'example_english' => $row['example_english'] ?? '',
                'etymology'       => $row['etymology'] ?? '',
                'synonyms'        => $row['synonyms'] ?? '',
                'contributor'     => $row['contributor'] ?? '',
                'status'          => $row['status'] ?? 'Published',
            ];
        }
        fclose($handle);
    }
}

$wordsCreated  = 0;
$defsAdded     = 0;
$rowsSkipped   = 0;
$dialectsMade  = 0;
$categoriesMade = 0;

// Simple in-request caches to avoid repeat lookups
$dialectCache  = [];
$categoryCache = [];
$posCache      = [];
$wordCache     = []; // key: dialectId . '|' . strtolower(term) => word_id

function findOrCreateDialect(PDO $pdo, array &$cache, string $name, int &$createdCount): ?int {
    $name = trim($name);
    if ($name === '') return null;
    $key = strtolower($name);
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT id FROM dialects WHERE LOWER(name) = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row) { $cache[$key] = (int) $row['id']; return $cache[$key]; }
    $stmt = $pdo->prepare("INSERT INTO dialects (name, status) VALUES (?, 'Active')");
    $stmt->execute([$name]);
    $createdCount++;
    $cache[$key] = (int) $pdo->lastInsertId();
    return $cache[$key];
}

function findOrCreateCategory(PDO $pdo, array &$cache, string $name, int &$createdCount): ?int {
    $name = trim($name);
    if ($name === '') return null;
    $key = strtolower($name);
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row) { $cache[$key] = (int) $row['id']; return $cache[$key]; }
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    $createdCount++;
    $cache[$key] = (int) $pdo->lastInsertId();
    return $cache[$key];
}

function findOrCreatePos(PDO $pdo, array &$cache, string $name): ?int {
    $name = trim($name);
    if ($name === '') return null;
    $key = strtolower($name);
    if (isset($cache[$key])) return $cache[$key];
    $stmt = $pdo->prepare("SELECT id FROM parts_of_speech WHERE LOWER(name) = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row) { $cache[$key] = (int) $row['id']; return $cache[$key]; }
    $stmt = $pdo->prepare("INSERT INTO parts_of_speech (name) VALUES (?)");
    $stmt->execute([$name]);
    $cache[$key] = (int) $pdo->lastInsertId();
    return $cache[$key];
}

try {
    $pdo->beginTransaction();

    foreach ($rows as $row) {
        $term = trim($row['term']);
        $definition = trim($row['definition']);
        $dialectName = trim($row['dialect']);

        if ($term === '' || $definition === '' || $dialectName === '') {
            $rowsSkipped++;
            continue;
        }

        $dialectId = findOrCreateDialect($pdo, $dialectCache, $dialectName, $dialectsMade);
        $categoryId = findOrCreateCategory($pdo, $categoryCache, $row['category'], $categoriesMade);
        $posId = findOrCreatePos($pdo, $posCache, $row['part_of_speech']);
        $status = in_array($row['status'], ['Published', 'Draft']) ? $row['status'] : 'Published';

        $wordKey = $dialectId . '|' . strtolower($term);
        if (!isset($wordCache[$wordKey])) {
            $stmt = $pdo->prepare("SELECT id FROM words WHERE dialect_id = ? AND LOWER(dialect_term) = ?");
            $stmt->execute([$dialectId, strtolower($term)]);
            $existing = $stmt->fetch();
            if ($existing) {
                $wordId = (int) $existing['id'];
                // Fill in category/POS/etymology/contributor if not already set
                $pdo->prepare("UPDATE words SET category_id = COALESCE(category_id, ?), part_of_speech_id = COALESCE(part_of_speech_id, ?), etymology = COALESCE(NULLIF(etymology,''), ?), contributor = COALESCE(NULLIF(contributor,''), ?) WHERE id = ?")
                    ->execute([$categoryId, $posId, trim($row['etymology']), trim($row['contributor']), $wordId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO words (dialect_id, category_id, part_of_speech_id, dialect_term, etymology, status, contributor) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$dialectId, $categoryId, $posId, $term, trim($row['etymology']), $status, trim($row['contributor'])]);
                $wordId = (int) $pdo->lastInsertId();
                $wordsCreated++;
            }
            $wordCache[$wordKey] = $wordId;
        } else {
            $wordId = $wordCache[$wordKey];
        }

        // Skip duplicate identical definitions
        $checkDef = $pdo->prepare("SELECT id FROM word_definitions WHERE word_id = ? AND definition_english = ?");
        $checkDef->execute([$wordId, $definition]);
        if (!$checkDef->fetch()) {
            $maxSort = (int) $pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM word_definitions WHERE word_id = $wordId")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO word_definitions (word_id, definition_english, example_sentence_dialect, example_sentence_english, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$wordId, $definition, trim($row['example_dialect']) ?: null, trim($row['example_english']) ?: null, $maxSort + 1]);
            $defsAdded++;
        }

        // Synonyms (semicolon-separated)
        if (!empty($row['synonyms'])) {
            $synStmt = $pdo->prepare("INSERT INTO word_synonyms (word_id, synonym_term) VALUES (?, ?)");
            $checkSyn = $pdo->prepare("SELECT id FROM word_synonyms WHERE word_id = ? AND synonym_term = ?");
            foreach (explode(';', $row['synonyms']) as $syn) {
                $syn = trim($syn);
                if ($syn === '') continue;
                $checkSyn->execute([$wordId, $syn]);
                if (!$checkSyn->fetch()) $synStmt->execute([$wordId, $syn]);
            }
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../words_import.php?err=' . urlencode('Import failed: ' . $e->getMessage()));
    exit;
}

$summary = "Imported: {$wordsCreated} new word(s), {$defsAdded} definition(s) added, "
         . "{$dialectsMade} new dialect(s), {$categoriesMade} new categor(y/ies). "
         . ($rowsSkipped > 0 ? "{$rowsSkipped} row(s) skipped (missing dialect/term/definition)." : "No rows skipped.");

header('Location: ../words_import.php?msg=' . urlencode($summary));
exit;
