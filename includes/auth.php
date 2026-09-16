<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?show_login=1');
        exit;
    }
}

function current_user(): array
{
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'full_name' => $_SESSION['full_name']  ?? 'Administrator',
        'role'      => $_SESSION['role']       ?? 'Administrator',
    ];
}
