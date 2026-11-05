<?php

function store_word_audio(array $file, string $directory, ?PDO $pdo = null): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Audio upload failed. Please try a smaller file or check the server upload limit.');
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $acceptedTypes = [
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'wav' => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a', 'video/mp4'],
        'aac' => ['audio/aac', 'audio/x-aac', 'audio/x-hx-aac-adts'],
    ];
    $canonicalTypes = [
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
    ];
    $temporaryPath = $file['tmp_name'] ?? '';
    if (!isset($acceptedTypes[$extension]) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Choose a valid MP3, WAV, OGG, M4A, or AAC audio file.');
    }

    $size = filesize($temporaryPath);
    if ($size === false || $size === 0) {
        throw new RuntimeException('The selected audio file is empty or could not be read.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
    if (!in_array($mime, $acceptedTypes[$extension], true)) {
        throw new RuntimeException('The selected file is not a supported audio recording.');
    }

    $name = 'audio_' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (audio_storage_driver() === 'tidb') {
        if (!$pdo) throw new RuntimeException('Database audio storage is unavailable.');
        $contents = @file_get_contents($temporaryPath);
        if ($contents === false) {
            throw new RuntimeException('The uploaded audio could not be read. Please try again.');
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO dictionary_audio_files (audio_path, mime_type, byte_length) VALUES (?, ?, ?)');
            $stmt->execute([$name, $canonicalTypes[$extension], $size]);
            $chunkStmt = $pdo->prepare('INSERT INTO dictionary_audio_chunks (audio_path, chunk_number, audio_data) VALUES (?, ?, ?)');
            foreach (str_split($contents, 262144) as $chunkNumber => $chunk) {
                $chunkStmt->execute([$name, $chunkNumber, $chunk]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new RuntimeException('Audio could not be saved to TiDB. Check the audio storage table setup and try again.', 0, $e);
        }
        return $name;
    }

    // Git deployments may omit an empty uploads folder.
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Audio storage is unavailable. Please contact the administrator.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Audio storage is not writable. Please contact the administrator.');
    }
    if (!@move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('The audio file could not be stored. The entry was not saved.');
    }
    return $name;
}

function audio_storage_driver(): string
{
    $driver = strtolower(trim((string) getenv('AUDIO_STORAGE_DRIVER')));
    if ($driver === '') {
        $host = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
        // Never silently save uploads to Render's temporary container filesystem.
        $driver = str_ends_with($host, '.onrender.com') ? 'tidb' : 'local';
    }
    if (!in_array($driver, ['local', 'tidb'], true)) {
        throw new RuntimeException('Audio storage is not configured correctly.');
    }
    return $driver;
}

function delete_word_audio(string $filename, string $directory, ?PDO $pdo = null): void
{
    if ($filename === '') return;
    if (audio_storage_driver() === 'tidb') {
        if (!$pdo) throw new RuntimeException('Database audio storage is unavailable.');
        try {
            $pdo->beginTransaction();
            $chunks = $pdo->prepare('DELETE FROM dictionary_audio_chunks WHERE audio_path = ?');
            $chunks->execute([$filename]);
            $stmt = $pdo->prepare('DELETE FROM dictionary_audio_files WHERE audio_path = ?');
            $stmt->execute([$filename]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw new RuntimeException('The audio file could not be removed from TiDB storage.', 0, $e);
        }
        return;
    }

    $path = $directory . DIRECTORY_SEPARATOR . basename($filename);
    if (is_file($path) && !@unlink($path)) {
        throw new RuntimeException('The audio file could not be removed from storage.');
    }
}
