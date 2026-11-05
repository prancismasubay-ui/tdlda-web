<?php
require_once __DIR__ . '/audio_upload.php';

function dictionary_public_base_url(array $server): string
{
    $forwardedScheme = strtolower(trim(explode(',', $server['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $directHttps = !empty($server['HTTPS']) && strtolower((string)$server['HTTPS']) !== 'off';
    $host = $server['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^(?:[a-z0-9.-]+|\[[a-f0-9:]+\])(?::[0-9]+)?$/i', $host)) {
        $host = 'localhost';
    }
    $renderHost = str_ends_with(strtolower(explode(':', $host)[0]), '.onrender.com');
    $scheme = $directHttps || $forwardedScheme === 'https' || $renderHost ? 'https' : 'http';
    $scriptName = str_replace('\\', '/', $server['SCRIPT_NAME'] ?? '/api/word.php');
    $scriptDirectory = str_replace(chr(92), '/', dirname($scriptName));
    if (basename($scriptDirectory) === 'api' || basename($scriptDirectory) === 'process') {
        $scriptDirectory = dirname($scriptDirectory);
    }
    $path = rtrim(str_replace(chr(92), '/', $scriptDirectory), '/');
    return $scheme . '://' . $host . $path . '/';
}

function dictionary_audio_url(?string $filename, array $server): ?string
{
    if (!$filename) return null;
    if (audio_storage_driver() === 'tidb') {
        return dictionary_public_base_url($server) . 'api/audio.php?file=' . rawurlencode(basename($filename));
    }
    return dictionary_public_base_url($server) . 'uploads/audio/' . rawurlencode(basename($filename));
}
