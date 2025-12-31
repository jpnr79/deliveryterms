<?php
	
	/**
	 * Security Check: Ensure the user has the 'config' right with 'UPDATE' permissions.
	 * This prevents unauthorized users from accessing or modifying plugin settings.
	 */
	Session::haveRight("config", UPDATE);
	
	/**
	 * Render the HTML Header for the page.
	 * Parameters: Page Title, Help URL, Menu name, and Sub-menu name.
	 */
	Html::header(PluginDeliverytermsConfig::getTypeName(2), '', "config", "PluginDeliverytermsMenu");
	
	/**
	 * Dependency Management:
	 * 1. A manual flag is set to ensure tinyMCE is recognized (fix for potential loading race conditions).
	 * 2. Require the standard GLPI TinyMCE and File Upload JS libraries.
	 */
    echo Html::scriptBlock("window.tinyMCE = true;"); 
    Html::requireJs('tinymce');
    Html::requireJs('fileupload');
			   
	// Instantiate the configuration object to access its methods.
	$PluginDeliverytermsConfig = new PluginDeliverytermsConfig();
	
	/**
	 * Logic for Protocol Templates:
	 * Handles the saving and deletion of general protocol configuration.
	 * Uses 'menu_mode' = 't' to keep the user on the "Templates" tab after redirect.
	 */
	if (isset($_REQUEST['save'])) {
		$PluginDeliverytermsConfig::saveConfigs();
		$_SESSION['menu_mode'] = 't';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}	
	
	if (isset($_REQUEST['delete'])) {
		$PluginDeliverytermsConfig::deleteConfigs();
		$_SESSION['menu_mode'] = 't';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}

	/**
	 * Logic for Email Configuration:
	 * Handles saving and deleting settings related to email notifications.
	 * Uses 'menu_mode' = 'e' to return the user to the "Email" tab.
	 */
	if (isset($_REQUEST['save_email'])) {
		$PluginDeliverytermsConfig::saveEmailConfigs();
		$_SESSION['menu_mode'] = 'e';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}	
	
	if (isset($_REQUEST['delete_email'])) {
		$PluginDeliverytermsConfig::deleteEmailConfigs();
		$_SESSION['menu_mode'] = 'e';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}	
	
	/**
	 * Navigation Logic:
	 * Handles the 'Cancel' button action for both Templates ('t') and Email ('e') sections.
	 */
	if (isset($_REQUEST['cancel'])) {
		$_SESSION['menu_mode'] = 't';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}	
	
	if (isset($_REQUEST['cancel_email'])) {
		$_SESSION['menu_mode'] = 'e';
		Html::back();
		unset($_SESSION["menu_mode"]);
	}
	
	/**
	 * Main View:
	 * Calls the method that renders the actual HTML form fields for the plugin settings.
	 */
	$PluginDeliverytermsConfig->showFormProtocolsmanager();

	/**
	 * HTML Footer:
	 * Mandatory call to render the footer. This is also where the JS files 
	 * queued by 'Html::requireJs' are actually printed to the page.
	 */
	Html::footer();

	// Clean up the session variable to prevent side effects on other pages.
	unset($_SESSION["menu_mode"]);
	
?>

<script>
/**
 * Legacy/Commented-out jQuery:
 * This was likely used to toggle visibility between Template and Email settings 
 * tabs on the client side without reloading the page.
 */
/* $(function(){
	$("#template_button").click(function(){
		$("#template_settings").show();
		$("#show_configs").show();
		$("#email_settings").hide();
		$("#show_emailconfigs").hide();
	});	
	$("#email_button").click(function(){
		$("#template_settings").hide();
		$("#show_configs").hide();
		$("#email_settings").show();
		$("#show_emailconfigs").show();
	});
});	*/

</script>