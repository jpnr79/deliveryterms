<?php
// Deprecated helper — no-op to avoid accidental execution.
// This file can be deleted or kept for audit purposes.

echo "Deprecated: no-op\n";
exit(0);

if (!defined('GLPI_ROOT')) {
    echo "This script should be run from GLPI's plugins/deliveryterms directory using the GLPI bootstrap.\n";
    exit(1);
}

global $DB;
try {
    $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET recipients = '' WHERE recipients = 'Testmail'");
    $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET email_subject = '' WHERE email_subject = 'Testmail'");
    $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET email_content = '' WHERE email_content = 'Testmail'");
    echo "Placeholder email rows cleaned (if any).\n";
} catch (\Throwable $e) {
    echo "Failed to clean rows: " . $e->getMessage() . "\n";
    exit(2);
}
return 0;
