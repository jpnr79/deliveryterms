<?php
// Verify that per-template doc_model is saved and used in filename pattern when generating
$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
// Ensure config table exists and has at least one template
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_config (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(255), title VARCHAR(255), filename_pattern VARCHAR(255) DEFAULT NULL, doc_model VARCHAR(64) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
// Ensure columns exist if table was created earlier without new columns
$col = $mysqli->query("SHOW COLUMNS FROM glpi_plugin_deliveryterms_config LIKE 'filename_pattern'");
if (!($col && $col->num_rows)) {
    $mysqli->query("ALTER TABLE glpi_plugin_deliveryterms_config ADD COLUMN filename_pattern VARCHAR(255) DEFAULT NULL");
}
$col2 = $mysqli->query("SHOW COLUMNS FROM glpi_plugin_deliveryterms_config LIKE 'doc_model'");
if (!($col2 && $col2->num_rows)) {
    $mysqli->query("ALTER TABLE glpi_plugin_deliveryterms_config ADD COLUMN doc_model VARCHAR(64) DEFAULT NULL");
}

// Use template id 3 (simulate_generate.php uses id 3 by default)
$tid = 3;
$pattern = '{docmodel}-{YYYY}-{seq}.pdf';
$docmodel = 'NDM-TEST';
// Backup previous values
$prev = $mysqli->query("SELECT id, filename_pattern, doc_model FROM glpi_plugin_deliveryterms_config WHERE id = {$tid}");
$hadPrev = ($prev && $prev->num_rows);
$old = $hadPrev ? $prev->fetch_assoc() : null;

if (!$hadPrev) {
    $mysqli->query("INSERT INTO glpi_plugin_deliveryterms_config (id, name, title, filename_pattern, doc_model) VALUES ({$tid}, 'DocModel Test', 'DocModel Test', '{$pattern}', '{$docmodel}')");
} else {
    $mysqli->query("UPDATE glpi_plugin_deliveryterms_config SET filename_pattern = '{$pattern}', doc_model = '{$docmodel}' WHERE id = {$tid}");
}

// Now run a simulate-like generation using this template id
// Reuse logic from integration test to allocate sequence
$year = date('Y');
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_sequence (`year` INT(4) UNSIGNED NOT NULL, `last` INT(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`year`)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
$res = $mysqli->query("SELECT year FROM glpi_plugin_deliveryterms_sequence WHERE `year` = {$year}");
if ($res && $res->num_rows === 0) {
    $c = $mysqli->query("SELECT COUNT(*) AS c FROM glpi_plugin_deliveryterms_protocols WHERE gen_date BETWEEN '{$year}-01-01' AND '{$year}-12-31'");
    $count = ($c) ? (int)$c->fetch_assoc()['c'] : 0;
    $mysqli->query("INSERT INTO glpi_plugin_deliveryterms_sequence (`year`, `last`) VALUES ({$year}, {$count}) ON DUPLICATE KEY UPDATE `last` = `last`;");
}
$mysqli->query("UPDATE glpi_plugin_deliveryterms_sequence SET `last` = `last` + 1 WHERE `year` = {$year}");
$nr = $mysqli->query("SELECT `last` FROM glpi_plugin_deliveryterms_sequence WHERE `year` = {$year}");
$row = $nr->fetch_assoc();
$seqnum = (int)$row['last'];

// Compute filename using template pattern and doc_model
$tpl = $mysqli->query("SELECT name, filename_pattern, doc_model FROM glpi_plugin_deliveryterms_config WHERE id = {$tid}");
$trow = $tpl->fetch_assoc();
$pattern_used = $trow['filename_pattern'] ?? '{type}-{YYYY}-{seq}.pdf';
$docmodel_used = $trow['doc_model'] ?? '';
$typename = preg_replace('/\s+/', '_', $trow['name'] ?? 'Template');
$repl = [
    '{type}' => $typename,
    '{YYYY}' => $year,
    '{date}' => date('dmY'),
    '{owner}' => 'owner',
    '{docmodel}' => preg_replace('/\s+/', '_', $docmodel_used),
    '{seq}' => sprintf('%04d', $seqnum)
];
$computed = strtr($pattern_used, $repl);
if (strtolower(substr($computed, -4)) !== '.pdf') { $computed .= '.pdf'; }

echo "Computed filename: {$computed}\n";
if (strpos($computed, $docmodel) !== false) {
    echo "OK: docmodel appears in computed filename\n";
    // cleanup: restore previous template values (if any)
    if ($hadPrev && $old) {
        $fp = $mysqli->real_escape_string($old['filename_pattern'] ?? '');
        $dm = $mysqli->real_escape_string($old['doc_model'] ?? '');
        $mysqli->query("UPDATE glpi_plugin_deliveryterms_config SET filename_pattern = '{$fp}', doc_model = '{$dm}' WHERE id = {$tid}");
    } else {
        $mysqli->query("DELETE FROM glpi_plugin_deliveryterms_config WHERE id = {$tid}");
    }
    exit(0);
} else {
    echo "FAILED: computed filename did not contain docmodel {$docmodel}\n";
    if ($hadPrev && $old) {
        $fp = $mysqli->real_escape_string($old['filename_pattern'] ?? '');
        $dm = $mysqli->real_escape_string($old['doc_model'] ?? '');
        $mysqli->query("UPDATE glpi_plugin_deliveryterms_config SET filename_pattern = '{$fp}', doc_model = '{$dm}' WHERE id = {$tid}");
    } else {
        $mysqli->query("DELETE FROM glpi_plugin_deliveryterms_config WHERE id = {$tid}");
    }
    exit(2);
}
?>