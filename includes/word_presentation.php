<?php

function word_optional_text($value): ?string
{
    if ($value === null) return null;
    if (!is_string($value)) throw new InvalidArgumentException('Translation and pronunciation must be text.');
    $value = trim($value);
    $length = preg_match_all('/./us', $value);
    if ($length === false) throw new InvalidArgumentException('Translation and pronunciation must be valid UTF-8 text.');
    if ($length > 255) {
        throw new InvalidArgumentException('Translation and pronunciation must be at most 255 characters.');
    }
    return $value === '' ? null : $value;
}

function word_english_heading(array $word): string
{
    return trim($word['english_translation'] ?? '') ?: ($word['definitions'][0]['definition_english'] ?? '');
}

function word_text_key(string $text): string
{
    return strtolower(preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text));
}

function word_heading_translation(array $word): string
{
    $english = word_english_heading($word);
    return word_text_key($english) === word_text_key($word['dialect_term'] ?? '') ? '' : $english;
}

function word_definition_is_redundant(array $word, string $definition): bool
{
    return word_text_key($definition) === word_text_key(word_english_heading($word));
}
