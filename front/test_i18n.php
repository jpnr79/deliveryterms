<?php
// Diagnostic endpoint removed — no longer required.
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found";
exit;
setlocale(LC_ALL, 'pt_PT.utf8');
bindtextdomain('deliveryterms', __DIR__ . '/../locales');
bind_textdomain_codeset('deliveryterms', 'UTF-8');
textdomain('deliveryterms');
$keys = [
    'Font', 'Font size', 'Word breaking', 'On', 'Off', 'Upper Content', 'Logo width (px)', 'Logo height (px)', 'Enable email autosending', 'Delete file'
];
$out = [];
foreach ($keys as $k) {
    $out[$k] = dgettext('deliveryterms', $k);
}
// Also include results via GLPI's __() wrapper for comparison
$out['__ Font'] = __('Font', 'deliveryterms');
$out['__ Font size'] = __('Font size', 'deliveryterms');
$out['__ Ciudad'] = __('Ciudad', 'deliveryterms');

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
