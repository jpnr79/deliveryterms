<?php
// Check that tab_itemtypes setting can be stored and read
$mysqli = new mysqli('localhost', 'glpi', 'YourStrongPassword', 'glpi');
if ($mysqli->connect_errno) {
    echo "DB connect failed: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
// Ensure settings table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS glpi_plugin_deliveryterms_settings (id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `option_key` VARCHAR(100) NOT NULL, `option_value` TEXT, PRIMARY KEY (id), UNIQUE KEY `option_key` (`option_key`)) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
// Upsert a test value
$val = 'User,Computer,Printer';
$mysqli->query("INSERT INTO glpi_plugin_deliveryterms_settings (`option_key`, `option_value`) VALUES ('tab_itemtypes', '${val}') ON DUPLICATE KEY UPDATE `option_value` = '${val}'");
// Read it back
$res = $mysqli->query("SELECT option_value FROM glpi_plugin_deliveryterms_settings WHERE option_key = 'tab_itemtypes'");
if ($res && $row = $res->fetch_assoc()) {
    $val = $row['option_value'];
    $parts = array_filter(array_map('trim', explode(',', $val)));
    echo "tab_itemtypes: " . implode(',', $parts) . PHP_EOL;
} else {
    echo "No tab_itemtypes setting found\n";
}
if (in_array('--cleanup', $argv ?? [])) {
    $mysqli->query("DELETE FROM glpi_plugin_deliveryterms_settings WHERE option_key = 'tab_itemtypes'");
    echo "Cleanup done\n";
}
?>