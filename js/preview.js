(function(){
  // Simple preview helper: insert placeholder and preview current template
  function insertPlaceholder(placeholder, editorId) {
    if (window.tinymce) {
      var ed = window.tinymce.get(editorId);
      if (ed) { ed.execCommand('mceInsertContent', false, placeholder); return; }
    }
    // fallback to textarea insert
    var ta = document.getElementById(editorId);
    if (ta) {
      var start = ta.selectionStart || 0;
      var end = ta.selectionEnd || 0;
      ta.value = ta.value.substring(0, start) + placeholder + ta.value.substring(end);
    }
  }

  function openPreview(html) {
    // create a form to POST to preview endpoint and open in new tab
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.origin + '/plugins/deliveryterms/front/preview.php';
    form.target = '_blank';
    var token = document.querySelector('input[name="_glpi_csrf_token"]');
    if (token) {
      var t = document.createElement('input'); t.type = 'hidden'; t.name = '_glpi_csrf_token'; t.value = token.value; form.appendChild(t);
    }
    var input = document.createElement('input'); input.type = 'hidden'; input.name = 'html'; input.value = html; form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  function getCombinedHtml() {
    var upper = '';
    var content = '';
    var footer = '';
    if (window.tinymce) {
      var edUpper = window.tinymce.get('template_uppercontent');
      var edContent = window.tinymce.get('template_content');
      if (edUpper) { upper = edUpper.getContent(); } else { var ta = document.getElementById('template_uppercontent'); if (ta) upper = ta.value; }
      if (edContent) { content = edContent.getContent(); } else { var ta2 = document.getElementById('template_content'); if (ta2) content = ta2.value; }
    } else {
      var ta = document.getElementById('template_uppercontent'); if (ta) upper = ta.value;
      var ta2 = document.getElementById('template_content'); if (ta2) content = ta2.value;
    }
    var tf = document.querySelector('textarea[name="footer_text"]'); if (tf) footer = tf.value;
    return (upper || '') + '\n' + (content || '') + '\n' + (footer || '');
  }

  document.addEventListener('DOMContentLoaded', function(){
    // Add placeholder dropdown and preview button next to the lower content field label
    var lowerLabel = Array.from(document.querySelectorAll('td')).find(function(td){ return td.textContent && td.textContent.trim().startsWith('Lower Content'); });
    if (lowerLabel) {
      var container = document.createElement('div'); container.className = 'mb-2 d-flex gap-2';
      var placeholders = ['{owner}','{YYYY}','{seq}','{docmodel}','{date}'];
      var select = document.createElement('select'); select.className = 'form-select form-select-sm';
      var opt = document.createElement('option'); opt.value = ''; opt.text = 'Insert placeholder...'; select.appendChild(opt);
      placeholders.forEach(function(ph){ var o = document.createElement('option'); o.value = ph; o.text = ph; select.appendChild(o); });
      select.addEventListener('change', function(){ if (this.value) { insertPlaceholder(this.value, 'template_content'); this.selectedIndex = 0; } });
      var previewBtn = document.createElement('button'); previewBtn.type = 'button'; previewBtn.className = 'btn btn-outline-primary btn-sm'; previewBtn.textContent = 'Preview';
      previewBtn.addEventListener('click', function(){ var html = getCombinedHtml(); openPreview(html); });
      container.appendChild(select); container.appendChild(previewBtn);
      // insert container before the content textarea
      var contentTd = lowerLabel.nextElementSibling || lowerLabel.parentElement.querySelector('td:nth-child(2)');
      if (contentTd) contentTd.insertBefore(container, contentTd.firstChild);
    }
  });
})();