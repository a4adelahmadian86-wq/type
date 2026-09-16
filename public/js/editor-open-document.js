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
      const n = t ? t.split(/\s+/u).filter(Boolean).length : 0;
      statusWords.textContent = String(n);
    }

    window.FarastOpenDocument = doc;
    window.FarastCurrentDocumentId = doc.id;
  };

  /**
   * The editor blade keeps docId in a closed IIFE. We cannot assign that let
   * from outside, so we intercept save/feedback/export paths.
   */
  const installHooks = (docId) => {
    if (!docId || window.__farastOpenDocHooks) return;
    window.__farastOpenDocHooks = true;

    const nativeFetch = window.fetch.bind(window);
    window.fetch = (input, init = {}) => {
      try {
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        if (url.includes('/editor/save') || url.includes('/editor/feedback')) {
          const headers = init.headers || {};
          const ct = headers['Content-Type'] || headers['content-type'] || '';
          if (String(ct).includes('application/json') && typeof init.body === 'string') {
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

    // Override saveNow click path: blade registers listener that exits when docId is null.
    const saveNowBtn = document.getElementById('saveNow');
    if (saveNowBtn) {
      saveNowBtn.addEventListener(
        'click',
        async (event) => {
          const editor = document.getElementById('editor');
          if (!editor) return;
          event.stopImmediatePropagation();
          try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const res = await nativeFetch('/editor/save', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
              },
              body: JSON.stringify({ document_id: docId, content: editor.innerHTML }),
            });
            const j = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(j.message || 'ذخیره ناموفق');
            const saveState = document.getElementById('saveState');
            const statusText = document.getElementById('statusText');
            if (saveState) saveState.textContent = 'ذخیره شد';
            if (statusText) statusText.textContent = 'ذخیره شد';
          } catch (e) {
            const statusText = document.getElementById('statusText');
            if (statusText) statusText.textContent = e.message || 'ذخیره ناموفق';
          }
        },
        true
      );
    }

    // Export forms: ensure document_id is present.
    document.addEventListener(
      'submit',
      (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.action || !form.action.includes('/editor/export/')) return;
        let input = form.querySelector('input[name="document_id"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'document_id';
          form.appendChild(input);
        }
        if (!input.value) input.value = String(docId);
      },
      true
    );
  };

  const boot = () => {
    const doc = readPayload();
    if (!doc || !doc.id) return;
    applyToDom(doc);
    installHooks(doc.id);
    setTimeout(() => applyToDom(doc), 100);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
