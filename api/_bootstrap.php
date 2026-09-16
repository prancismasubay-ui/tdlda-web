<?php
/**
 * Shared bootstrap for the public read-only REST API.
 * All endpoints here are GET-only and return JSON. Intended for a future
 * mobile app or other client to consume the dictionary data.
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests are supported by this API.']);
    exit;
}

function api_respond($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $status = 400): void {
    api_respond(['error' => $message], $status);
}
