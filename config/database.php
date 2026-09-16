<?php
/**
 * Production-safe database connection.
 * Render supplies these values as environment variables.
 * Local XAMPP can still work by using the fallback values below.
 */
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'tdlda';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_SSL_CA = getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // TiDB Cloud Starter requires TLS. Render's Debian image provides
            // the public CA bundle used to verify TiDB Cloud's certificate.
            PDO::MYSQL_ATTR_SSL_CA         => $DB_SSL_CA,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please check the server configuration.');
}
