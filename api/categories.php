<?php
/**
 * GET /api/categories.php
 */
require_once __DIR__ . '/_bootstrap.php';

$stmt = $pdo->query("SELECT id, name, description FROM categories ORDER BY name");
api_respond(['data' => $stmt->fetchAll()]);
