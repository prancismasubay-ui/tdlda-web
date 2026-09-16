<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dialects.php');
    exit;
}

$id           = $_POST['id'] ?? '';
$name         = trim($_POST['name'] ?? '');
$municipality = trim($_POST['municipality'] ?? '');
$description  = trim($_POST['description'] ?? '');
$status       = in_array($_POST['status'] ?? '', ['Active', 'Planned']) ? $_POST['status'] : 'Active';

if ($name === '') {
    header('Location: ../dialects.php?err=' . urlencode('Dialect name is required.'));
    exit;
}

if ($id !== '') {
    $stmt = $pdo->prepare("UPDATE dialects SET name=?, municipality=?, description=?, status=? WHERE id=?");
    $stmt->execute([$name, $municipality, $description, $status, $id]);
    $message = 'Dialect updated successfully.';
} else {
    $check = $pdo->prepare("SELECT id FROM dialects WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        header('Location: ../dialects.php?err=' . urlencode('That dialect already exists.'));
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO dialects (name, municipality, description, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $municipality, $description, $status]);
    $message = 'Dialect added successfully.';
}

header('Location: ../dialects.php?msg=' . urlencode($message));
exit;
