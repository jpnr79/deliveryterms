<?php
// Renders the deliveryterms generate page in CLI and checks for expected labels and icon spacing
require_once '/var/www/glpi/vendor/autoload.php';
require_once '/var/www/glpi/src/autoload/constants.php';
require_once '/var/www/glpi/inc/includes.php';

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Set a valid session
$_SESSION['valid_id'] = session_id();
// Use user id 2 (glpi admin in test DB)
$_SESSION['glpiID'] = 2;
$_SESSION['glpiactiveprofile']['id'] = 4;
$_SESSION['glpilanguage'] = 'en_GB';

// Bootstrap environment constants used by the plugin
if (!defined('GLPI_UPLOAD_DIR')) { define('GLPI_UPLOAD_DIR', '/var/www/glpi/files'); }
if (!defined('GLPI_PICTURE_DIR')) { define('GLPI_PICTURE_DIR', GLPI_UPLOAD_DIR . '/pictures'); }
if (!defined('GLPI_VAR_DIR'))     { define('GLPI_VAR_DIR', GLPI_UPLOAD_DIR); }
if (!defined('CFG_GLPI'))         { define('CFG_GLPI', ['root_doc' => '/var/www/glpi']); }
if (!defined('GLPI_CONFIG_DIR'))  { define('GLPI_CONFIG_DIR', '/var/www/glpi/config'); }

// Ensure DB is available in CLI context (like run_generate_e2e.php does)
if (!isset($DB) || $DB === null) {
    require_once '/var/www/glpi/config/config_db.php';
    $DB = new DB();
}

// Load plugin classes
require_once __DIR__ . '/../inc/profile.class.php';
require_once __DIR__ . '/../inc/config.class.php';
require_once __DIR__ . '/../inc/generate.class.php';

// Ensure language and local i18n dir are set as in e2e script
if (!isset($_SESSION['glpilanguage'])) { $_SESSION['glpilanguage'] = 'en_GB'; }
if (!defined('GLPI_LOCAL_I18N_DIR')) { define('GLPI_LOCAL_I18N_DIR', GLPI_VAR_DIR . '/_locales'); }
@mkdir(GLPI_LOCAL_I18N_DIR, 0755, true);

// Ensure plugin directories constant to prevent DbUtils fatal in CLI
if (!defined('GLPI_PLUGINS_DIRECTORIES')) { define('GLPI_PLUGINS_DIRECTORIES', ['/var/www/glpi/plugins']); }

// Create a User object (id 2)
$User = new User();
$User->getFromDB(2);

// Avoid DB/loader complexities for item types in CLI render: no linked user types
$CFG_GLPI['linkuser_types'] = array();

ob_start();
$Plugin = new PluginDeliverytermsGenerate();
try {
    $Plugin->showContent($User);
} catch (Throwable $e) {
    // Ensure we still capture partial output for inspection
}
$html = ob_get_clean();

// Save a copy for inspection
file_put_contents(__DIR__ . '/ui_render_output.html', $html);

$checks = [
    'Term Generation Options' => (strpos($html, 'Term Generation Options') !== false),
    'Create Term' => (strpos($html, 'Create Term') !== false),
    'Generated Terms History' => (strpos($html, 'Generated Terms History') !== false),
    'plus icon spacing' => (strpos($html, "fa-plus me-1") !== false || strpos($html, "fa-plus-circle me-1") !== false),
    'history icon spacing' => (strpos($html, "fa-history me-1") !== false),
];

foreach ($checks as $k => $v) {
    echo ($v ? "OK: " : "FAIL: ") . $k . PHP_EOL;
}

// Exit with non-zero if any check failed
$failed = array_filter($checks, function($v){ return !$v; });
exit(count($failed) > 0 ? 1 : 0);
