import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

function initTipTapEditor(textarea) {
  if (window._deliveryterms_tiptap_initialized) return window._deliveryterms_tiptap_editor;

  const el = document.createElement('div');
  el.id = 'tiptap-poc-editor';
  el.className = 'tiptap-poc-editor';
  const toolbar = document.createElement('div');
  toolbar.className = 'tiptap-poc-toolbar';

  const btnHeader = document.createElement('button');
  btnHeader.type = 'button';
  btnHeader.className = 'btn btn-sm btn-outline-secondary me-1';
  btnHeader.textContent = 'Insert Header';
  btnHeader.addEventListener('click', function () {
    editor.chain().focus().insertContent('<h2>Header</h2>').run();
  });

  const select = document.createElement('select');
  select.className = 'form-select form-select-sm d-inline-block';
  select.style.width = '220px';
  const phOpt = ['', '{owner}', '{YYYY}', '{seq}', '{docmodel}', '{date}'];
  phOpt.forEach(function (p) {
    var o = document.createElement('option');
    o.value = p;
    o.text = p || 'Insert placeholder...';
    select.appendChild(o);
  });
  select.addEventListener('change', function () {
    if (this.value) {
      editor.chain().focus().insertContent(this.value).run();
      this.selectedIndex = 0;
    }
  });

  toolbar.appendChild(btnHeader);
  toolbar.appendChild(select);
  const wrapper = document.createElement('div');
  wrapper.appendChild(toolbar);
  wrapper.appendChild(el);

  textarea.parentNode.insertBefore(wrapper, textarea);

  const editor = new Editor({
    element: el,
    extensions: [StarterKit],
    content: textarea.value || '<p></p>',
    onUpdate: ({ editor }) => {
      textarea.value = editor.getHTML();
    },
  });

  window._deliveryterms_tiptap_initialized = true;
  window._deliveryterms_tiptap_editor = editor;
  return editor;
}

// Expose to global namespace for the PoC wrapper to call
window.deliverytermsInitTipTap = initTipTapEditor;

export default initTipTapEditor;
