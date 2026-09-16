<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../users.php');
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = in_array($_POST['role'] ?? '', ['Administrator', 'Editor']) ? $_POST['role'] : 'Editor';

if ($fullName === '' || $username === '' || strlen($password) < 6) {
    header('Location: ../users.php?err=' . urlencode('All fields are required and password must be at least 6 characters.'));
    exit;
}

$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute([$username]);
if ($check->fetch()) {
    header('Location: ../users.php?err=' . urlencode('That username is already taken.'));
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$fullName, $username, $hash, $role]);

header('Location: ../users.php?msg=' . urlencode('User created successfully.'));
exit;
