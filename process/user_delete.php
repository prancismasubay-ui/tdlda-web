<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '' && $id != current_user()['id']) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../users.php?msg=' . urlencode('User deleted.'));
exit;
