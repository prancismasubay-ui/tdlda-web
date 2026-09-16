<?php
/**
 * GET /api/dialects.php
 * GET /api/dialects.php?status=Active
 */
require_once __DIR__ . '/_bootstrap.php';

$status = $_GET['status'] ?? '';

$sql = "SELECT id, name, municipality, description, status FROM dialects WHERE 1=1";
$params = [];
if (in_array($status, ['Active', 'Planned'])) {
    $sql .= " AND status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

api_respond(['data' => $stmt->fetchAll()]);
