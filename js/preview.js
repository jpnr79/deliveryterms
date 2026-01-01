(function () {
  // Simple preview helper: insert placeholder and preview current template
  function insertPlaceholder(placeholder, editorId) {
    if (window.tinymce) {
      var ed = window.tinymce.get(editorId);
      if (ed) {
        ed.execCommand('mceInsertContent', false, placeholder);
        return;
      }
    }
    // fallback to textarea insert
    var ta = document.getElementById(editorId);
    if (ta) {
      var start = ta.selectionStart || 0;
      var end = ta.selectionEnd || 0;
      ta.value = ta.value.substring(0, start) + placeholder + ta.value.substring(end);
    }
  }

  async function openPreview(html) {
    // Send HTML to preview endpoint via fetch and display PDF in modal
    var token = document.querySelector('input[name="_glpi_csrf_token"]');
    var tokenVal = token
      ? token.value
      : document.querySelector('meta[property="glpi:csrf_token"]')
      ? document.querySelector('meta[property="glpi:csrf_token"]').getAttribute('content')
      : '';

    var fd = new FormData();
    fd.append('_glpi_csrf_token', tokenVal);
    fd.append('html', html);

    // create or reuse modal
    var existing = document.getElementById('deliverytermsPreviewModal');
    if (!existing) {
      var modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'deliverytermsPreviewModal';
      modal.tabIndex = -1;
      modal.innerHTML = `<div class='modal-dialog modal-xl modal-dialog-centered' style='max-width:95%;height:95%;'><div class='modal-content' style='height:95%;'><div class='modal-header'><h5 class='modal-title'>Preview</h5><button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button></div><div class='modal-body p-0' style='height:calc(100% - 56px);'><div id='deliverytermsPreviewBody' style='width:100%;height:100%;display:flex;align-items:center;justify-content:center;'><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div></div></div></div>`;
      document.body.appendChild(modal);
    }

    var modalEl = document.getElementById('deliverytermsPreviewModal');
    var bodyEl = document.getElementById('deliverytermsPreviewBody');

    // show loading spinner
    bodyEl.innerHTML =
      '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

    try {
      var resp = await fetch(window.location.origin + '/plugins/deliveryterms/front/preview.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      });

      if (!resp.ok) {
        var txt = await resp.text();
        bodyEl.innerHTML = '<div class="p-3">Error: ' + resp.status + ' - ' + txt + '</div>';
      } else {
        var contentType = resp.headers.get('content-type') || '';
        var blob = await resp.blob();
        if (contentType.indexOf('application/pdf') !== -1) {
          var url = URL.createObjectURL(blob);
          // insert iframe
          bodyEl.innerHTML =
            '<iframe src="' +
            url +
            '" style="width:100%;height:100%;border:0;" allowfullscreen></iframe>';

          // cleanup when modal hidden
          modalEl.addEventListener('hidden.bs.modal', function cleanup() {
            try {
              URL.revokeObjectURL(url);
            } catch (e) {}
            modalEl.removeEventListener('hidden.bs.modal', cleanup);
          });
        } else {
          // display textual response
          var text = await blob.text();
          bodyEl.innerHTML = '<div class="p-3">Preview returned: ' + text + '</div>';
        }
      }
    } catch (e) {
      bodyEl.innerHTML = '<div class="p-3">Preview failed: ' + e.message + '</div>';
    }

    // show modal using Bootstrap's JS API if available
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
      var modalInstance = new window.bootstrap.Modal(modalEl);
      modalInstance.show();
    } else {
      // fallback: make visible
      modalEl.style.display = 'block';
    }
  }

  function getCombinedHtml() {
    var upper = '';
    var content = '';
    var footer = '';
    if (window.tinymce) {
      var edUpper = window.tinymce.get('template_uppercontent');
      var edContent = window.tinymce.get('template_content');
      if (edUpper) {
        upper = edUpper.getContent();
      } else {
        var ta = document.getElementById('template_uppercontent');
        if (ta) upper = ta.value;
      }
      if (edContent) {
        content = edContent.getContent();
      } else {
        var ta2 = document.getElementById('template_content');
        if (ta2) content = ta2.value;
      }
    } else {
      var ta = document.getElementById('template_uppercontent');
      if (ta) upper = ta.value;
      var ta2 = document.getElementById('template_content');
      if (ta2) content = ta2.value;
    }
    var tf = document.querySelector('textarea[name="footer_text"]');
    if (tf) footer = tf.value;
    return (upper || '') + '\n' + (content || '') + '\n' + (footer || '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Add placeholder dropdown and preview button next to the lower content field label
    var lowerLabel = Array.from(document.querySelectorAll('td')).find(function (td) {
      return td.textContent && td.textContent.trim().startsWith('Lower Content');
    });
    if (lowerLabel) {
      var container = document.createElement('div');
      container.className = 'mb-2 d-flex gap-2';
      var placeholders = ['{owner}', '{YYYY}', '{seq}', '{docmodel}', '{date}'];
      var select = document.createElement('select');
      select.className = 'form-select form-select-sm';
      var opt = document.createElement('option');
      opt.value = '';
      opt.text = 'Insert placeholder...';
      select.appendChild(opt);
      placeholders.forEach(function (ph) {
        var o = document.createElement('option');
        o.value = ph;
        o.text = ph;
        select.appendChild(o);
      });
      select.addEventListener('change', function () {
        if (this.value) {
          insertPlaceholder(this.value, 'template_content');
          this.selectedIndex = 0;
        }
      });
      var previewBtn = document.createElement('button');
      previewBtn.type = 'button';
      previewBtn.className = 'btn btn-outline-primary btn-sm';
      previewBtn.textContent = 'Preview';
      previewBtn.addEventListener('click', function () {
        var html = getCombinedHtml();
        openPreview(html);
      });
      container.appendChild(select);
      container.appendChild(previewBtn);
      // insert container before the content textarea
      var contentTd =
        lowerLabel.nextElementSibling || lowerLabel.parentElement.querySelector('td:nth-child(2)');
      if (contentTd) contentTd.insertBefore(container, contentTd.firstChild);
    }
  });
})();
