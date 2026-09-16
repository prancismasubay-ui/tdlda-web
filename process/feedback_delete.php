<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$id = $_GET['id'] ?? '';
if ($id !== '') {
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
}
header('Location: ../feedback.php?msg=' . urlencode('Feedback entry deleted.'));
exit;
