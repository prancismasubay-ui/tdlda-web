<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    // Remove audio file from disk if present
    $stmt = $pdo->prepare("SELECT audio_path FROM words WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['audio_path'])) {
        $path = __DIR__ . '/../uploads/audio/' . $row['audio_path'];
        if (file_exists($path)) unlink($path);
    }
    $stmt = $pdo->prepare("DELETE FROM words WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../words.php?msg=' . urlencode('Entry deleted.'));
exit;
