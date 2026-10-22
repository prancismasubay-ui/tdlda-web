<?php

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
    $path = rtrim(str_replace('\\', '/', dirname(dirname($server['SCRIPT_NAME'] ?? '/api/word.php'))), '/');
    return $scheme . '://' . $host . $path . '/';
}

function dictionary_audio_url(?string $filename, array $server): ?string
{
    return $filename ? dictionary_public_base_url($server) . 'uploads/audio/' . rawurlencode($filename) : null;
}
