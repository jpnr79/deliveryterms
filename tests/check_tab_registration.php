<?php
// Check that plugin_init_deliveryterms registers tabs according to the `tab_itemtypes` setting.
// This test runs without full GLPI bootstrap by stubbing a minimal Plugin and DB interface.

$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}

// Ensure settings table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_settings (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `option_key` VARCHAR(100) NOT NULL, `option_value` TEXT, PRIMARY KEY (id), UNIQUE KEY `option_key` (`option_key`)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Upsert a test value with two item types
$val = 'User,Computer';
$mysqli->query("INSERT INTO glpi_plugin_deliveryterms_settings (`option_key`, `option_value`) VALUES ('tab_itemtypes', '${val}') ON DUPLICATE KEY UPDATE `option_value` = '${val}'");

// Minimal $DB wrapper used by plugin_init_deliveryterms
class SimpleDBWrapper {
    private $mysqli;
    public function __construct($mysqli) { $this->mysqli = $mysqli; }
    public function tableExists($name) {
        $name = $this->mysqli->real_escape_string($name);
        $res = $this->mysqli->query("SHOW TABLES LIKE '${name}'");
        return ($res && $res->num_rows > 0);
    }
    public function request($opts) {
        // Expecting ['FROM' => 'table', 'WHERE' => ['option_key' => 'tab_itemtypes']]
        $table = $this->mysqli->real_escape_string($opts['FROM']);
        $whereKey = '';
        if (!empty($opts['WHERE']) && is_array($opts['WHERE'])) {
            foreach ($opts['WHERE'] as $k => $v) { $whereKey = $this->mysqli->real_escape_string($v); break; }
        }
        $res = $this->mysqli->query("SELECT * FROM ${table} WHERE option_key = '${whereKey}' LIMIT 1");
        $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
        return new class($row) {
            private $row;
            public function __construct($r) { $this->row = $r; }
            public function current() { return $this->row; }
        };
    }
}

// Stub Plugin if not present
if (!class_exists('Plugin')) {
    class Plugin {
        public static $registered = [];
        public static function registerClass($class, $args) {
            self::$registered[] = ['class' => $class, 'args' => $args];
        }
        public static function messageIncompatible() { /* noop */ }
    }
}

// Make minimal globals expected by plugin_init_deliveryterms
$PLUGIN_HOOKS = [];
$DB = new SimpleDBWrapper($mysqli);

// Include setup file and call the init function
require_once __DIR__ . '/../setup.php';
try {
    plugin_init_deliveryterms();
} catch (Throwable $e) {
    echo "plugin_init_deliveryterms raised: " . $e->getMessage() . PHP_EOL;
    exit(2);
}

// Now check captured registrations
$registered = [];
if (isset(Plugin::$registered) && is_array(Plugin::$registered)) {
    foreach (Plugin::$registered as $entry) {
        if ($entry['class'] === 'PluginDeliverytermsGenerate' && !empty($entry['args']['addtabon'])) {
            $registered[] = $entry['args']['addtabon'];
        }
    }
}

$registered = array_unique($registered);
sort($registered);
$expected = ['Computer','User'];
sort($expected);

if ($registered == $expected) {
    echo "OK: tab registrations found for: " . implode(',', $registered) . PHP_EOL;
    // cleanup
    if (in_array('--cleanup', $argv ?? [])) {
        $mysqli->query("DELETE FROM glpi_plugin_deliveryterms_settings WHERE option_key = 'tab_itemtypes'");
        echo "Cleanup done\n";
    }
    exit(0);
} else {
    echo "FAILED: expected registrations for " . implode(',', $expected) . " but got " . implode(',', $registered) . PHP_EOL;
    exit(3);
}
?>