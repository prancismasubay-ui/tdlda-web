<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    $stmt = $pdo->prepare("DELETE FROM parts_of_speech WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../parts_of_speech.php?msg=' . urlencode('Deleted.'));
exit;
