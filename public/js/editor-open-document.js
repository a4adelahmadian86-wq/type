(() => {
  'use strict';

  const readPayload = () => {
    const node = document.getElementById('farast-open-document');
    if (!node) return null;
    try {
      return JSON.parse(node.textContent || 'null');
    } catch (_) {
      return null;
    }
  };

  const applyToDom = (doc) => {
    if (!doc || !doc.id) return;
    const editor = document.getElementById('editor');
    const title = document.getElementById('docTitle');
    const saveState = document.getElementById('saveState');
    const statusWords = document.getElementById('statusWords');

    if (title && doc.title) title.value = doc.title;
    if (editor && typeof doc.content === 'string') {
      editor.innerHTML = doc.content || '<p><br></p>';
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (saveState) saveState.textContent = 'بارگذاری‌شده از اسناد من';
    if (statusWords && editor) {
      const t = (editor.innerText || '').trim();
      statusWords.textContent = t ? String(t.split(/\s+/u).filter(Boolean).length) : '0';
    }

    window.FarastOpenDocument = doc;
    window.FarastCurrentDocumentId = doc.id;
  };

  const patchFetch = (docId) => {
    if (!docId || window.__farastOpenDocFetchPatched) return;
    window.__farastOpenDocFetchPatched = true;
    const nativeFetch = window.fetch.bind(window);

    window.fetch = (input, init = {}) => {
      try {
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        if (url.includes('/editor/save') || url.includes('/editor/feedback')) {
          const headers = init.headers || {};
          const isJson =
            (headers['Content-Type'] || headers['content-type'] || '').includes('application/json');
          if (isJson && typeof init.body === 'string') {
            const body = JSON.parse(init.body);
            if (!body.document_id) {
              body.document_id = docId;
              init = { ...init, body: JSON.stringify(body) };
            }
          }
        }
      } catch (_) {}
      return nativeFetch(input, init);
    };
  };

  const boot = () => {
    const doc = readPayload();
    if (!doc || !doc.id) return;
    applyToDom(doc);
    patchFetch(doc.id);

    // Retry once after other editor scripts finish mutating the DOM.
    setTimeout(() => applyToDom(doc), 50);
    setTimeout(() => applyToDom(doc), 300);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
