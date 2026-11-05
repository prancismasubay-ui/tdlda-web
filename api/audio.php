<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/audio_upload.php';

if (audio_storage_driver() !== 'tidb') {
    http_response_code(404);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}

$filename = $_GET['file'] ?? '';
if (!is_string($filename) || !preg_match('/^audio_[a-f0-9]{32}\.(?:mp3|wav|ogg|m4a|aac)$/', $filename)) {
    http_response_code(404);
    exit;
}

$fileStmt = $pdo->prepare('SELECT mime_type, byte_length FROM dictionary_audio_files WHERE audio_path = ?');
$fileStmt->execute([$filename]);
$file = $fileStmt->fetch();
if (!$file) {
    http_response_code(404);
    exit;
}

$mimeTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4', 'audio/aac'];
if (!in_array($file['mime_type'], $mimeTypes, true)) {
    http_response_code(404);
    exit;
}

$totalLength = (int) $file['byte_length'];
$audioData = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dataStmt = $pdo->prepare('SELECT audio_data FROM dictionary_audio_chunks WHERE audio_path = ? ORDER BY chunk_number');
    $dataStmt->execute([$filename]);
    $audioData = '';
    while (($chunk = $dataStmt->fetchColumn()) !== false) $audioData .= $chunk;
    if (strlen($audioData) !== $totalLength) {
        http_response_code(500);
        exit;
    }
}

$start = 0;
$end = $totalLength - 1;
$partial = false;
$range = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));
if ($range !== '') {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches) || ($matches[1] === '' && $matches[2] === '')) {
        header('Content-Range: bytes */' . $totalLength);
        http_response_code(416);
        exit;
    }
    if ($matches[1] === '') {
        $suffixLength = (int) $matches[2];
        if ($suffixLength < 1) {
            header('Content-Range: bytes */' . $totalLength);
            http_response_code(416);
            exit;
        }
        $start = max(0, $totalLength - $suffixLength);
    } else {
        $start = (int) $matches[1];
        if ($matches[2] !== '') $end = min($end, (int) $matches[2]);
    }
    if ($start >= $totalLength || $end < $start) {
        header('Content-Range: bytes */' . $totalLength);
        http_response_code(416);
        exit;
    }
    $partial = true;
}

$responseLength = $end - $start + 1;
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . $responseLength);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
if ($partial) {
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $totalLength);
}
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') exit;
echo substr($audioData, $start, $responseLength);
