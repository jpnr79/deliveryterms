<?php
// Simulate protocol generation without using Document->add to avoid full GLPI bootstrap issues.
// This allocates a per-year sequence, generates a simple PDF using Dompdf, writes the file to GLPI upload root,
// inserts a glpi_documents row and a glpi_plugin_deliveryterms_protocols row, and links them via glpi_documents_items.

require __DIR__ . '/integration_check_sequence.php'; // provides mysqli connection & allocation helper

$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

// Parameters
$doc_config_id = 3; // template id
$user_id = 2; // owner
$author_name = 'glpi';
$template_name = 'Termo_de_Entrega';

// Allocate sequence by reusing logic
$year = date('Y');
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_sequence (`year` INT(4) UNSIGNED NOT NULL, `last` INT(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`year`)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
$res = $mysqli->query("SELECT year FROM glpi_plugin_deliveryterms_sequence WHERE `year` = ${year}");
if ($res && $res->num_rows === 0) {
    $c = $mysqli->query("SELECT COUNT(*) AS c FROM glpi_plugin_deliveryterms_protocols WHERE gen_date BETWEEN '${year}-01-01' AND '${year}-12-31'");
    $count = ($c) ? (int)$c->fetch_assoc()['c'] : 0;
    $mysqli->query("INSERT INTO glpi_plugin_deliveryterms_sequence (`year`, `last`) VALUES (${year}, ${count}) ON DUPLICATE KEY UPDATE `last` = `last`;");
}
$mysqli->query("UPDATE glpi_plugin_deliveryterms_sequence SET `last` = `last` + 1 WHERE `year` = ${year}");
$nr = $mysqli->query("SELECT `last` FROM glpi_plugin_deliveryterms_sequence WHERE `year` = ${year}");
$row = $nr->fetch_assoc();
$seqnum = (int)$row['last'];

$doc_name = $template_name . '-' . $year . '-' . sprintf('%04d', $seqnum) . '.pdf';

// Generate simple PDF using Dompdf
require_once __DIR__ . '/../dompdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
$options = new Options();
$options->set('defaultFont', 'dejavusans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml('<h1>Test Protocol ' . $doc_name . '</h1>');
$dompdf->setPaper('A4');
$dompdf->render();
$output = $dompdf->output();

$upload_root = '/var/www/glpi/files';
@mkdir($upload_root, 0755, true);
$upload_path = $upload_root . '/' . $doc_name;
file_put_contents($upload_path, $output);

// insert a minimal document row
$now = date('Y-m-d H:i:s');
$entities_id = 0; $is_recursive = 0; $filename = $mysqli->real_escape_string($doc_name); $filepath = '';
$documentcategories_id = 0; $mime = 'application/pdf'; $comment = $mysqli->real_escape_string('Simulated import'); $users_id = $user_id;
$doc_sql = "INSERT INTO glpi_documents (entities_id, is_recursive, name, filename, filepath, documentcategories_id, mime, date_mod, users_id, comment, date_creation) VALUES (${entities_id}, ${is_recursive}, '${filename}', '${filename}', '${filepath}', ${documentcategories_id}, '${mime}', '${now}', ${users_id}, '${comment}', '${now}')";
$mysqli->query($doc_sql);
$doc_id = $mysqli->insert_id;

// insert protocol row
$name_e = $mysqli->real_escape_string($doc_name);
$author_e = $mysqli->real_escape_string($author_name);
$template_e = $mysqli->real_escape_string($template_name);
$protocol_sql = "INSERT INTO glpi_plugin_deliveryterms_protocols (name, gen_date, author, user_id, document_id, document_type) VALUES ('${name_e}', '${now}', '${author_e}', ${user_id}, ${doc_id}, '${template_e}')";
$mysqli->query($protocol_sql);
$protocol_id = $mysqli->insert_id;

// link document to user in glpi_documents_items
$mysqli->query("INSERT INTO glpi_documents_items (documents_id, items_id, itemtype, users_id, date_creation, date_mod, date) VALUES (${doc_id}, ${user_id}, 'User', ${user_id}, '${now}', '${now}', '${now}')");

echo "Simulated generation complete:\n";
echo "Document id: ${doc_id}, Protocol id: ${protocol_id}, Filename: ${doc_name}\n";
if (file_exists($upload_path)) { echo "File exists at ${upload_path}\n"; } else { echo "File missing: ${upload_path}\n"; }

// Support cleanup mode: delete the protocol, document and file that were created when --cleanup is passed
if (in_array('--cleanup', $argv ?? [])) {
    $mysqli->query("DELETE FROM glpi_documents_items WHERE documents_id = ${doc_id}");
    $mysqli->query("DELETE FROM glpi_plugin_deliveryterms_protocols WHERE id = ${protocol_id}");
    $mysqli->query("DELETE FROM glpi_documents WHERE id = ${doc_id}");
    if (file_exists($upload_path)) { unlink($upload_path); }
    echo "Cleanup done: removed protocol ${protocol_id}, document ${doc_id} and file ${upload_path}\n";
}

?>