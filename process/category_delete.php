<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../categories.php?msg=' . urlencode('Category deleted.'));
exit;
