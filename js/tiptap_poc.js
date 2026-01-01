(function () {
  // Minimal TipTap PoC: dynamically load UMD builds and initialize a simple editor
  function initEditor(textarea) {
    // The bundled initializer is exposed at window.deliverytermsInitTipTap by the built bundle.
    if (typeof window.deliverytermsInitTipTap === 'function') {
      try {
        var ed = window.deliverytermsInitTipTap(textarea);
        return ed;
      } catch (e) {
        console.warn('TipTap PoC initializer failed:', e);
        return null;
      }
    }

    console.warn('TipTap bundle not loaded; editor unavailable');
    return null;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Add toggle control near the content textarea
    var ta = document.querySelector('textarea[name="template_content"]');
    if (!ta) return;

    var container = document.createElement('div');
    container.className = 'mb-2';
    var label = document.createElement('label');
    label.textContent = 'Editor (PoC TipTap):';
    label.className = 'form-label me-2';
    var toggle = document.createElement('input');
    toggle.type = 'checkbox';
    toggle.id = 'tiptap_poc_toggle';
    toggle.className = 'form-check-input me-2';
    var toggLabel = document.createElement('label');
    toggLabel.setAttribute('for', 'tiptap_poc_toggle');
    toggLabel.className = 'form-check-label';
    toggLabel.textContent = 'Use TipTap PoC editor';

    var info = document.createElement('small');
    info.className = 'form-text text-muted d-block';
    info.textContent =
      'PoC: TipTap editor for WYSIWYG blocks. Toggle to switch (editor syncs into the existing content field).';

    container.appendChild(label);
    container.appendChild(toggle);
    container.appendChild(toggLabel);
    container.appendChild(info);
    ta.parentNode.insertBefore(container, ta);

    var editorInstance = null;

    toggle.addEventListener('change', function () {
      if (this.checked) {
        // initialize editor and hide textarea
        initEditor(ta).then(function (ed) {
          if (ed) {
            editorInstance = ed;
            ta.style.display = 'none';
          } else {
            toggle.checked = false;
            alert('Failed to initialize TipTap PoC (see console)');
          }
        });
      } else {
        // sync content and show textarea
        if (window._deliveryterms_tiptap_editor) {
          ta.value = window._deliveryterms_tiptap_editor.getHTML() || ta.value;
        }
        ta.style.display = '';
      }
    });

    // Ensure on form submit we sync content
    var form = ta.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        if (window._deliveryterms_tiptap_editor) {
          ta.value = window._deliveryterms_tiptap_editor.getHTML();
        }
      });
    }
  });
})();
