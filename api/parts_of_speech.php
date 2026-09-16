<?php
/**
 * GET /api/parts_of_speech.php
 */
require_once __DIR__ . '/_bootstrap.php';

$stmt = $pdo->query("SELECT id, name FROM parts_of_speech ORDER BY name");
api_respond(['data' => $stmt->fetchAll()]);
