<?php

/**
 * Plugin version info
 *
 * @return array
 */
function plugin_version_deliveryterms(): array
{
    return [
        'name'         => __('Delivery Terms', 'deliveryterms'),
        'version'      => '1.0', // Updated version
        'author'       => 'Pedro Rocha',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/CanMik/protocolsmanager',
        'requirements' => [
            'glpi' => [
                'min' => '11.0.0',
                'max' => '12.0.0' // Adjusted to follow GLPI 11 lifecycle
            ],
            'php'  => [
                'min' => '8.2' // Recommended for GLPI 11
            ]
        ]
    ];
}

/**
 * Config check
 *
 * @return bool
 */
function plugin_deliveryterms_check_config(): bool
{
    return true;
}

/**
 * Prerequisites check
 *
 * @return bool
 */
function plugin_deliveryterms_check_prerequisites(): bool
{
    // GLPI version check using built-in Plugin method if available
    if (version_compare(GLPI_VERSION, '11.0.0', '<') || version_compare(GLPI_VERSION, '12.0.0', '>=')) {
        $message = sprintf(
            __('This plugin requires GLPI >= %1$s and < %2$s', 'deliveryterms'),
            '11.0.0',
            '12.0.0'
        );
        
        if (method_exists('Plugin', 'messageIncompatible')) {
            Plugin::messageIncompatible('core', '11.0.0', '12.0.0');
        } else {
            echo $message;
        }
        return false;
    }
    return true;
}

/**
 * Init plugin hooks
 *
 * @return void
 */
function plugin_init_deliveryterms(): void
{
    global $PLUGIN_HOOKS, $DB;

    // Ensure plugin gettext domain is bound to the plugin locales directory so translations are available at runtime
    bindtextdomain('deliveryterms', __DIR__ . '/locales');
    bind_textdomain_codeset('deliveryterms', 'UTF-8');

    // CSRF and CSS
    $PLUGIN_HOOKS['csrf_compliant']['deliveryterms'] = true;
    $PLUGIN_HOOKS['add_css']['deliveryterms']        = 'css/styles.css';

    // Register tabs for supported item types
    $tabTargets = [
        'User'
    ];
    
    foreach ($tabTargets as $target) {
        Plugin::registerClass('PluginDeliverytermsGenerate', ['addtabon' => $target]);
    }

    // Config and Profile tabs
    Plugin::registerClass('PluginDeliverytermsProfile', ['addtabon' => 'Profile']);
    Plugin::registerClass('PluginDeliverytermsConfig',  ['addtabon' => 'Config']);

    // Security check for DB and Rights
    if ($DB->tableExists('glpi_plugin_deliveryterms_profiles')) {
        // Use the centralized right check if the class exists
        if (class_exists('PluginDeliverytermsProfile')) {
            
            // Check for configuration rights
            if (Session::haveRight('config', UPDATE)) {
                $PLUGIN_HOOKS['config_page']['deliveryterms'] = 'front/config.form.php';
            }

            // Register for item deletion hooks so we can cleanup protocol rows when core Documents are removed
            $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::ITEM_DELETE]['deliveryterms'] = 'plugin_deliveryterms_item_delete';
            // Also register for purge (hard delete) so we remove protocol rows on purge
            $PLUGIN_HOOKS[\Glpi\Plugin\Hooks::ITEM_PURGE]['deliveryterms']  = 'plugin_deliveryterms_item_purge';

            // Menu integration: intentionally removed to avoid adding a Configuration menu entry.
            // Previously this plugin added an item under Configuration > Protocols Manager via `menu_toadd`.
            // That behaviour has been disabled to keep the Configuration menu uncluttered.
        }
    }
}