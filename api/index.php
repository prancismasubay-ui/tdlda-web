<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>API Docs - Kalinga Dialects Learning Dictionary</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  body { padding: 40px; max-width: 820px; margin: 0 auto; }
  code { background:#f3f4f6; padding:2px 7px; border-radius:5px; font-size:13px; }
  pre { background:#111827; color:#e5e7eb; padding:16px 18px; border-radius:8px; overflow-x:auto; font-size:12.5px; }
  h2 { margin-top:32px; margin-bottom:8px; font-size:18px; }
  table { width:100%; border-collapse:collapse; margin: 10px 0 20px; }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid var(--border-color); font-size:13px; }
</style>
</head>
<body>
  <h1>Kalinga Dialects Learning Dictionary &mdash; REST API</h1>
  <p style="color:var(--text-muted);">Read-only, no API key required. Returns JSON. CORS enabled for all origins &mdash; suitable for a mobile app or other frontend to consume.</p>

  <h2>GET /api/dialects.php</h2>
  <p>List all dialects. Optional <code>?status=Active</code> or <code>?status=Planned</code>.</p>

  <h2>GET /api/categories.php</h2>
  <p>List all word categories.</p>

  <h2>GET /api/parts_of_speech.php</h2>
  <p>List all part-of-speech tags.</p>

  <h2>GET /api/words.php</h2>
  <p>Search/list published words with nested definitions and synonyms.</p>
  <table>
    <tr><th>Param</th><th>Description</th></tr>
    <tr><td><code>q</code></td><td>Search text &mdash; matches the term or any definition</td></tr>
    <tr><td><code>dialect</code></td><td>Filter by dialect id</td></tr>
    <tr><td><code>category</code></td><td>Filter by category id</td></tr>
    <tr><td><code>pos</code></td><td>Filter by part_of_speech id</td></tr>
    <tr><td><code>limit</code></td><td>Default 50, max 200</td></tr>
    <tr><td><code>offset</code></td><td>Default 0, for pagination</td></tr>
  </table>
  <pre>GET /api/words.php?q=mano

{
  "meta": { "total": 1, "limit": 50, "offset": 0 },
  "data": [
    {
      "id": 1,
      "dialect_term": "Mano",
      "etymology": "...",
      "dialect_name": "Tinglayan",
      "category_name": "Greetings",
      "part_of_speech_name": "Greeting",
      "definitions": [
        { "definition_english": "Hello / Greetings",
          "example_sentence_dialect": "Mano, kumusta ka?",
          "example_sentence_english": "Hello, how are you?" }
      ],
      "synonyms": [],
      "audio_url": null
    }
  ]
}</pre>

  <h2>GET /api/word.php?id=1</h2>
  <p>Full detail for a single published word.</p>

  <p style="margin-top:30px;"><a href="../index.php">&larr; Back to the dictionary</a></p>
</body>
</html>
