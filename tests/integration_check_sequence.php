<?php
// Integration-style script to verify per-year sequence allocation used by the plugin.
// This uses direct DB queries and mirrors the logic used in inc/generate.class.php

$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

$year = date('Y');

// Ensure sequence table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_sequence (`year` INT(4) UNSIGNED NOT NULL, `last` INT(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`year`)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Initialize for current year if missing (use count of existing protocols for safety)
$res = $mysqli->query("SELECT year FROM glpi_plugin_deliveryterms_sequence WHERE `year` = " . $mysqli->real_escape_string($year));
if ($res && $res->num_rows === 0) {
    $c = $mysqli->query("SELECT COUNT(*) AS c FROM glpi_plugin_deliveryterms_protocols WHERE gen_date BETWEEN '${year}-01-01' AND '${year}-12-31'");
    $count = ($c) ? (int)$c->fetch_assoc()['c'] : 0;
    $mysqli->query("INSERT INTO glpi_plugin_deliveryterms_sequence (`year`, `last`) VALUES (${year}, ${count}) ON DUPLICATE KEY UPDATE `last` = `last`;");
    echo "Initialized sequence for ${year} with last=${count}\n";
}

// Atomically increment
$mysqli->query("UPDATE glpi_plugin_deliveryterms_sequence SET `last` = `last` + 1 WHERE `year` = ${year}");
$nr = $mysqli->query("SELECT `last` FROM glpi_plugin_deliveryterms_sequence WHERE `year` = ${year}");
$row = $nr->fetch_assoc();
$seqnum = (int)$row['last'];

// For demonstration, use a hardcoded document type
$doc_type = 'Termo_de_Entrega';
$filename = $doc_type . '-' . $year . '-' . sprintf('%04d', $seqnum) . '.pdf';

echo "Allocated sequence: ${seqnum}\n";
echo "Filename would be: ${filename}\n";

?>