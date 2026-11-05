<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    // Delete the database entry first; remove its external or local audio afterwards.
    $stmt = $pdo->prepare("SELECT audio_path FROM words WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $stmt = $pdo->prepare("DELETE FROM words WHERE id = ?");
    $stmt->execute([$id]);
    if ($row && !empty($row['audio_path'])) {
        require_once __DIR__ . '/../includes/audio_upload.php';
        try {
            delete_word_audio($row['audio_path'], __DIR__ . '/../uploads/audio', $pdo);
        } catch (RuntimeException $cleanupError) {
            error_log('Could not remove deleted word audio: ' . $cleanupError->getMessage());
        }
    }
}
header('Location: ../words.php?msg=' . urlencode('Entry deleted.'));
exit;
