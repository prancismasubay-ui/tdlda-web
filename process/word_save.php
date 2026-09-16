<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../words.php');
    exit;
}

$id            = $_POST['id'] ?? '';
$dialectId     = $_POST['dialect_id'] !== '' ? (int) $_POST['dialect_id'] : null;
$term          = trim($_POST['dialect_term'] ?? '');
$posId         = $_POST['part_of_speech_id'] !== '' ? (int) $_POST['part_of_speech_id'] : null;
$categoryId    = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
$etymology     = trim($_POST['etymology'] ?? '');
$status        = in_array($_POST['status'] ?? '', ['Published', 'Draft']) ? $_POST['status'] : 'Published';
$contributor   = trim($_POST['contributor'] ?? '');
$synonymsRaw   = trim($_POST['synonyms'] ?? '');

$defDefinitions = $_POST['def_definition'] ?? [];
$defExDialect   = $_POST['def_example_dialect'] ?? [];
$defExEnglish   = $_POST['def_example_english'] ?? [];

if ($term === '' || !$dialectId || count(array_filter($defDefinitions, fn($d) => trim($d) !== '')) === 0) {
    header('Location: ../words.php?msg=' . urlencode('Dialect, term, and at least one definition are required.'));
    exit;
}

// Handle optional audio upload
$audioFileName = null;
if (!empty($_FILES['audio_file']['name'])) {
    $allowedExt = ['mp3', 'wav', 'ogg', 'm4a'];
    $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExt) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
        $audioFileName = 'audio_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir = __DIR__ . '/../uploads/audio/';
        move_uploaded_file($_FILES['audio_file']['tmp_name'], $destDir . $audioFileName);
    }
}

try {
    $pdo->beginTransaction();

    if ($id !== '') {
        if ($audioFileName) {
            $stmt = $pdo->prepare("UPDATE words SET dialect_id=?, category_id=?, part_of_speech_id=?, dialect_term=?, etymology=?, status=?, contributor=?, audio_path=? WHERE id=?");
            $stmt->execute([$dialectId, $categoryId, $posId, $term, $etymology, $status, $contributor, $audioFileName, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE words SET dialect_id=?, category_id=?, part_of_speech_id=?, dialect_term=?, etymology=?, status=?, contributor=? WHERE id=?");
            $stmt->execute([$dialectId, $categoryId, $posId, $term, $etymology, $status, $contributor, $id]);
        }
        $wordId = (int) $id;

        // Replace definitions and synonyms wholesale (simplest consistent approach)
        $pdo->prepare("DELETE FROM word_definitions WHERE word_id = ?")->execute([$wordId]);
        $pdo->prepare("DELETE FROM word_synonyms WHERE word_id = ?")->execute([$wordId]);
        $message = 'Entry updated successfully.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO words (dialect_id, category_id, part_of_speech_id, dialect_term, etymology, status, contributor, audio_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dialectId, $categoryId, $posId, $term, $etymology, $status, $contributor, $audioFileName]);
        $wordId = (int) $pdo->lastInsertId();
        $message = 'Entry added successfully.';
    }

    // Insert definitions
    $defStmt = $pdo->prepare("INSERT INTO word_definitions (word_id, definition_english, example_sentence_dialect, example_sentence_english, sort_order) VALUES (?, ?, ?, ?, ?)");
    $sort = 0;
    foreach ($defDefinitions as $i => $defText) {
        $defText = trim($defText);
        if ($defText === '') continue;
        $sort++;
        $exDialect = trim($defExDialect[$i] ?? '');
        $exEnglish = trim($defExEnglish[$i] ?? '');
        $defStmt->execute([$wordId, $defText, $exDialect ?: null, $exEnglish ?: null, $sort]);
    }

    // Insert synonyms
    if ($synonymsRaw !== '') {
        $synStmt = $pdo->prepare("INSERT INTO word_synonyms (word_id, synonym_term) VALUES (?, ?)");
        foreach (explode(',', $synonymsRaw) as $syn) {
            $syn = trim($syn);
            if ($syn !== '') $synStmt->execute([$wordId, $syn]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../words.php?msg=' . urlencode('Error saving entry: ' . $e->getMessage()));
    exit;
}

header('Location: ../words.php?msg=' . urlencode($message));
exit;
