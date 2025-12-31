<?php
// Diagnostic endpoint removed — no longer required.
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found";
exit;
$keys = [
    'Font', 'Font size', 'Word breaking', 'On', 'Off', 'Upper Content', 'Logo width (px)', 'Logo height (px)', 'Enable email autosending', 'Delete file'
];
$out = [];
foreach ($keys as $k) {
    $out[$k] = dgettext('deliveryterms', $k);
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
