<?php

function dictionary_search_clause(string $search): array
{
    $normalized = str_replace(['-', ' '], '', trim($search));
    // Separator-only input should search literally instead of matching every entry.
    $ignoreSeparators = $normalized !== '';
    $value = $ignoreSeparators ? $normalized : trim($search);
    $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value) . '%';
    $expressions = [];
    foreach (['w.dialect_term', 'wd.definition_english', 'ws.synonym_term'] as $column) {
        $expression = $ignoreSeparators ? "REPLACE(REPLACE($column, '-', ''), ' ', '')" : $column;
        $expressions[] = "LOWER($expression) LIKE LOWER(?) ESCAPE '!'";
    }
    return [
        'sql' => " AND ({$expressions[0]} OR EXISTS (
            SELECT 1 FROM word_definitions wd WHERE wd.word_id = w.id AND {$expressions[1]}
        ) OR EXISTS (
            SELECT 1 FROM word_synonyms ws WHERE ws.word_id = w.id AND {$expressions[2]}
        ))",
        'params' => [$pattern, $pattern, $pattern],
    ];
}
