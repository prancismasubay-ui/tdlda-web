<?php

function feedback_text(array $input, string $key, int $maxLength, string $default = ''): string
{
    $value = $input[$key] ?? $default;
    if (!is_string($value)) throw new InvalidArgumentException('Please enter valid text.', 422);
    $value = trim($value);
    $length = preg_match_all('/./us', $value);
    if ($length === false || $length > $maxLength) {
        throw new InvalidArgumentException('A feedback field is too long or contains invalid text.', 422);
    }
    return $value;
}

function save_word_feedback(PDO $pdo, array $input): void
{
    $wordId = filter_var($input['word_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    if ($wordId === false || $rating === false) {
        throw new InvalidArgumentException('Please select a valid word and a rating from 1 to 5.', 422);
    }
    $feedbackType = feedback_text($input, 'feedback_type', 30, 'Comment');
    $respondentType = feedback_text($input, 'respondent_type', 30, 'Other');
    $name = feedback_text($input, 'respondent_name', 100);
    $comments = feedback_text($input, 'comments', 2000);
    if ($comments === '') throw new InvalidArgumentException('Please enter a comment.', 422);
    if (!in_array($feedbackType, ['Comment', 'Spelling', 'Definition', 'Example', 'Suggestion'], true)) {
        throw new InvalidArgumentException('Please select a valid feedback type.', 422);
    }
    if (!in_array($respondentType, ['Tourist', 'Non-Kalinga Speaker', 'Native Speaker', 'Student', 'Other'], true)) {
        throw new InvalidArgumentException('Please select a valid respondent type.', 422);
    }
    $stmt = $pdo->prepare("SELECT id FROM words WHERE id = ? AND status = 'Published'");
    $stmt->execute([$wordId]);
    if (!$stmt->fetchColumn()) throw new InvalidArgumentException('That dictionary word could not be found.', 404);
    $stmt = $pdo->prepare('INSERT INTO feedback (word_id, feedback_type, respondent_name, respondent_type, rating, comments) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$wordId, $feedbackType, $name === '' ? null : $name, $respondentType, $rating, $comments]);
}
