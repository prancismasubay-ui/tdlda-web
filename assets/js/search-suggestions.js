function suggestionSearchKey(value) {
  return value.trim().toLowerCase().replace(/[- ]/g, '');
}

function dictionarySuggestions(words, query) {
  const normalized = suggestionSearchKey(query);
  if (!normalized) return [];
  const suggestions = new Map();
  for (const word of words) {
    const candidates = [word.dialect_term,
      ...(word.definitions || []).map(definition => definition.definition_english),
      ...(word.synonyms || [])];
    for (const value of candidates) {
      if (typeof value !== 'string') continue;
      const text = value.trim();
      const key = text.toLowerCase();
      if (suggestionSearchKey(text).includes(normalized) && !suggestions.has(key)) {
        suggestions.set(key, { text, dialect: word.dialect_name || '' });
      }
    }
  }
  return Array.from(suggestions.values()).sort((a, b) =>
    Number(suggestionSearchKey(b.text).startsWith(normalized)) -
    Number(suggestionSearchKey(a.text).startsWith(normalized)) || a.text.localeCompare(b.text)
  ).slice(0, 8);
}

if (typeof module !== 'undefined') module.exports = { dictionarySuggestions };

if (typeof document !== 'undefined') {
  const input = document.getElementById('dictionarySearch');
  const list = document.getElementById('searchSuggestions');
  let timer;
  let controller;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    if (controller) controller.abort();
    list.replaceChildren();
    const query = input.value.trim();
    if (!query) return;
    controller = new AbortController();
    const signal = controller.signal;
    timer = setTimeout(async () => {
      const params = new URLSearchParams({ q: query, limit: '20' });
      for (const filter of ['dialect', 'category']) {
        const value = new URL(window.location.href).searchParams.get(filter);
        if (value) params.set(filter, value);
      }
      try {
        const response = await fetch('api/words.php?' + params, { signal });
        if (!response.ok) throw new Error('Suggestion request failed: ' + response.status);
        const result = await response.json();
        if (signal.aborted || input.value.trim() !== query) return;
        list.replaceChildren(...dictionarySuggestions(result.data, query).map(suggestion => {
          const option = document.createElement('option');
          option.value = suggestion.text;
          option.label = suggestion.dialect;
          return option;
        }));
      } catch (error) {
        if (!signal.aborted && error.name !== 'AbortError') {
          list.replaceChildren();
          console.warn('Dictionary suggestions unavailable', error);
        }
      }
    }, 300);
  });
}
