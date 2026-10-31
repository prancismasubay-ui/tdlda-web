function suggestionSearchKey(value) {
  return value.trim().toLowerCase().replace(/[- ]/g, '');
}

function dictionarySuggestions(words, query) {
  const normalized = suggestionSearchKey(query);
  if (!normalized) return [];
  const suggestions = new Map();
  for (const word of words) {
    if (typeof word.dialect_term !== 'string' || !word.dialect_term.trim()) continue;
    const candidates = [word.dialect_term, word.english_translation,
      ...(word.definitions || []).map(definition => definition.definition_english),
      ...(word.synonyms || [])];
    if (candidates.some(value => typeof value === 'string' && suggestionSearchKey(value).includes(normalized))) {
      const text = word.dialect_term.trim();
      const dialect = word.dialect_name || '';
      const key = JSON.stringify([text.toLowerCase(), dialect.toLowerCase()]);
      if (!suggestions.has(key)) suggestions.set(key, { text, dialect });
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
  let suggestions = [];
  let activeIndex = -1;

  function closeSuggestions() {
    clearTimeout(timer);
    if (controller) controller.abort();
    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    activeIndex = -1;
  }

  function choose(index) {
    input.value = suggestions[index].text;
    closeSuggestions();
    input.form.requestSubmit();
  }

  function highlight(index) {
    activeIndex = index;
    Array.from(list.children).forEach((option, position) => option.setAttribute('aria-selected', String(position === index)));
    if (index >= 0) {
      input.setAttribute('aria-activedescendant', list.children[index].id);
      list.children[index].scrollIntoView({ block: 'nearest' });
    }
  }

  input.addEventListener('keydown', event => {
    if (event.key === 'Escape') { closeSuggestions(); return; }
    if (list.hidden || !suggestions.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      const step = event.key === 'ArrowDown' ? 1 : -1;
      highlight(activeIndex < 0 ? (step > 0 ? 0 : suggestions.length - 1) :
        (activeIndex + step + suggestions.length) % suggestions.length);
    } else if (event.key === 'Enter' && activeIndex >= 0) {
      event.preventDefault();
      choose(activeIndex);
    }
  });
  input.addEventListener('blur', event => {
    if (!list.contains(event.relatedTarget)) closeSuggestions();
  });
  list.addEventListener('focusout', event => {
    if (event.relatedTarget !== input && !list.contains(event.relatedTarget)) closeSuggestions();
  });
  input.addEventListener('input', () => {
    closeSuggestions();
    suggestions = [];
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
        suggestions = dictionarySuggestions(result.data, query);
        list.replaceChildren(...suggestions.map((suggestion, index) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.id = 'search-suggestion-' + index;
          option.className = 'search-suggestion';
          option.tabIndex = -1;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', 'false');
          const word = document.createElement('span');
          word.className = 'search-suggestion-word';
          word.textContent = suggestion.text;
          option.append(word);
          if (suggestion.dialect) {
            const dialect = document.createElement('span');
            dialect.className = 'search-suggestion-dialect';
            dialect.textContent = suggestion.dialect;
            option.append(dialect);
          }
          option.addEventListener('mousedown', event => event.preventDefault());
          option.addEventListener('click', () => choose(index));
          return option;
        }));
        list.hidden = suggestions.length === 0;
        input.setAttribute('aria-expanded', String(!list.hidden));
      } catch (error) {
        if (!signal.aborted && error.name !== 'AbortError') {
          closeSuggestions();
          list.replaceChildren();
          console.warn('Dictionary suggestions unavailable', error);
        }
      }
    }, 300);
  });
}
