<?php

/**
 * PluginDeliverytermsConfig Class
 * Handling the configuration for Delivery Terms Plugin in GLPI
 */
class PluginDeliverytermsConfig extends CommonDBTM {
	
    /**
     * Main entry point for the configuration form
     * Renders the tab navigation and calls the content display
     */
	function showFormProtocolsmanager() {
		global $CFG_GLPI;
		// Ensure plugin gettext domain is available during rendering and make it the active domain
		bindtextdomain('deliveryterms', __DIR__ . '/../locales');
		bind_textdomain_codeset('deliveryterms', 'UTF-8');
		// Make sure gettext lookups use 'deliveryterms' as the active domain for this render
		textdomain('deliveryterms');
		$plugin_conf = self::checkRights();

        // Check if user has write ('w') permissions
		if ($plugin_conf == 'w') {
			echo "<div class='asset-config-container' style='max-width: 1100px; margin: 0 auto; padding: 20px;'>";
			
			// Determine which tab is active (Templates 't' or Email 'e')
			$menu_mode = $_SESSION['menu_mode'] ?? ($_POST['menu_mode'] ?? 't');
			
			echo "<div class='card mb-3 shadow-sm'>";
			echo "  <div class='card-header border-bottom-0 bg-light'>";
			echo "    <ul class='nav nav-tabs card-header-tabs'>";
			
			// Navigation Tab: Template Settings
			echo "      <li class='nav-item'>
							<form action='config.form.php' method='post' style='display:inline;'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
			echo "          <input type='hidden' name='menu_mode' value='t'>
							<button type='submit' class='nav-link ".($menu_mode == 't' ? 'active font-weight-bold text-primary' : '')."'>
								<i class='fas fa-file-alt mr-1'></i> ". __('Templates settings', 'deliveryterms') ."
							</button>
						</form>
					</li>";
			
			// Navigation Tab: Email Settings
			echo "      <li class='nav-item'>
							<form action='config.form.php' method='post' style='display:inline;'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
			echo "          <input type='hidden' name='menu_mode' value='e'>
							<button type='submit' class='nav-link ".($menu_mode == 'e' ? 'active font-weight-bold text-primary' : '')."'>
								<i class='fas fa-envelope mr-1'></i> ". __('Email settings', 'deliveryterms') ."
							</button>
						</form>
					</li>";
			
			echo "    </ul>";
			echo "  </div>";
			echo "</div>";

			self::displayContent();
			echo "</div>";
		} else {
            // Access denied message if user lacks permissions
            echo "<div class='center'><br><i class='fas fa-exclamation-triangle fa-3x text-warning'></i><br>".__("Access denied")."</div>";
        }
	}
	
    /**
     * Permission check against the plugin's profiles table
     */
	static function checkRights() {
		global $DB;
		$active_profile = $_SESSION['glpiactiveprofile']['id'];
		$req = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_profiles', 'WHERE' => ['profile_id' => $active_profile]]);
        return ($row = $req->current()) ? $row['plugin_conf'] : "";
	}
	
    /**
     * Content Router
     * Switches between Template form and Email form
     */
	static function displayContent() {
		$menu_mode = $_POST["menu_mode"] ?? ($_SESSION["menu_mode"] ?? "t");
		if ($menu_mode == "e") {
			self::displayContentEmail();
		} else {
			self::displayContentConfig();
		}
	}
	
    /**
     * Renders the Template creation/edition form
     * Order of fields matches the user's requirements exactly
     */
	static function displayContentConfig() {
		global $CFG_GLPI, $DB;
		
		$edit_id = $_POST["edit_id"] ?? 0;
		$mode = $edit_id;

        // If editing, fetch existing data
		if ($edit_id > 0) {
			$req = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_config', 'WHERE' => ['id' => $edit_id ]]);
			if ($row = $req->current()) {
				extract($row); // Import variables from array
				$template_uppercontent = $row["upper_content"];
				$template_content = $row["content"];
				$template_footer = $row["footer"];
				$template_name = $row["name"];
			}
		} else {
            // Default values for new template
			$template_uppercontent = ''; $template_content = ''; $template_footer = ''; $template_name = '';
			$title = ''; $font = 'freesans'; $fontsize = '9'; $city = ''; $mode = 0;
			$serial_mode = 1; $orientation = "p"; $breakword = 1; $email_mode = 2;
			$email_template = 0; $author_name = ''; $author_state = 1; $logo_width = ''; $logo_height = '';
		}
		
		$fonts = ['Courier'=>'Courier', 'Helvetica'=>'Helvetica', 'Times'=>'Times', 'Istok'=>'Istok', 'UbuntuMono'=>'UbuntuMono', 'Roboto'=>'Roboto', 'Liberation-Sans'=>'Liberation-Sans', 'DroidSerif'=>'DroidSerif', 'DejaVu Sans'=>'DejaVu Sans'];
		$fontsizes = ['7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10', '11'=>'11', '12'=>'12'];
		$orientations = ['p' => __('Portrait', 'deliveryterms'), 'l' => __('Landscape', 'deliveryterms')];

		echo "<div class='card shadow-sm'>";
		echo "<form name='form' action='config.form.php' method='post' enctype='multipart/form-data'>";
		echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
		echo Html::hidden('mode', ['value' => $mode]);
		echo Html::hidden('MAX_FILE_SIZE', ['value' => 1948000]);

		echo "  <div class='card-header'><h4 class='mb-0'>".($mode == 0 ? __('Crear Plantilla', 'deliveryterms') : __('Editar Plantilla', 'deliveryterms'))."</h4></div>";
		// diagnostics removed (cleanup)
		echo "  <div class='card-body'>";
		echo "  <table class='table borderless'>";
		
		// Field 1: Template Name
		echo "<tr><td width='25%'>".__('Nombre de la plantilla', 'deliveryterms')."*</td><td><input type='text' name='template_name' class='form-control' value='".htmlescape($template_name)."' required></td></tr>";
		
		// Field 2: Document Title
		echo "<tr><td>".__('Document title', 'deliveryterms')."*</td><td><input type='text' name='title' class='form-control' value='".htmlescape($title)."' required><small class='text-info'>".__('You can use {owner} here.', 'deliveryterms')."</small></td></tr>";
		
		// Field 3: Font Selection
		echo "<tr><td>" . dgettext('deliveryterms', 'Font') . "</td><td><select name='font' class='form-select' style='max-width:200px;'>";
		foreach($fonts as $code => $fontname) echo "<option value='$code' ".($code == $font ? "selected" : "").">$fontname</option>";
		echo "</select></td></tr>";

		// Field 4: Font Size
		echo "<tr><td>" . dgettext('deliveryterms', 'Font size') . "</td><td><select name='fontsize' class='form-select' style='max-width:200px;'>";
		foreach($fontsizes as $fsize => $fs) echo "<option value='$fsize' ".($fsize == $fontsize ? "selected" : "").">$fs</option>";
		echo "</select></td></tr>";

		// Field 5: Word Breaking (Radio)
		echo "<tr><td>" . dgettext('deliveryterms', 'Word breaking') . "</td><td>
				<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='breakword' value='1' ".($breakword == 1 ? "checked" : "")."> " . dgettext('deliveryterms', 'On') . "</div>
				<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='breakword' value='0' ".($breakword == 0 ? "checked" : "")."> " . dgettext('deliveryterms', 'Off') . "</div></td></tr>";

		// Field 6: City
		echo "<tr><td>". dgettext('deliveryterms', 'Ciudad') ."</td><td><input type='text' name='city' class='form-control' value='".htmlescape($city)."'></td></tr>";

		// Field 7: Upper Content (Rich Text)
		echo "<tr><td>" . dgettext('deliveryterms', 'Upper Content') . "</td><td>";
		Html::textarea(['name'=>'template_uppercontent', 'value'=>$template_uppercontent, 'enable_richtext'=>true, 'rows'=>5]);
		echo "<small class='text-info'>You can use {owner}, {admin}, {reg_num}, {title} or {category} here.</small></td></tr>";

		// Field: Filename Pattern (optional)
		echo "<tr><td>" . dgettext('deliveryterms', 'Filename pattern') . "</td><td>";
		echo "<input type='text' name='filename_pattern' class='form-control' value='" . htmlescape(
				$template_filename_pattern ?? '{type}-{YYYY}-{seq}.pdf'
			) . "'>";
		echo "<small class='text-info'>Use placeholders: {type} (template name), {YYYY}, {seq} (zero-padded 4 digits), {owner}, {date}</small></td></tr>";

		// Field 8: Main Content (Rich Text)
		echo "<tr><td>".__('Contenido', 'deliveryterms')."</td><td>";
		Html::textarea(['name'=>'template_content', 'value'=>$template_content, 'enable_richtext'=>true, 'rows'=>8]);
		echo "<small class='text-info'>".__('You can use {owner}, {admin}, {reg_num}, {title} or {category} here.', 'deliveryterms')."</small></td></tr>";

		// Field 9: Footer
		echo "<tr><td>".__('Footer', 'deliveryterms')."</td><td><textarea name='footer_text' class='form-control' rows='4'>".htmlescape($template_footer)."</textarea></td></tr>";

		// Field 10: Orientation
		echo "<tr><td>".__('Orientación', 'deliveryterms')."</td><td><select name='orientation' class='form-select' style='max-width:200px;'>";
		foreach($orientations as $val => $name) echo "<option value='$val' ".($val == $orientation ? "selected" : "").">$name</option>";
		echo "</select></td></tr>";

		// Field 11: Serial Number Logic (Radio)
		echo "<tr><td>".__('Número de serie', 'deliveryterms')."</td><td>
				<div class='form-check'><input class='form-check-input' type='radio' name='serial_mode' value='1' ".($serial_mode == 1 ? "checked" : "")."> serial and inventory number in separate columns</div>
				<div class='form-check'><input class='form-check-input' type='radio' name='serial_mode' value='2' ".($serial_mode == 2 ? "checked" : "")."> serial or inventory number if serial doesn't exists</div>
			</td></tr>";

		// Field 12: Logo Upload and Deletion
		echo "<tr><td>".__('Logo', 'deliveryterms')."</td><td>
				<div class='d-flex align-items-center gap-3'><input type='file' name='logo' class='form-control' style='max-width:300px;'>";
		if (!empty($logo)) {
			echo "<img src='".$CFG_GLPI['root_doc']."/pics/".$logo."' style='max-height:50px;'>";
			echo "<div class='form-check'><input class='form-check-input' type='checkbox' name='img_delete' value='1'> " . __('Delete file', 'deliveryterms') . "</div>";
		}
		echo "</div></td></tr>";

		// Field 13 & 14: Logo Dimensions
		echo "<tr><td>" . dgettext('deliveryterms', 'Logo width (px)') . "</td><td><input type='number' name='logo_width' class='form-control' style='max-width:100px;' value='".htmlescape($logo_width)."'></td></tr>";
		echo "<tr><td>" . dgettext('deliveryterms', 'Logo height (px)') . "</td><td><input type='number' name='logo_height' class='form-control' style='max-width:100px;' value='".htmlescape($logo_height)."'></td></tr>";

		// Plugin Settings: toggle which item types show the tab
		echo "<tr><td>" . dgettext('deliveryterms', 'Plugin tabs on item types') . "</td><td>";
		// Load current setting
		$cur = '';
		if ($DB->tableExists('glpi_plugin_deliveryterms_settings')) {
			$rowset = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_settings', 'WHERE' => ['option_key' => 'tab_itemtypes']])->current();
			if ($rowset) { $cur = $rowset['option_value']; }
		}
		$selected_types = $cur ? explode(',', $cur) : ['User'];
		$all_types = ['User' => 'User', 'Computer' => 'Computer', 'Printer' => 'Printer', 'Peripheral' => 'Peripheral', 'Phone' => 'Phone', 'Line' => 'Line', 'Monitor' => 'Monitor'];
		echo "<div class='d-flex flex-wrap gap-2'>";
		foreach ($all_types as $k => $label) {
			$checked = in_array($k, $selected_types) ? "checked" : "";
			echo "<div class='form-check'><input class='form-check-input' type='checkbox' name='tab_itemtypes[]' value='".htmlescape($k)."' $checked> <label class='form-check-label'>".htmlescape($label)."</label></div>";
		}
		echo "</div><small class='text-info'>Choose which item types should display the Delivery Terms tab. Save by clicking 'Save' below.</small><br><small class='text-muted'>See plugin <a href='README.md' target='_blank'>README</a> for filename placeholders ({type}, {YYYY}, {seq}, {owner}) and TinyMCE table helpers.</small></td></tr>";

		// Field 15: Email Autosending Toggle
		echo "<tr><td>" . dgettext('deliveryterms', 'Enable email autosending') . "</td><td>
				<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='email_mode' value='1' ".($email_mode == 1 ? "checked" : "")."> " . dgettext('deliveryterms', 'ON') . "</div>
				<div class='form-check form-check-inline'><input class='form-check-input' type='radio' name='email_mode' value='2' ".($email_mode == 2 ? "checked" : "")."> " . dgettext('deliveryterms', 'OFF') . "</div></td></tr>";

		// Field 16: Link to Email Template
		echo "<tr><td>".__('Email template', 'deliveryterms')."</td><td><select name='email_template' class='form-select' style='max-width:300px;'>";
        foreach ($DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig']) as $list) {
            echo "<option value='".$list['id']."' ".($list['id'] == $email_template ? "selected" : "").">".$list['tname']."</option>";
        }
        echo "</select></td></tr>";

		// Field 17: Author Selection
		echo "<tr><td>".__('Select who should generate the pdf', 'deliveryterms')."</td><td>
			<div class='row g-2 align-items-center'>
				<div class='col-auto'><input type='radio' name='author_state' value='1' ".($author_state == 1 ? "checked" : "")."> " . __('The user who generates the document', 'deliveryterms') . "</div>
					<div class='col-auto ms-4 d-flex align-items-center gap-2'>
						<input type='radio' name='author_state' value='2' ".($author_state == 2 ? "checked" : "").">
						<input type='text' name='author_name' class='form-control' style='max-width:200px;' value='".htmlescape($author_name)."'>
					</div>
				</div></td></tr>";
        
        echo "  </table>";
		echo "  </div>";
		echo "  <div class='card-footer d-flex justify-content-center gap-2'>";
			echo "    <button type='submit' name='save' class='btn btn-primary'>".__('Save', 'deliveryterms')."</button>";
			echo "    <button type='submit' name='cancel' class='btn btn-outline-secondary'>".__('Cancel', 'deliveryterms')."</button>";
		echo "  </div>";
		Html::closeForm();
		echo "</div><br>";
		
		self::showConfigs();
	}

    /**
     * Logic to save Template data into the DB
     * Triggered by 'save' button in Template form
     */
	static function saveConfigs() {
		global $DB;
		
        // Basic validation
		if (empty($_POST["template_name"]) || empty($_POST["title"])) {
			Session::addMessageAfterRedirect('Mandatory fields empty', 'WARNING', true);
			return;
		}

		$mode = (int)$_POST["mode"];
		$full_img_name = self::uploadImage(); // Process file upload
		
		$fields = [
			'name'           => $_POST['template_name'],
			'title'          => $_POST['title'],
			'upper_content'  => $_POST['template_uppercontent'],
			'content'        => $_POST['template_content'],
			'footer'         => $_POST['footer_text'],
			'font'           => $_POST['font'],
			'fontsize'       => $_POST['fontsize'],
			'city'           => $_POST['city'],
            // Cast numeric flags to integers and convert empty values to NULL where appropriate
            'serial_mode'    => isset($_POST['serial_mode']) ? (int) $_POST['serial_mode'] : null,
            'orientation'    => $_POST['orientation'],
            'breakword'      => isset($_POST['breakword']) ? (int) $_POST['breakword'] : null,
            'email_mode'     => isset($_POST['email_mode']) ? (int) $_POST['email_mode'] : null,
            'email_template' => isset($_POST['email_template']) && $_POST['email_template'] !== '' ? (int) $_POST['email_template'] : null,
            'author_name'    => $_POST['author_name'],
            'author_state'   => isset($_POST['author_state']) ? (int) $_POST['author_state'] : null,
            'logo_width'     => isset($_POST['logo_width']) && $_POST['logo_width'] !== '' ? (int) $_POST['logo_width'] : null,
            'logo_height'    => isset($_POST['logo_height']) && $_POST['logo_height'] !== '' ? (int) $_POST['logo_height'] : null
        ];

        // Handle logo update or deletion
        if ($full_img_name) { $fields['logo'] = $full_img_name; }
        if (isset($_POST['img_delete'])) { $fields['logo'] = ''; }

        if ($mode == 0) {
            $DB->insert('glpi_plugin_deliveryterms_config', $fields);

        } else {
            // Capture previous values before update for traceability
            $old = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_config', 'WHERE' => ['id' => $mode]])->current();
            $DB->update('glpi_plugin_deliveryterms_config', $fields, ['id' => $mode]);

        }
        // Save filename_pattern if present
        if (isset($_POST['filename_pattern'])) {
            $pattern = trim($_POST['filename_pattern']);
            if ($mode == 0) {
                // update the just inserted row
                $last = $DB->request('glpi_plugin_deliveryterms_config', ['ORDER' => ['id' => 'DESC'], 'LIMIT' => 1])->current();
                if ($last) { $DB->update('glpi_plugin_deliveryterms_config', ['filename_pattern' => $pattern], ['id' => $last['id']]); }
            } else {
                $DB->update('glpi_plugin_deliveryterms_config', ['filename_pattern' => $pattern], ['id' => $mode]);
            }
        }

        // Handle plugin settings save (tab item types)
        if (isset($_POST['save'])) {
            // If checkboxes were left empty, treat as no types selected
            $selected = isset($_POST['tab_itemtypes']) ? $_POST['tab_itemtypes'] : [];
            $value = implode(',', $selected);
            // Upsert into settings table
            $existing = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_settings', 'WHERE' => ['option_key' => 'tab_itemtypes']])->current();
            if ($existing) {
                $DB->update('glpi_plugin_deliveryterms_settings', ['option_value' => $value], ['option_key' => 'tab_itemtypes']);
            } else {
                $DB->insert('glpi_plugin_deliveryterms_settings', ['option_key' => 'tab_itemtypes', 'option_value' => $value]);
            }
        }
        Session::addMessageAfterRedirect(__('Settings saved', 'deliveryterms'));
	}

    /**
     * Handles file uploads to GLPI_PICTURE_DIR
     * Returns filename on success, null on failure
     */
	static function uploadImage() {
		if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
			$ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
			if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
				$filename = "logo".time().".".$ext;
				move_uploaded_file($_FILES['logo']['tmp_name'], GLPI_PICTURE_DIR."/".$filename);
				return $filename;
			}
		}
		return null;
	}

    /**
     * Shows the table of existing templates below the form
     */
	static function showConfigs() {
		global $DB;
		echo "<div class='card shadow-sm'><div class='card-header bg-light'><h5><i class='fas fa-list'></i> ". __('Templates', 'deliveryterms') ."</h5></div>";
		echo "<table class='table table-hover mb-0'><thead><tr><th>" . __('Name', 'deliveryterms') . "</th><th class='text-center' width='120'>" . __('Action', 'deliveryterms') . "</th></tr></thead><tbody>";
		foreach ($DB->request(['FROM' => 'glpi_plugin_deliveryterms_config']) as $configs) {
			echo "<tr><td>".htmlescape($configs['name'])."</td><td class='text-center d-flex justify-content-center gap-1'>";
			// Edit button form
            echo "<form method='post' action='config.form.php'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
			echo Html::hidden('edit_id', ['value' => $configs['id']]);
			echo "<button type='submit' name='edit' class='btn btn-sm btn-info'><i class='fas fa-edit'></i></button></form>";
			// Delete button form
            echo "<form method='post' action='config.form.php'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
			echo Html::hidden('conf_id', ['value' => $configs['id']]);
				echo "<button type='submit' name='delete' class='btn btn-sm btn-danger' onclick='return confirm(" . __('Are you sure?', 'deliveryterms') . ")'><i class='fas fa-trash'></i></button></form></td></tr>";
		}
		echo "</tbody></table></div>";
	}

    /**
     * Removes a template configuration from the DB
     */
	static function deleteConfigs() {
		global $DB;
		if (isset($_POST['conf_id'])) {
			$confId = (int)$_POST['conf_id'];
			$DB->delete('glpi_plugin_deliveryterms_config', ['id' => $confId]);
			Session::addMessageAfterRedirect(__('Template deleted', 'deliveryterms'));
		}
	}

    /**
     * Renders the Email configuration form (Active when 'menu_mode' is 'e')
     */
	static function displayContentEmail() {
		global $DB;
		$email_edit_id = $_POST["email_edit_id"] ?? 0;

		if ($email_edit_id > 0) {
			$req = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig', 'WHERE' => ['id' => $email_edit_id ]]);			
			if ($row = $req->current()) {
				extract($row);
			}
		} else {
			$tname = ''; $email_subject = ''; $email_content = ''; $recipients = '';
		}

		echo "<div class='card shadow-sm'>";
		echo "<form name='email_template_edit' action='config.form.php' method='post'>";
		echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
		echo Html::hidden('email_edit_id', ['value' => $email_edit_id]);

		echo "  <div class='card-header'><h4 class='mb-0'>".($email_edit_id == 0 ? __('Crear Plantilla Email', 'deliveryterms') : __('Editar Plantilla Email', 'deliveryterms'))."</h4></div>";
		echo "  <div class='card-body'>";
		echo "  <table class='table borderless'>";
		echo "<tr><td>".__('Template name', 'deliveryterms')."*</td><td><input type='text' name='tname' class='form-control' value='".htmlescape($tname)."' required></td></tr>";
			echo "<tr><td>".__('Email subject', 'deliveryterms')."*</td><td><input type='text' name='email_subject' class='form-control' value='".htmlescape($email_subject)."' required></td></tr>";
			echo "<tr><td>".__('Email content', 'deliveryterms')."*</td><td><textarea name='email_content' class='form-control' rows='4' required>".htmlescape($email_content)."</textarea></td></tr>";
			echo "<tr><td>".__('Add emails (separate with ;)', 'deliveryterms')."*</td><td><textarea name='recipients' class='form-control' rows='2' required>".htmlescape($recipients)."</textarea></td></tr>";
		echo "  </table></div>";
			echo "  <div class='card-footer text-end'><button type='submit' name='save_email' class='btn btn-primary'>".__('Save', 'deliveryterms')."</button></div>";
		Html::closeForm();
		echo "</div><br>";
		
		self::showEmailConfigs();
	}

    /**
     * Logic to save Email data into the DB
     */
	static function saveEmailConfigs() {
		global $DB;
		$email_edit_id = (int)$_POST["email_edit_id"];
		$fields = [
			'tname'         => $_POST['tname'],
			'email_subject' => $_POST['email_subject'],
			'email_content' => $_POST['email_content'],
			'recipients'    => $_POST['recipients'],
            'send_user'     => 2 // Standard value
		];

		if ($email_edit_id == 0) {
			$DB->insert('glpi_plugin_deliveryterms_emailconfig', $fields);
			$DB->insert('glpi_plugin_deliveryterms_emailconfig', $fields);
		} else {
			// Capture previous values
			$old = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig', 'WHERE' => ['id' => $email_edit_id]])->current();
			$DB->update('glpi_plugin_deliveryterms_emailconfig', $fields, ['id' => $email_edit_id]);
		}
		Session::addMessageAfterRedirect(__('Email settings saved', 'deliveryterms'));
	}

    /**
     * Shows table of email templates
     */
	static function showEmailConfigs() {
		global $DB;
		echo "<div class='card shadow-sm'><div class='card-header bg-light'><h5><i class='fas fa-envelope'></i> " . __('Email Templates', 'deliveryterms') . "</h5></div>";
		echo "<table class='table table-hover mb-0'><thead><tr><th>" . __('Name', 'deliveryterms') . "</th><th class='text-center' width='120'>" . __('Action', 'deliveryterms') . "</th></tr></thead><tbody>";
		foreach ($DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig']) as $emailconfigs) {
			echo "<tr><td>".htmlescape($emailconfigs['tname'])."</td><td class='text-center d-flex justify-content-center gap-1'>";
			echo "<form method='post' action='config.form.php'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo Html::hidden('menu_mode', ['value' => 'e']);
			echo Html::hidden('email_edit_id', ['value' => $emailconfigs['id']]);
			echo "<button type='submit' name='email_edit' class='btn btn-sm btn-info'><i class='fas fa-edit'></i></button></form>";
			echo "<form method='post' action='config.form.php'>";
			echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
			echo Html::hidden('email_conf_id', ['value' => $emailconfigs['id']]);
					echo "<button type='submit' name='delete_email' class='btn btn-sm btn-danger' onclick='return confirm(" . __('Are you sure?', 'deliveryterms') . ")'><i class='fas fa-trash'></i></button></form></td></tr>";
		}
		echo "</tbody></table></div>";
	}

    /**
     * Removes an email template
     */
	static function deleteEmailConfigs() {
		global $DB;
		if (isset($_POST['email_conf_id'])) {
			$emailConfId = (int)$_POST['email_conf_id'];
			error_log('[deliveryterms] Audit: email_config_deleted (not recorded because audit table has been removed) id=' . $emailConfId);
			$DB->delete('glpi_plugin_deliveryterms_emailconfig', ['id' => $emailConfId]);
			Session::addMessageAfterRedirect(__('Email template deleted', 'deliveryterms'));
		}
	}
}