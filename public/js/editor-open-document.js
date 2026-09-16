(() => {
  'use strict';

  const boot = () => {
    const payloadNode = document.getElementById('farast-open-document');
    if (!payloadNode) return;

    let doc;
    try {
      doc = JSON.parse(payloadNode.textContent || 'null');
    } catch (_) {
      return;
    }
    if (!doc || !doc.id) return;

    const editor = document.getElementById('editor');
    const title = document.getElementById('docTitle');
    const saveState = document.getElementById('saveState');

    if (title && doc.title) title.value = doc.title;
    if (editor && typeof doc.content === 'string') {
      editor.innerHTML = doc.content || '<p><br></p>';
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    }

    window.FarastCurrentDocumentId = doc.id;
    window.FarastOpenDocument = doc;

    if (saveState) {
      saveState.textContent = 'بارگذاری‌شده از اسناد من';
    }

    // Common pattern in FARAST editor scripts: keep a global document id for save/export.
    try {
      if (window.localStorage) {
        localStorage.setItem('farast.lastDocumentId', String(doc.id));
      }
    } catch (_) {}
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
