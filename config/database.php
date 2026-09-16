<?php

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'tdlda';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,

    // TiDB Cloud requires TLS for public connections.
    PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        $options
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please check the server configuration.');
}
