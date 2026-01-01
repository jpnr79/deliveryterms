<?php
// E2E generation script for deliveryterms plugin
// Usage: php run_generate_e2e.php

// Bootstrap GLPI by loading core constants and includes
require_once '/var/www/glpi/vendor/autoload.php';
require_once '/var/www/glpi/src/autoload/constants.php';
require_once '/var/www/glpi/inc/includes.php';

// Start session if not already
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure a valid session for GLPI functions
$_SESSION['valid_id'] = session_id();
// Use user id 2 (glpi) and profile id 4 (has plugin rights in plugin table)
$_SESSION['glpiID'] = 2;
$_SESSION['glpiactiveprofile']['id'] = 4;
// Active entity (0 is usually default)
$_SESSION['glpiactive_entity'] = 0;

// Ensure DB is available in CLI context
global $DB;
if (!isset($DB) || $DB === null) {
    require_once '/var/www/glpi/config/config_db.php';
    $DB = new DB();
}

// Minimal POST to generate a protocol
$_POST = [];
$_POST['generate'] = 1;
// Use template id present in DB (from earlier): 3
$_POST['list'] = 3;
// user to which protocol applies (choose user id 2)
$_POST['user_id'] = 2;
// optional fields
$_POST['notes'] = 'E2E test generation';

// Load plugin classes
require_once __DIR__ . '/../inc/profile.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/generate.class.php';

// Ensure GLPI directories and constants exist for CLI context
if (!defined('GLPI_UPLOAD_DIR')) { define('GLPI_UPLOAD_DIR', '/var/www/glpi/files'); }
if (!defined('GLPI_PICTURE_DIR')) { define('GLPI_PICTURE_DIR', GLPI_UPLOAD_DIR . '/pictures'); }
if (!defined('GLPI_VAR_DIR'))     { define('GLPI_VAR_DIR', GLPI_UPLOAD_DIR); }
if (!defined('CFG_GLPI'))         { define('CFG_GLPI', ['root_doc' => '/var/www/glpi']); }

// Call the generation directly
echo "Calling PluginDeliverytermsGenerate::makeProtocol()...\n";
PluginDeliverytermsGenerate::makeProtocol();

// Use mysqli to fetch the latest protocol row for verification
$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$res = $mysqli->query("SELECT id, name, gen_date, author, document_id FROM glpi_plugin_deliveryterms_protocols ORDER BY id DESC LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "New protocol record:\n";
    print_r($row);
    $docRes = $mysqli->query("SELECT * FROM glpi_documents WHERE id = " . (int)$row['document_id']);
    if ($docRow = $docRes->fetch_assoc()) {
        echo "Associated document:\n";
        print_r($docRow);
    } else {
        echo "No associated document found (document_id={$row['document_id']}).\n";
    }
} else {
    echo "No protocol record found after generation.\n";
}

?>