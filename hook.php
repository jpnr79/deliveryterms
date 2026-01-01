<?php

/**
 * Install the plugin
 */
function plugin_deliveryterms_install(): bool
{
    global $DB;
    $version    = plugin_version_deliveryterms();
    $migration  = new Migration($version['version']);

    // Migrate existing tables if still named with old plugin key
    $oldToNewTables = [
        'glpi_plugin_protocolsmanager_profiles' => 'glpi_plugin_deliveryterms_profiles',
        'glpi_plugin_protocolsmanager_config'   => 'glpi_plugin_deliveryterms_config',
        'glpi_plugin_protocolsmanager_emailconfig' => 'glpi_plugin_deliveryterms_emailconfig',
        'glpi_plugin_protocolsmanager_protocols' => 'glpi_plugin_deliveryterms_protocols',
    ];
    foreach ($oldToNewTables as $old => $new) {
        if ($DB->tableExists($old) && !$DB->tableExists($new)) {
            try {
                $DB->doQuery(sprintf('RENAME TABLE `%s` TO `%s`', $old, $new));
                error_log("[deliveryterms] Renamed table $old to $new");
            } catch (\Throwable $e) {
                error_log("[deliveryterms] Could not rename table $old to $new: " . $e->getMessage());
            }
        }
    }

    // Helper: create table if not exists
    $createTable = function (string $name, string $schema, array $inserts = []) use ($DB) {
        // Ensure id and *_id integer fields are declared UNSIGNED to avoid DB deprecation warnings
        $fixedSchema = preg_replace_callback(
            '/((?:^|\s)`?(?:id|[a-z0-9_]+_id(?:_[a-z0-9_]+)?)`?\s+[^\n,]*?\bint(?:\s*\(\s*\d+\s*\))\b)(?![^\n,]*unsigned)/im',
            function ($matches) {
                return preg_replace('/\bint(\s*\(\s*\d+\s*\))\b/i', 'INT$1 UNSIGNED', $matches[1]);
            },
            $schema
        );
        if ($fixedSchema !== null) {
            $schema = $fixedSchema;
        }

        if (!$DB->tableExists($name)) {
            // Temporarily allow signed keys checks to be skipped (workaround for DB warning regex behavior).
            $oldAllow = $DB->allow_signed_keys;
            $DB->allow_signed_keys = true;
            try {
                $DB->doQuery($schema, $DB->error());
                foreach ($inserts as $insert) {
                    $DB->doQuery($insert, $DB->error());
                }
            } finally {
                $DB->allow_signed_keys = $oldAllow;
            }
        }
    };

    // Profiles table
    $createTable(
        'glpi_plugin_deliveryterms_profiles',
        "CREATE TABLE glpi_plugin_deliveryterms_profiles (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            profile_id INT(11) UNSIGNED,
            plugin_conf CHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            tab_access CHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            make_access CHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            delete_access CHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        [
            sprintf(
                "INSERT INTO glpi_plugin_deliveryterms_profiles (profile_id, plugin_conf, tab_access, make_access, delete_access)
                 VALUES (%d, 'w', 'w', 'w', 'w')",
                $_SESSION['glpiactiveprofile']['id'] ?? 0
            )
        ]
    );

    // Config table
    $createTable(
        'glpi_plugin_deliveryterms_config',
        "CREATE TABLE glpi_plugin_deliveryterms_config (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255),
            title VARCHAR(255),
            font VARCHAR(255),
            fontsize VARCHAR(255),
            logo VARCHAR(255),
            logo_width INT(11) UNSIGNED DEFAULT NULL,
            logo_height INT(11) UNSIGNED DEFAULT NULL,
            content TEXT,
            footer TEXT,
            city VARCHAR(255),
            serial_mode INT(2),
            column1 VARCHAR(255),
            column2 VARCHAR(255),
            orientation VARCHAR(10),
            breakword INT(2),
            email_mode INT(2),
            upper_content TEXT,
            email_template INT(2),
            author_name VARCHAR(255),
            author_state INT(2),
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        [
            "INSERT INTO glpi_plugin_deliveryterms_config
                (name, title, font, fontsize, content, footer, city, serial_mode, orientation, breakword, email_mode, author_name, author_state)
             VALUES
                ('Equipment report',
                 'Certificate of delivery of {owner}',
                 'Roboto',
                 '9',
                 'User: \\n I have read the terms of use of IT equipment in the Example Company.',
                 'Example Company \\n Example Street 21 \\n 01-234 Example City',
                 'Example city',
                 1,
                 'Portrait',
                 1,
                 2,
                 'Test Division',
                 1)",
            "INSERT INTO glpi_plugin_deliveryterms_config
                (name, title, font, fontsize, content, footer, city, serial_mode, orientation, breakword, email_mode, author_name, author_state)
             VALUES
                ('Equipment report 2',
                 'Certificate of delivery of {owner}',
                 'Roboto',
                 '9',
                 'User: \\n I have read the terms of use of IT equipment in the Example Company.',
                 'Example Company \\n Example Street 21 \\n 01-234 Example City',
                 'Example city',
                 1,
                 'Portrait',
                 1,
                 2,
                 'Test Division',
                 1)"
        ]
    );

    // Email config table
    $createTable(
        'glpi_plugin_deliveryterms_emailconfig',
        "CREATE TABLE glpi_plugin_deliveryterms_emailconfig (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            tname VARCHAR(255),
            send_user INT(2),
            email_content TEXT,
            email_subject VARCHAR(255),
            email_footer VARCHAR(255),
            recipients VARCHAR(255),
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        [
            "INSERT INTO glpi_plugin_deliveryterms_emailconfig
                (tname, send_user, email_content, email_subject, recipients)
             VALUES
                ('Email default', 2, '', '', '')"
        ]
    );

    // Protocols table
    $createTable(
        'glpi_plugin_deliveryterms_protocols',
        "CREATE TABLE glpi_plugin_deliveryterms_protocols (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255),
            user_id INT(11) UNSIGNED,
            gen_date DATETIME,
            author VARCHAR(255),
            document_id INT(11) UNSIGNED,
            document_type VARCHAR(255),
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    // Clean any pre-existing installer placeholder values that would cause invalid email addresses
    try {
        $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET recipients = '' WHERE recipients = 'Testmail'");
        $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET email_subject = '' WHERE email_subject = 'Testmail'");
        $DB->doQuery("UPDATE glpi_plugin_deliveryterms_emailconfig SET email_content = '' WHERE email_content = 'Testmail'");
    } catch (\Throwable $e) {
        // Non-fatal: log for diagnostics
        error_log("[deliveryterms] Could not clean placeholder email rows: " . $e->getMessage());
    }

    // Ensure primary and foreign key integer columns are UNSIGNED to avoid deprecation warnings
    $tablesToFix = [
        'glpi_plugin_deliveryterms_profiles' => [
            'id'         => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'profile_id' => 'INT(11) UNSIGNED DEFAULT NULL',
        ],
        'glpi_plugin_deliveryterms_config' => [
            'id' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
        ],
        'glpi_plugin_deliveryterms_emailconfig' => [
            'id' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
        ],
        'glpi_plugin_deliveryterms_protocols' => [
            'id'          => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'user_id'     => 'INT(11) UNSIGNED DEFAULT NULL',
            'document_id' => 'INT(11) UNSIGNED DEFAULT NULL',
        ],
    ];

    foreach ($tablesToFix as $table => $fields) {
        if ($DB->tableExists($table)) {
            foreach ($fields as $field => $definition) {
                $fieldInfo = $DB->getField($table, $field, false);
                if ($fieldInfo && stripos($fieldInfo['Type'], 'unsigned') === false) {
                    // Modify field to unsigned (keep existing nullability/auto_increment where applicable)
                    $sql = sprintf("ALTER TABLE `%s` MODIFY `%s` %s", $table, $field, $definition);
                    try {
                        // Temporarily disable signed-key warnings while running ALTER (the warning detection is currently strict)
                        $oldAllow = $DB->allow_signed_keys;
                        $DB->allow_signed_keys = true;
                        try {
                            $DB->doQuery($sql);
                        } finally {
                            $DB->allow_signed_keys = $oldAllow;
                        }
                    } catch (\Throwable $e) {
                        // Non-fatal: log but keep install moving
                        error_log("[deliveryterms] Could not modify field $field on table $table to unsigned: " . $e->getMessage());
                    }
                }
            }
        }
    }

    $migration->executeMigration();

    // Ensure .mo files are placed in proper gettext structure (lang/LC_MESSAGES/deliveryterms.mo)
    $moDir = __DIR__ . '/locales';
    if (is_dir($moDir)) {
        foreach (glob($moDir . '/*.mo') as $mo) {
            $lang = basename($mo, '.mo');
            $destDir = $moDir . '/' . $lang . '/LC_MESSAGES';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            @copy($mo, $destDir . '/deliveryterms.mo');
        }
        // Clear GLPI locale cache files to force reload
        if (defined('GLPI_ROOT')) {
            $cacheLocales = GLPI_ROOT . '/files/_locales';
            if (is_dir($cacheLocales)) {
                $files = glob($cacheLocales . '/*');
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
    }

    return true;
}

/**
 * Hook: called when an item is deleted in core (soft-delete)
 * We use this to remove any plugin protocol rows referencing deleted documents so history stays consistent.
 */
function plugin_deliveryterms_item_delete($item) {
    global $DB;
    // Only act for Document deletions
    if (!is_object($item) || $item::class !== Document::class) { return; }
    $docId = $item->getID();
    if ($docId) {
        try {
            $DB->delete('glpi_plugin_deliveryterms_protocols', ['document_id' => $docId]);
            error_log("[deliveryterms] Cleaned protocol rows for deleted document id $docId");
        } catch (\Throwable $e) {
            error_log("[deliveryterms] Error cleaning protocol rows for document id $docId: " . $e->getMessage());
        }
    }
}

/**
 * Called when an item is purged (hard delete) in core.
 * We remove plugin protocol rows and ensure any plugin-side cleanup is performed.
 */
function plugin_deliveryterms_item_purge($item) {
    global $DB;
    // Only act for Document purges
    if (!is_object($item) || $item::class !== Document::class) { return; }
    $docId = $item->getID();
    if ($docId) {
        try {
            // Delete any plugin rows referencing this document (hard cleanup)
            $DB->delete('glpi_plugin_deliveryterms_protocols', ['document_id' => $docId]);
            error_log("[deliveryterms] Purged protocol rows for purged document id $docId");
        } catch (\Throwable $e) {
            error_log("[deliveryterms] Error purging protocol rows for document id $docId: " . $e->getMessage());
        }
    }
}

/**
 * Uninstall the plugin
 */
function plugin_deliveryterms_uninstall(): bool
{
    global $DB;
    $tables = [
        'glpi_plugin_deliveryterms_protocols',
        'glpi_plugin_deliveryterms_config',
        'glpi_plugin_deliveryterms_profiles',
        'glpi_plugin_deliveryterms_emailconfig'
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            // CORRECCIÓN AQUI: Usar doQuery en lugar de query
            $DB->doQuery("DROP TABLE IF EXISTS `$table`");
        }
    }

    return true;
}

/**
 * Safe loader to prevent early table access
 */
function plugin_deliveryterms_getRights(?int $profile_id = null)
{
    global $DB;
    if (!$DB->tableExists('glpi_plugin_deliveryterms_profiles')) {
        return []; // Avoid query before installation
    }

    return $DB->request([
        'FROM'  => 'glpi_plugin_deliveryterms_profiles',
        'WHERE' => ['profile_id' => $profile_id ?? 0]
    ])->current();
}