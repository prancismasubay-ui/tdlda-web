<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    header('Location: ../parts_of_speech.php?err=' . urlencode('Name is required.'));
    exit;
}
$check = $pdo->prepare("SELECT id FROM parts_of_speech WHERE name = ?");
$check->execute([$name]);
if ($check->fetch()) {
    header('Location: ../parts_of_speech.php?err=' . urlencode('That tag already exists.'));
    exit;
}
$stmt = $pdo->prepare("INSERT INTO parts_of_speech (name) VALUES (?)");
$stmt->execute([$name]);
header('Location: ../parts_of_speech.php?msg=' . urlencode('Added successfully.'));
exit;
