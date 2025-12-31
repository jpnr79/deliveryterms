<?php

/**
 * Protocols Manager - Profile Rights Management
 *
 * This class handles the integration of the plugin's permissions into GLPI's profile system.
 * It manages which user profiles can configure the plugin, access tabs, or manage protocols.
 *
 * @package   glpi\deliveryterms
 */
class PluginDeliverytermsProfile extends CommonDBTM
{
    /** * @var array<string,string> Profile rights handled by this plugin 
     * Maps database field names to their human-readable labels for the UI.
     */
    private static $rightFields = [
        'plugin_conf'   => 'Plugin configuration',
        'tab_access'    => 'Protocols manager tab access',
        'make_access'   => 'Create protocols',
        'delete_access' => 'Delete protocols'
    ];

    /**
     * Defines the name and icon of the tab that will appear in the GLPI Profile configuration.
     * * @param CommonGLPI $item The profile object
     * @param int $withtemplate Template flag
     * @return string HTML string containing the icon and the translated label
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        // Using FontAwesome icon for a professional visual representation
        $icon = "<i class='fas fa-file-contract me-2'></i>";
        $label = __('Delivery Terms', 'deliveryterms');
        
        return "<span class='d-inline-flex align-items-center'>$icon $label</span>";
    }

    /**
     * Entry point for rendering the content inside the "Protocols manager" tab.
     * * @param CommonGLPI $item The profile object
     * @param int $tabnum Current tab index
     * @param int $withtemplate Template flag
     * @return bool Returns true to indicate the content was displayed
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        // Renders the specific rights form using the profile ID
        self::showRightsForm($item->getID());
        return true;
    }

    /**
     * Renders the HTML form used to toggle specific plugin permissions for a profile.
     * Uses GLPI's standard UI components for consistency.
     * * @param int $profile_id The ID of the profile being edited
     */
    private static function showRightsForm(int $profile_id): void
    {
        global $CFG_GLPI, $DB;

        // Initialize default state (all permissions empty)
        $rights    = array_fill_keys(array_keys(self::$rightFields), '');
        $edit_flag = 1; // Flag indicating a new record needs to be inserted

        // Query the database to see if this profile already has permissions defined
        $req = $DB->request([
            'FROM' => 'glpi_plugin_deliveryterms_profiles',
            'WHERE' => ['profile_id' => $profile_id]
        ]);
        
        // If a record exists, populate the $rights array and switch to update mode
        if ($row = $req->current()) {
            foreach (self::$rightFields as $field => $_) {
                $rights[$field] = $row[$field] ?? '';
            }
            $edit_flag = 0; // Record found, switch to update mode
        }

        // Form header pointing to the internal plugin processing script
        echo "<form name='profiles' action='{$CFG_GLPI['root_doc']}/plugins/deliveryterms/front/profile.form.php' method='post'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_5'><th colspan='2'>" . __('Delivery Terms', 'deliveryterms') . "</th></tr>";

        // Generate a table row for each permission defined in $rightFields
        foreach (self::$rightFields as $field => $label) {
            echo "<tr class='tab_bg_2'><td width='30%'>" . __($label, 'deliveryterms') . "</td><td>";
            // GLPI standard checkbox: 'w' (write) indicates the permission is granted
            Html::showCheckbox([
                'name'    => $field,
                'checked' => ($rights[$field] === 'w'),
                'value'   => 'w'
            ]);
            echo "</td></tr>";
        }

        // Footer with submit button and hidden metadata (profile ID and operation mode)
        echo "<tr class='tab_bg_5'><th colspan='2'>";
        echo "<input type='submit' class='submit' name='update' value='" . __('Save', 'deliveryterms') . "'>";
        echo Html::hidden('profile_id', ['value' => $profile_id]);
        echo Html::hidden('edit_flag', ['value' => $edit_flag]);
        echo "</th></tr>";

        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    /**
     * Processes the POST data from the rights form and persists it to the database.
     * Handles both creation (insert) and modification (update) of rights.
     */
    public static function updateRights(): void
    {
        global $DB;

        // Collect and sanitize form data
        $data = [
            'profile_id'    => (int)$_POST['profile_id'],
            'plugin_conf'   => $_POST['plugin_conf'] ?? '',
            'tab_access'    => $_POST['tab_access'] ?? '',
            'make_access'   => $_POST['make_access'] ?? '',
            'delete_access' => $_POST['delete_access'] ?? ''
        ];
        
        // Determine whether to insert a new row or update the existing one based on edit_flag
        if ((int)$_POST['edit_flag'] === 1) {
            $DB->insert('glpi_plugin_deliveryterms_profiles', $data);
        } else {
            $DB->update('glpi_plugin_deliveryterms_profiles', $data, [
                'profile_id' => (int)$_POST['profile_id']
            ]);
        }
    }

    /**
     * Utility method to check if the currently logged-in user has a specific permission.
     * * @param string $right The key of the right to check (e.g., 'make_access')
     * @return bool True if the user has the 'w' (write) permission, false otherwise
     */
	public static function currentUserHasRight(string $right): bool
	{
		global $DB;
	
        // Retrieve the active profile ID from the session
		$profile_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
		if ($profile_id <= 0) {
			return false;
		}
	
        // Look up the permissions associated with the active profile
		$res = $DB->request(
			['FROM' => 'glpi_plugin_deliveryterms_profiles', 'WHERE' => ['profile_id' => $profile_id]]
		);
	
		if ($row = $res->current()) {
            // Check if the specific right field is set to 'w'
			return !empty($row[$right]) && $row[$right] === 'w';
		}
	
		return false;
	}
	
}
?>