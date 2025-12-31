<?php

/**
 * PluginProtocolsmanagerMenu Class
 * * Handles the menu integration for the Protocols Manager plugin in GLPI.
 * Optimized for GLPI 11.
 */
class PluginDeliverytermsMenu extends CommonGLPI
{
    /**
     * Defines the name of the menu item as it appears in the navigation.
     * @return string The translated plugin name.
     */
    public static function getMenuName(): string
    {
        return __('Delivery Terms', 'deliveryterms');
    }

    /**
     * Defines the FontAwesome icon for the menu.
     * * @return string FontAwesome class string.
     */
    public static function getIcon(): string
    {
        // 'fas fa-file-contract' is a modern, relevant icon for protocols
        return 'fas fa-file-contract';
    }

    /**
     * Returns the menu configuration.
     * For GLPI 11, 'page' MUST be a string representing the URL path.
     * * @return array Configuration for the GLPI menu system.
     */
    public static function getMenuContent(): array
    {
        return [
            'title' => self::getMenuName(),
            'page'  => '/plugins/deliveryterms/front/config.form.php',
            'icon'  => self::getIcon(),
        ];
    }
}