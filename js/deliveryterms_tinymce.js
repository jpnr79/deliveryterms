// Enhance TinyMCE used in Delivery Terms plugin forms to expose table editing helpers
(function () {
  function addTableMenu(editor) {
    if (!editor) return;
    try {
      // Add a simple menu button grouping common table actions
      editor.ui.registry.addMenuButton('deliveryterms_table_menu', {
        text: 'Table',
        fetch: function (callback) {
          callback([
            {
              type: 'menuitem',
              text: 'Insert row before',
              onAction: function () {
                editor.execCommand('mceTableInsertRowBefore');
              },
            },
            {
              type: 'menuitem',
              text: 'Insert row after',
              onAction: function () {
                editor.execCommand('mceTableInsertRowAfter');
              },
            },
            {
              type: 'menuitem',
              text: 'Delete row',
              onAction: function () {
                editor.execCommand('mceTableDeleteRow');
              },
            },
            {
              type: 'menuitem',
              text: 'Insert column before',
              onAction: function () {
                editor.execCommand('mceTableInsertColBefore');
              },
            },
            {
              type: 'menuitem',
              text: 'Insert column after',
              onAction: function () {
                editor.execCommand('mceTableInsertColAfter');
              },
            },
            {
              type: 'menuitem',
              text: 'Delete column',
              onAction: function () {
                editor.execCommand('mceTableDeleteCol');
              },
            },
          ]);
        },
      });

      // Try to insert the button into existing toolbar if possible
      // Only add if not already present
      if (!(editor.settings.toolbar || '').includes('deliveryterms_table_menu')) {
        editor.settings.toolbar = 'deliveryterms_table_menu | ' + (editor.settings.toolbar || '');
      }
    } catch (e) {
      console.debug('[deliveryterms] Could not enhance TinyMCE editor:', e.message);
    }
  }

  function enhanceExistingEditors() {
    if (!window.tinymce) return;
    window.tinymce.editors.forEach(function (ed) {
      addTableMenu(ed);
    });
  }

  // When an editor is added, TinyMCE fires 'AddEditor' event on the window
  if (window.tinymce) {
    enhanceExistingEditors();
    window.addEventListener('AddEditor', function (e) {
      try {
        addTableMenu(window.tinymce.get(e.detail));
      } catch (e) {
        /* ignore */
      }
    });
  }

  // As a fallback, poll briefly for tinymce to appear (covers late initialization)
  var attempts = 0;
  var poll = setInterval(function () {
    if (window.tinymce) {
      enhanceExistingEditors();
      clearInterval(poll);
    }
    if (++attempts > 10) {
      clearInterval(poll);
    }
  }, 500);
})();
