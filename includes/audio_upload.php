<?php

function store_word_audio(array $file, string $directory): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Audio upload failed. Please try a smaller file or check the server upload limit.');
    }
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $types = [
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
    ];
    $temporaryPath = $file['tmp_name'] ?? '';
    if (!isset($types[$extension]) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Choose a valid MP3, WAV, OGG, or M4A audio file.');
    }
    $size = filesize($temporaryPath);
    if ($size === false || $size === 0 || $size > 10 * 1024 * 1024) {
        throw new RuntimeException('Audio files must be between 1 byte and 10 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    if (!in_array($mime, $types[$extension], true)) {
        throw new RuntimeException('The selected file is not a supported audio recording.');
    }
    // Git deployments may omit an empty uploads folder.
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Audio storage is unavailable. Please contact the administrator.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Audio storage is not writable. Please contact the administrator.');
    }
    $name = 'audio_' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (!@move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('The audio file could not be stored. The entry was not saved.');
    }
    return $name;
}
