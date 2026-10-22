<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST requests only.']);
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/word_feedback.php';

try {
    save_word_feedback($pdo, $_POST);
    echo json_encode(['success' => true, 'message' => 'Thank you! Your feedback on this word has been submitted.']);
} catch (InvalidArgumentException $e) {
    http_response_code($e->getCode());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Word feedback save failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feedback could not be saved. Please try again.']);
}
