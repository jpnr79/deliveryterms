<?php

/**
 * Initialize the PluginDeliverytermsProfile object.
 * This class handles the logic related to GLPI user profiles and 
 * their specific permissions (rights) within this plugin.
 */
$PluginDeliverytermsProfile = new PluginDeliverytermsProfile();

/**
 * Action: Update Profile Rights
 * Triggered when the 'update' request is submitted (usually from a profile permissions form).
 * * 1. Calls the static method updateRights() to save new permission levels 
 * for a specific profile into the GLPI database.
 * 2. Uses Html::back() to redirect the administrator back to the 
 * profile management page after the changes are saved.
 */
if (isset($_REQUEST['update'])) {
	$PluginDeliverytermsProfile::updateRights();
	Html::back();
}

?>