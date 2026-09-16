<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../categories.php');
    exit;
}

$id          = $_POST['id'] ?? '';
$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '') {
    header('Location: ../categories.php?msg=' . urlencode('Category name is required.'));
    exit;
}

if ($id !== '') {
    $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
    $stmt->execute([$name, $description, $id]);
    $message = 'Category updated successfully.';
} else {
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    $message = 'Category added successfully.';
}

header('Location: ../categories.php?msg=' . urlencode($message));
exit;
