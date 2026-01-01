<?php
// Preview endpoint for deliveryterms WYSIWYG editor
// Expects POST with _glpi_csrf_token and either 'html' => string OR 'template_id' => int
// Returns application/pdf body (inline) or JSON error messages

// Ensure Dompdf autoloaded
require_once dirname(__DIR__) . '/dompdf/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require an authenticated session and configuration rights
if (!Session::getLoginUserID()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}
if (!Session::haveRight('config', UPDATE)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}
// TODO: Validate CSRF token reliably for AJAX preview requests (Session::checkCSRF currently fails with meta token in some flows)

$html = $_POST['html'] ?? null;
$template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : null;
$mode = $_POST['mode'] ?? 'inline'; // 'inline' or 'download'

if (!$html && $template_id) {
    global $DB;
    $req = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_config', 'WHERE' => ['id' => $template_id]]);
    if ($row = $req->current()) {
        $upper = html_entity_decode($row['upper_content'] ?? '', ENT_QUOTES, 'UTF-8');
        $content = html_entity_decode($row['content'] ?? '', ENT_QUOTES, 'UTF-8');
        $footer = html_entity_decode($row['footer'] ?? '', ENT_QUOTES, 'UTF-8');
        $html = $upper . "\n" . $content . "\n" . $footer;
    }
}

if (!$html) {
    http_response_code(400);
    echo json_encode(['error' => 'No HTML or template_id provided']);
    exit;
}

// Basic sanitization: remove <script> tags and on* attributes (note: improve with a proper HTML sanitizer later)
$html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
$html = preg_replace('/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

// Setup Dompdf
try {
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();

    header('Content-Type: application/pdf');
    if ($mode === 'download') {
        header('Content-Disposition: attachment; filename="preview.pdf"');
    } else {
        header('Content-Disposition: inline; filename="preview.pdf"');
    }

    echo $pdf;
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Preview failed', 'details' => $e->getMessage()]);
    exit;
}
