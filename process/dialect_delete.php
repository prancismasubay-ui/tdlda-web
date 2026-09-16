<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    $stmt = $pdo->prepare("DELETE FROM dialects WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../dialects.php?msg=' . urlencode('Dialect deleted.'));
exit;
