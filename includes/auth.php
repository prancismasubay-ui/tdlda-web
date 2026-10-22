<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        $prefix = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'process' ? '../' : '';
        header('Location: ' . $prefix . 'index.php?show_login=1');
        exit;
    }
    if (!in_array($_SESSION['role'] ?? '', ['Administrator', 'Editor'], true)) {
        http_response_code(403);
        exit('Account access denied. Please sign out and sign in again.');
    }
}

function is_administrator(): bool
{
    return ($_SESSION['role'] ?? '') === 'Administrator';
}

function require_administrator(): void
{
    require_login();
    if (!is_administrator()) {
        http_response_code(403);
        exit('Administrator access required.');
    }
}

function current_user(): array
{
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'full_name' => $_SESSION['full_name']  ?? 'Administrator',
        'role'      => $_SESSION['role']       ?? '',
    ];
}
