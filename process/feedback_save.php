<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST requests only.']);
    exit;
}

$wordId = filter_input(INPUT_POST, 'word_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$feedbackType = trim($_POST['feedback_type'] ?? 'Comment');
$name = trim($_POST['respondent_name'] ?? '');
$type = trim($_POST['respondent_type'] ?? 'Other');
$comments = trim($_POST['comments'] ?? '');

$allowedFeedbackTypes = ['Comment', 'Spelling', 'Definition', 'Example', 'Suggestion'];
$allowedRespondentTypes = ['Tourist', 'Non-Kalinga Speaker', 'Native Speaker', 'Student', 'Other'];

if (!$wordId || !$rating || $rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please select a valid rating.']);
    exit;
}
if (!in_array($feedbackType, $allowedFeedbackTypes, true)) $feedbackType = 'Comment';
if (!in_array($type, $allowedRespondentTypes, true)) $type = 'Other';
if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);
if ($comments === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a comment.']);
    exit;
}
if (mb_strlen($comments) > 2000) $comments = mb_substr($comments, 0, 2000);

$stmt = $pdo->prepare("SELECT w.id, w.dialect_term, d.name AS dialect_name FROM words w INNER JOIN dialects d ON d.id = w.dialect_id WHERE w.id = ? AND w.status = 'Published'");
$stmt->execute([$wordId]);
$word = $stmt->fetch();
if (!$word) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'That dictionary word could not be found.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO feedback (word_id, feedback_type, respondent_name, respondent_type, rating, comments) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$wordId, $feedbackType, $name !== '' ? $name : null, $type, $rating, $comments]);
    echo json_encode(['success' => true, 'message' => 'Thank you! Your feedback on this word has been submitted.']);
} catch (Throwable $e) {
    error_log('Word feedback save failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feedback could not be saved. Please try again.']);
}
