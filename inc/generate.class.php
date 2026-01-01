<?php
/**
 * Protocols Manager - Generate Class
 * This class handles the UI display and PDF generation logic within GLPI.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

require_once dirname(__DIR__) . '/dompdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PluginDeliverytermsGenerate extends CommonDBTM {

    /**
     * Define the tab name shown in the item view
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
		// Use d-inline-flex to eliminate excessive spacing and align both elements
        return "<span class='d-inline-flex align-items-center'><i class='fas fa-file-contract me-2'></i>" . __('Delivery Terms', 'deliveryterms') . "</span>";
    }

    /**
     * Renders the tab content
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
        global $CFG_GLPI;
        
        $tab_access = self::checkRights();
    
        if ($tab_access == 'w') {
            $PluginDeliverytermsGenerate = new self();
            $PluginDeliverytermsGenerate->showContent($item);
        } else {
            echo "<div class='center'><br><img src='".$CFG_GLPI['root_doc']."/pics/warning.png'><br>".__("Access denied")."</div>";
        }
    }
    
    /**
     * Checks if the active profile has the right to access the plugin tab
     */
    static function checkRights() {
        global $DB;
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return "";
        }

        $active_profile = $_SESSION['glpiactiveprofile']['id'];
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_deliveryterms_profiles',
            'WHERE' => ['profile_id' => $active_profile]
        ]);
        
        if ($row = $iterator->current()) {
            return $row['tab_access'];
        }

        return "";
    }

    /**
     * Helper to fetch extra user data like registration number, title, and category
     */
    private static function getUserExtraData($user_id) {
        global $DB;

        $data = [
            'registration_number' => "_______________",
            'title'               => "_______________",
            'category'            => "_______________"
        ];

        if (empty($user_id)) {
            return $data;
        }

        $iterator = $DB->request('glpi_users', ['id' => $user_id]);
        $user_row = $iterator->current();

        if ($user_row) {
            if (!empty($user_row['registration_number'])) {
                $data['registration_number'] = $user_row['registration_number'];
            }

            if (!empty($user_row['usertitles_id'])) {
                $title = Dropdown::getDropdownName('glpi_usertitles', $user_row['usertitles_id']);
                if (!empty($title) && $title != '&nbsp;') {
                    $data['title'] = $title;
                }
            }

            if (!empty($user_row['usercategories_id'])) {
                $category = Dropdown::getDropdownName('glpi_usercategories', $user_row['usercategories_id']);
                if (!empty($category) && $category != '&nbsp;') {
                    $data['category'] = $category;
                }
            }
        }

        return $data;
    }
    
    /**
     * Main UI function: Displays item list, generation form, and history
     */
    function showContent($item) {
        global $DB, $CFG_GLPI;

        // CHECK PERMISSIONS
        $can_create = PluginDeliverytermsProfile::currentUserHasRight('make_access');
        $can_delete = PluginDeliverytermsProfile::currentUserHasRight('delete_access');

        $itemid = null;
        $tstid  = null;

        // Ensure we are working with a User object
        if (get_class($item) !== "User") {
            if (!empty($item->id) && !empty($item->fields["users_id"])) {
                $itemid = $item->id;
                $tstid  = $item->fields["users_id"];
                $item = new User();
                $item->getFromDB($tstid);
            }
        }

        $id = $item->getField('id'); 
        $userData = self::getUserExtraData($id);
        
        $type_user  = $CFG_GLPI['linkuser_types'];
        $field_user = 'users_id';
        $rand       = mt_rand();
        $counter    = 0; 

        // Pre-fetch owner and author names
        $Owner = new User();
        $Owner->getFromDB($id);
        $owner = $Owner->getFriendlyName();

        $Author = new User();
        $Author->getFromDB(Session::getLoginUserID());
        $author = $Author->getFriendlyName();

        $rows_to_print = [];

        echo "<div class='container-fluid p-3'>";
        echo "<form method='post' name='user_field".$rand."' id='user_field".$rand."' action=\"" . $CFG_GLPI["root_doc"] . "/plugins/deliveryterms/front/generate.form.php\">";
        
        // --- TOP ACTION BAR (Template selection) ---
        // CHANGE: Only show generation options if user has 'make_access'
        if ($can_create) {
            echo "<div class='card mb-4 shadow-sm'>";
            echo "  <div class='card-header fw-bold'>".__('Protocol Generation Options', 'deliveryterms')."</div>";
            echo "  <div class='card-body bg-light'>";
            echo "      <div class='row g-3 align-items-end'>"; 
            echo "          <div class='col-md-4'>";
            echo "              <label class='form-label fw-bold'>".__('Template', 'deliveryterms')."</label>";
            echo "              <select required name='list' class='form-select'>";
                                $doc_types = $DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'glpi_plugin_deliveryterms_config']);
                                foreach ($doc_types as $list) {
                                    echo '<option value="'.htmlescape($list["id"]).'">'.htmlescape($list["name"]).'</option>';
                                }
            echo "              </select>";
            echo "          </div>";
            echo "          <div class='col-md-5'>";
            echo "              <label class='form-label fw-bold'>".__('Note', 'deliveryterms')."</label>";
            echo "              <input type='text' name='notes' class='form-control' placeholder='".__('Add a custom note to this protocol', 'deliveryterms')."'>";
            echo "          </div>";
            echo "          <div class='col-md-3'>";
            echo "              <button type='submit' name='generate' class='btn btn-primary w-100'><i class='fas fa-plus'></i> ".__('Create Protocol', 'deliveryterms')."</button>";
            echo "          </div>";
            echo "      </div>";
            echo "  </div>";
            echo "</div>";
        }

        // --- ASSETS TABLE ---
        // CHANGE: Only show Asset selection table if user can create protocols (otherwise they can't select items to generate)
        if ($can_create) {
            echo "<div class='card shadow-sm mb-4'>";
            echo "  <div class='card-header bg-white d-flex justify-content-between align-items-center py-2'>"; 
            echo "      <h5 class='mb-0'><i class='fas fa-laptop'></i> " . __('Select Items to Include', 'deliveryterms') ."</h5>";
            echo "      <button type='button' class='btn btn-success fw-bold addNewRow' id='addNewRow'><i class='fas fa-plus-circle'></i> ".__('Add Manual Field', 'deliveryterms')."</button>";
            echo "  </div>";
            echo "  <div class='table-responsive'>";
            echo "      <table class='table table-hover align-middle mb-0' id='additional_table'>";
            // ... (Resto de la tabla de assets igual que antes) ...
            echo "          <thead class='table-light text-center'>";
            echo "              <tr>";
            echo "                  <th width='40'><input type='checkbox' class='form-check-input checkall'></th>";
            echo "                  <th>".__('Type', 'deliveryterms')."</th>";
            echo "                  <th>".__('Manufacturer', 'deliveryterms')."</th>";
            echo "                  <th>".__('Model', 'deliveryterms')."</th>";
            echo "                  <th>".__('Name', 'deliveryterms')."</th>";
            echo "                  <th>".__('State', 'deliveryterms')."</th>";
            echo "                  <th>".__('Serial number', 'deliveryterms')."</th>";
            echo "                  <th>".__('Inventory number', 'deliveryterms')."</th>";
            echo "                  <th width='200'>".__('Comments', 'deliveryterms')."</th>";
            echo "              </tr>";
            echo "          </thead>";
            echo "          <tbody>";
        
        // --- DATA COLLECTION (Computers, Phones, etc.) ---
        foreach ($type_user as $itemtype) {
                if (!($itemObj = getItemForItemtype($itemtype))) { continue; }
                
                if ($itemObj->canView()) {
                    $itemtable = getTableForItemType($itemtype);
                    $criteria = ['FROM' => $itemtable, 'WHERE' => [$field_user => $id]];
                    if ($itemObj->maybeTemplate()) { $criteria['WHERE']['is_template'] = 0; }
                    if ($itemObj->maybeDeleted()) { $criteria['WHERE']['is_deleted'] = 0; }
    
                    $item_iterator = $DB->request($criteria);
                    $type_name = $itemObj->getTypeName();
    
                    foreach ($item_iterator as $data) {
                        $cansee = $itemObj->can($data["id"], READ);
                        $linkName = empty($data["name"]) ? $data["id"] : $data["name"];
                        $link = $cansee ? "<a href='".htmlescape($itemObj::getFormURLWithID($data['id']))."'>".htmlescape($linkName)."</a>" : htmlescape($linkName);
            
                        $man_name = !empty($data["manufacturers_id"]) ? explode(' ', trim(Dropdown::getDropdownName('glpi_manufacturers', $data['manufacturers_id'])))[0] : '';
                        
                        $mod_name = '';
                        $modeltypes = ["computer", "phone", "monitor", "networkequipment", "printer", "peripheral"];
                        foreach ($modeltypes as $prefix) {
                            if (!empty($data[$prefix.'models_id'])) {
                                $mod_name = Dropdown::getDropdownName('glpi_'.$prefix.'models', $data[$prefix.'models_id']);
                                break; 
                            }
                        }
                        
                        $sta_name = !empty($data["states_id"]) ? explode(' ', trim(Dropdown::getDropdownName('glpi_states', $data['states_id'])))[0] : '';
                        
                        $rows_to_print[$counter] = [
                            'type_label'    => $type_name,
                            'manufacturer'  => $man_name,
                            'model'         => $mod_name,
                            'name_link'     => $link,
                            'state'         => $sta_name,
                            'serial'        => $data["serial"] ?? '',
                            'otherserial'   => $data["otherserial"] ?? '',
                            'raw_name'      => $data["name"] ?? '',
                            'hidden_class'  => $itemtype,
                            'hidden_id'     => $data["id"] ?? '',
                        ];
                        $counter++;
                    }
                }
            }
    
            // --- DATA COLLECTION: Assets/Tablets ---
            if ($DB->tableExists('glpi_assets_assets')) {
                $criteria_assets = ['FROM' => 'glpi_assets_assets', 'WHERE' => ['users_id' => $id, 'is_deleted' => 0, 'is_template' => 0]];
                foreach ($DB->request($criteria_assets) as $data) {
                    $def_name = !empty($data['assets_assetdefinitions_id']) ? Dropdown::getDropdownName('glpi_assets_assetdefinitions', $data['assets_assetdefinitions_id']) : 'Tablet';
                    $man_name = !empty($data['manufacturers_id']) ? explode(' ', trim(Dropdown::getDropdownName('glpi_manufacturers', $data['manufacturers_id'])))[0] : '';
                    
                    $rows_to_print[$counter] = [
                        'type_label'    => $def_name,
                        'manufacturer'  => $man_name,
                        'model'         => !empty($data['assets_assetmodels_id']) ? Dropdown::getDropdownName('glpi_assets_assetmodels', $data['assets_assetmodels_id']) : '',
                        'name_link'     => htmlescape($data['name'] ?? ''),
                        'state'         => !empty($data['states_id']) ? explode(' ', trim(Dropdown::getDropdownName('glpi_states', $data['states_id'])))[0] : '',
                        'serial'        => $data['serial'] ?? '',
                        'otherserial'   => $data['otherserial'] ?? '',
                        'raw_name'      => $data['name'] ?? '',
                        'hidden_class'  => 'Tablet',
                        'hidden_id'     => $data['id'],
                    ];
                    $counter++;
                }
            }
			
			// --- MULTI-LEVEL SORTING (Type > Model) ---
			// Sort the complete array (including Assets) by item type and then by model
			usort($rows_to_print, function($a, $b) {
				// First, compare the Item Type (Computers, Monitors, Tablets, etc.)
				$typeComparison = strcasecmp($a['type_label'], $b['type_label']);
				
				// If the Type is the same, compare by the Model name
				if ($typeComparison === 0) {
					return strcasecmp($a['model'], $b['model']);
				}
				
				return $typeComparison;
			});

			// Now the loop will print all items in the new ascending order
            foreach ($rows_to_print as $idx => $row) {
                echo "<tr class='text-center'>";
                echo "<td><input type='checkbox' name='number[]' value='".htmlescape($idx)."' class='form-check-input child'></td>";
                echo "<td>".htmlescape($row['type_label'])."</td>";
                echo "<td>".($row['manufacturer'] ?: '-')."</td>";
                echo "<td>".($row['model'] ?: '-')."</td>";
                echo "<td class='text-start'>".$row['name_link']."</td>"; 
                echo "<td><span class='badge bg-info text-dark'>".($row['state'] ?: '-')."</span></td>";
                echo "<td>".($row['serial'] ?: '-')."</td>";
                echo "<td>".($row['otherserial'] ?: '-')."</td>";
				echo "<td><input type='text' name='comments[".htmlescape($idx)."]' class='form-control form-control-sm' placeholder='...'></td>";
                
                // Hidden inputs
                echo "<input type='hidden' name='classes[]' value='".htmlescape($row['hidden_class'])."'>";
                echo "<input type='hidden' name='ids[]' value='".htmlescape($row['hidden_id'])."'>";    
                echo "<input type='hidden' name='owner' value='".htmlescape($owner)."'>";
                echo "<input type='hidden' name='author' value='".htmlescape($author)."'>";
                echo "<input type='hidden' name='type_name[]' value='".htmlescape($row['type_label'])."'>";
                echo "<input type='hidden' name='man_name[]' value='".htmlescape($row['manufacturer'])."'>";
                echo "<input type='hidden' name='mod_name[]' value='".htmlescape($row['model'])."'>";
                echo "<input type='hidden' name='serial[]' value='".htmlescape($row['serial'])."'>";
                echo "<input type='hidden' name='otherserial[]' value='".htmlescape($row['otherserial'])."'>";
                echo "<input type='hidden' name='item_name[]' value='".htmlescape($row['raw_name'])."'>";
                echo "<input type='hidden' name='user_id' value='".htmlescape($id)."'>";
                echo "</tr>";
            }
            
            echo "          </tbody>";
            echo "      </table>";
            echo "  </div>";
            echo "</div>";
        } // END IF CAN_CREATE
        Html::closeForm();

        // --- EMAIL MODAL ---
        echo "
        <div class='modal fade' id='motus' tabindex='-1' aria-hidden='true'>
          <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
              <div class='modal-header bg-success text-white'>
                <h5 class='modal-title'><i class='fas fa-envelope'></i> " .__('Send Protocol by Email', 'deliveryterms')."</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
              </div>
              <form method='post' action='".$CFG_GLPI["root_doc"]."/plugins/deliveryterms/front/generate.form.php'>
                <div class='modal-body'>";
        
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo '<input type="hidden" id="dialogVal" name="doc_id" value="">';
        
        echo "      <div class='card mb-3 p-3'>
                        <div class='form-check mb-2'>
                            <input class='form-check-input send_type' type='radio' name='send_type' id='manually' value='1'>
                            <label class='form-check-label fw-bold' for='manually'>".__('Manual Entry', 'deliveryterms')."</label>
                        </div>
                        <div class='ps-4'>
                            <input type='text' name='em_list' class='form-control mb-2 man_recs' placeholder='Recipients (e.g. user@domain.com; manager@domain.com)'>
                            <input type='text' name='email_subject' class='form-control mb-2 man_recs' placeholder='Subject'>
                            <textarea name='email_content' class='form-control man_recs' rows='3' placeholder='Message body...'></textarea>
                        </div>
                    </div>";

        echo "      <div class='card p-3'>
                        <div class='form-check mb-2'>
                            <input class='form-check-input send_type' type='radio' name='send_type' id='auto' value='2' checked>
                            <label class='form-check-label fw-bold' for='auto'>".__('Use Email Template', 'deliveryterms')."</label>
                        </div>
                        <div class='ps-4'>
                            <select name='e_list' id='auto_recs' class='form-select'>";
                            $email_configs = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig']);
                            foreach ($email_configs as $list) {
                                $val = htmlescape($list["recipients"]."|".$list["email_subject"]."|".$list["email_content"]."|".$list["send_user"]);
                                echo "<option value='$val'>".htmlescape($list["tname"])."</option>";
                            }
        echo "              </select>
                        </div>
                    </div>";

        if(!empty($author)) echo '<input type="hidden" name="author" value="'.htmlescape($author).'">';
        if(!empty($owner)) echo '<input type="hidden" name="owner" value="'.htmlescape($owner).'">';
        echo '<input type="hidden" name="user_id" value="'.htmlescape($id).'">';

        echo "  </div>
                <div class='modal-footer'>
                  <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>".__('Cancel', 'deliveryterms')."</button>
                  <button type='submit' name='send' class='btn btn-success'><i class='fas fa-paper-plane'></i> ".__('Send', 'deliveryterms')."</button>
                </div>
              </form>
            </div>
          </div>
        </div>";

        // --- HISTORY ---
        echo "<div class='card shadow-sm mt-4'>";
        echo "  <div class='card-header fw-bold'><i class='fas fa-history'></i> " . __('Generated Protocols History', 'deliveryterms') . "</div>";
        echo "  <form method='post' name='docs_form' action='".$CFG_GLPI["root_doc"]."/plugins/deliveryterms/front/generate.form.php'>";
        echo    Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        
        echo "  <div class='p-2 bg-light border-bottom d-flex align-items-center'>"; 
        
        // CHANGE: Only show delete button if user has 'delete_access'
        if ($can_delete) {
            echo "      <button type='submit' name='delete' class='btn btn-sm btn-outline-danger' onclick='return confirm(" . __('Are you sure you want to delete selected items?', 'deliveryterms') . ")'><i class='fas fa-trash'></i> " . __('Delete Selected', 'deliveryterms') ."</button>";
        }

        echo "  </div>";


        echo "  <div class='table-responsive'>";
        echo "      <table class='table table-hover align-middle mb-0' id='myTable'>";
        echo "          <thead class='table-light text-center'>";
        echo "              <tr>";
        // CHANGE: Hide check-all checkbox if user cannot delete
        if ($can_delete) {
            echo "                  <th width='40'><input type='checkbox' class='form-check-input checkalldoc'></th>";
        } else {
             echo "                  <th width='40'></th>";
        }
        echo "                  <th>".__('Name', 'deliveryterms')."</th>";
        echo "                  <th>".__('Template', 'deliveryterms')."</th>";
        echo "                  <th>".__('Creation Date', 'deliveryterms')."</th>";
        echo "                  <th>".__('Download', 'deliveryterms')."</th>";
        echo "                  <th>".__('Creator', 'deliveryterms')."</th>";
        echo "                  <th>".__('Comment', 'deliveryterms')."</th>";
        echo "                  <th>".__('Action', 'deliveryterms')."</th>";
        echo "              </tr>";
        echo "          </thead>";
        echo "          <tbody>";
                        self::getAllForUser($id, $can_delete); // Pass permission
        echo "          </tbody>";
        echo "      </table>";
        echo "  </div>";
        echo "  </form>";
        echo "</div>";

        echo "</div>"; 

        return true;
    }
    
    /**
     * Populate history table with user's generated protocols
     */
    static function getAllForUser($id, $can_delete = true) { // CHANGE: Add parameter
        global $DB;
    
        $iterator = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_protocols', 'WHERE' => ['user_id' => $id]]);

        foreach ($iterator as $exports) {
            echo "<tr class='text-center'>";
            
            // CHANGE: Only show checkbox if user can delete
            if ($can_delete) {
                echo "<td><input type='checkbox' name='docnumber[]' value='".htmlescape($exports['document_id'])."' class='form-check-input docchild'></td>";
            } else {
                 echo "<td></td>";
            }
    
            echo "<td>";
            $Doc = new Document();
            if ($Doc->getFromDB($exports['document_id'])) {
                 echo $Doc->getLink();
            } else {
                 echo "<span class='text-muted'>".__('Document not found', 'deliveryterms')."</span>";
            }
            echo "</td>";
    
            echo "<td>".htmlescape($exports['document_type'])."</td>";
            echo "<td>".htmlescape($exports['gen_date'])."</td>";
            echo "<td>".($Doc->fields ? $Doc->getDownloadLink() : '-')."</td>";
            echo "<td>".htmlescape($exports['author'])."</td>";
            echo "<td><small>".($Doc->fields ? htmlescape($Doc->getField("comment")) : '')."</small></td>";
            echo "<td>";
            echo "<button type='button' class='btn btn-sm btn-outline-success send-email-btn' 
                    data-docid='".htmlescape($exports['document_id'])."' 
                    data-bs-toggle='modal' 
                    data-bs-target='#motus'><i class='fas fa-envelope'></i> " . __('Send Email', 'deliveryterms') ."</button>";
            echo "</td>";
            echo "</tr>";
        }
    }
    
    /**
     * Logic to process POST data, generate PDF and store in GLPI
     */
    static function makeProtocol() 
    {
            global $DB;
            
            // CHANGE: Security check
            if (!PluginDeliverytermsProfile::currentUserHasRight('make_access')) {
                Session::addMessageAfterRedirect("Access denied", false, ERROR);
                return;
            }

            // Collect POST data
            $number     = $_POST['number'] ?? [];
            $type_name  = $_POST['type_name'] ?? [];
            $man_name   = $_POST['man_name'] ?? [];
            $mod_name   = $_POST['mod_name'] ?? [];
            $serial     = $_POST['serial'] ?? [];
            $otherserial= $_POST['otherserial'] ?? [];
            $item_name  = $_POST['item_name'] ?? [];
            $comments   = $_POST['comments'] ?? [];
            
            $owner  = $_POST['owner'] ?? '';
            $author = $_POST['author'] ?? '';              
            $doc_no = $_POST['list'] ?? 0;
            $id     = $_POST['user_id'] ?? 0;
            $notes  = $_POST['notes'] ?? '';
            
            $userExtra = self::getUserExtraData($id);
            $registration_number = $userExtra['registration_number'];
            $usertitle_name      = $userExtra['title'];
            $usercategory_name   = $userExtra['category'];

            // Fetch template configuration
            $req = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_config', 'WHERE' => ['id' => $doc_no ]]);
            
            if ($row = $req->current()) {
                $content = html_entity_decode($row["content"], ENT_QUOTES, "UTF-8");
                $upper_content = html_entity_decode($row["upper_content"], ENT_QUOTES, "UTF-8");
                $footer = html_entity_decode($row["footer"], ENT_QUOTES, "UTF-8");

                $serial_mode  = $row["serial_mode"] ?? 1;
                $author_state = $row["author_state"] ?? 1;
                $author_name  = $row["author_name"] ?? '';
                $breakword    = $row["breakword"] ?? 0;
                $title        = $row["title"];
                $title_template = $row["name"];
                $full_img_name  = $row["logo"];
                $font           = !empty($row["font"]) ? $row["font"] : 'dejavusans';
                $fontsize       = !empty($row["fontsize"]) ? $row["fontsize"] : '9';
                $city           = $row["city"];
                $orientation    = $row["orientation"];
                $email_mode     = $row["email_mode"];
                $email_template = $row["email_template"];
                
                $replacements = [
                    "{cur_date}" => date("d.m.Y"),
                    "{owner}"    => $owner,
                    "{admin}"    => $author,
                    "{reg_num}"  => $registration_number,
                    "{title}"    => $usertitle_name,
                    "{category}" => $usercategory_name
                ];

                $title = str_replace("{owner}", $owner, $title);
                foreach ($replacements as $key => $val) {
                    $content = str_replace($key, $val, $content);
                    $upper_content = str_replace($key, $val, $upper_content);
                }

                $logo_width = $row["logo_width"] ?? null;
                $logo_height = $row["logo_height"] ?? null;
            } else {
                return;
            }

            // Email template
            $email_content = ''; $email_subject = ''; $recipients = ''; $send_user = 0;
            if (!empty($email_template)) {
                $req2 = $DB->request(['FROM' => 'glpi_plugin_deliveryterms_emailconfig', 'WHERE' => ['id' => $email_template ]]);
                if ($row2 = $req2->current()) {
                    $send_user = $row2["send_user"];
                    $email_subject = str_replace(["{owner}", "{admin}", "{cur_date}"], [$owner, $author, date("d.m.Y")], $row2["email_subject"]);
                    $email_content = str_replace(["{owner}", "{admin}", "{cur_date}"], [$owner, $author, date("d.m.Y")], $row2["email_content"]);
                    $recipients = $row2["recipients"];
                }
            }
            
            // Logo
            if (empty($full_img_name)) {
                $backtop = "20mm"; $islogo = 0; $logo = '';
            } else {
                $logo = GLPI_PICTURE_DIR . '/' . $full_img_name;
                $backtop = "40mm"; $islogo = 1;
            }
            
            // Table for PDF
            $table_html = "<table style='width:100%; border-collapse:collapse; font-size:{$fontsize}pt;' border='1'>";
            $table_html .= "<tr style='background-color:#eee;'><th>".__('Type', 'deliveryterms')."</th><th>".__('Manufacturer', 'deliveryterms')."</th><th>".__('Model', 'deliveryterms')."</th><th>".__('Name', 'deliveryterms')."</th><th>".__('Serial number', 'deliveryterms')."</th><th>".__('Inventory number', 'deliveryterms')."</th><th>".__('Comments', 'deliveryterms')."</th></tr>";
            
            if (is_array($number)) {
                foreach ($number as $idx) {
                    $t = htmlescape($type_name[$idx] ?? '');
                    $m = htmlescape($man_name[$idx] ?? '');
                    $mo = htmlescape($mod_name[$idx] ?? '');
                    $n = htmlescape($item_name[$idx] ?? '');
                    $s = htmlescape($serial[$idx] ?? '');
                    $os = htmlescape($otherserial[$idx] ?? '');
                    $cmt = htmlescape($comments[$idx] ?? '');
                    $table_html .= "<tr><td>{$t}</td><td>{$m}</td><td>{$mo}</td><td>{$n}</td><td>{$s}</td><td>{$os}</td><td>{$cmt}</td></tr>";
                }
            }
            $table_html .= "</table>";
            
            $content = str_replace("{items_table}", $table_html, $content);
            $upper_content = str_replace("{items_table}", $table_html, $upper_content);
            
            ob_start();
            include dirname(__FILE__).'/template.php';
            $file_content = ob_get_clean();

            $options = new Options();
            $options->set('defaultFont', $font);
            $options->set('isRemoteEnabled', true); 
			$options->set('isHtml5ParserEnabled', true);

            $html2pdf = new Dompdf($options);
            $html2pdf->loadHtml($file_content);
            $html2pdf->setPaper('A4', $orientation);
            $html2pdf->render();

            $doc_name = str_replace(' ', '_', $title)."-".date('dmY').'.pdf';
            $output = $html2pdf->output();

            // Write the file into GLPI upload root (Document->moveUploadedDocument forbids slashes in filename)
            $upload_path = GLPI_UPLOAD_DIR . '/' . $doc_name;
            file_put_contents($upload_path, $output);

            // Also keep a copy in PDF/TERMS for organizational purpose (not used by Document->add)
            $pdf_terms_dir = GLPI_UPLOAD_DIR . '/PDF/TERMS';
            if (!is_dir($pdf_terms_dir)) {
                @mkdir($pdf_terms_dir, 0755, true);
            }
            @copy($upload_path, $pdf_terms_dir . '/' . $doc_name);

            // Create Document using the filename only (no slashes) so GLPI can move it securely
            $doc_id = self::createDoc($doc_name, $owner, $notes, $title, $id); 
            
            if ($email_mode == 1) {
                self::sendMail($doc_id, $send_user, $email_subject, $email_content, $recipients, $id);
            }
            
            $gen_date = date('Y-m-d H:i:s');

            $DB->insert('glpi_plugin_deliveryterms_protocols', [
                'name' => $doc_name,
                'gen_date' => $gen_date,
                'author' => $author,
                'user_id' => $id,
                'document_id' => $doc_id,
                'document_type' => $title_template
            ]);

            $DB->insert('glpi_documents_items', [
                'documents_id' => $doc_id, 'items_id' => $id, 'itemtype' => 'User',
                'users_id' => $id, 'date_creation' => $gen_date, 'date_mod' => $gen_date, 'date' => $gen_date,
            ]);

            if (isset($_POST["number"]) && is_array($_POST["number"])) {
                foreach ($_POST["number"] as $itms) {
                    if (isset($_POST["classes"][$itms]) && isset($_POST["ids"][$itms])) {
                        $DB->insert('glpi_documents_items',[
                            'documents_id' => $doc_id,
                            'items_id' => $_POST["ids"][$itms],
                            'itemtype' => $_POST["classes"][$itms],
                            'users_id' => $id, 'date_creation' => $gen_date, 'date_mod' => $gen_date, 'date' => $gen_date,
                        ]);
                    }
                }
            }
    }

    static function createDoc($doc_name, $owner, $notes, $title, $id) {
        global $DB;
        $entity = Session::getActiveEntity();
        $req1 = $DB->request('glpi_users', ['id' => $id]);
        if ($row1 = $req1->current()) { $entity = $row1["entities_id"]; }
        if (!Session::haveAccessToEntity($entity)) { $entity = Session::getActiveEntity(); }
        
        $doc_cat_id = 0;
        $req2 = $DB->request('glpi_documentcategories', ['name' => $title]);
        if ($row2 = $req2->current()) { $doc_cat_id = $row2["id"]; }
        
        $doc = new Document();
        $input = [
            "entities_id" => $entity,
            "name" => date('mdY_Hi'),
            "upload_file" => $doc_name,
            "documentcategories_id" => $doc_cat_id,
            "mime" => "application/pdf",
            "date_mod" => date("Y-m-d H:i:s"),
            "users_id" => Session::getLoginUserID(),
            "comment" => $owner."\r".$notes
        ];
        
        $doc->check(-1, CREATE, $input);
        return $doc->add($input);
    }
    
    static function deleteDocs() {
        global $DB;

        // CHANGE: Security check
        if (!PluginDeliverytermsProfile::currentUserHasRight('delete_access')) {
             Session::addMessageAfterRedirect("Access denied", false, ERROR);
             return;
        }

        if (isset($_POST['docnumber']) && is_array($_POST['docnumber'])) {
            foreach ($_POST['docnumber'] as $del_key) {
                // Remove plugin protocol entry
                $DB->delete('glpi_plugin_deliveryterms_protocols', ['document_id' => $del_key]);
                // Restore previous behavior: always delete underlying document
                try {
                    $doc = new Document();
                    $doc->delete(['id' => $del_key]);
                } catch (\Throwable $e) {
                    error_log('[deliveryterms] Failed to delete document id ' . $del_key . ': ' . $e->getMessage());
                }
            }
        }

    }

    static function sendMail($doc_id, $send_user, $email_subject, $email_content, $recipients, $id) {
        global $CFG_GLPI, $DB;
        $nmail = new GLPIMailer();
        $nmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"] ?? '', false);
        
        $req = $DB->request('glpi_documents', ['id' => $doc_id]);
        if ($row = $req->current()) {
            $docFilepath = $row["filepath"] ?? '';
            $docFilename = $row["filename"] ?? '';
            $candidate = rtrim(GLPI_VAR_DIR . '/' . $docFilepath, '/');

            // If filepath looks like a directory, append the filename
            if (is_dir($candidate) && !empty($docFilename)) {
                $candidate = $candidate . '/' . $docFilename;
            }

            // Normalise relative ./ prefixes
            $candidate = preg_replace('#^\./+#', '', $candidate);

            if (!empty($candidate) && is_readable($candidate) && is_file($candidate)) {
                try {
                    $nmail->addAttachment($candidate, $docFilename);
                } catch (\Throwable $e) {
                    error_log('[deliveryterms] Failed to attach document: ' . $e->getMessage());
                }
            } else {
                error_log('[deliveryterms] Attachment skipped; file not found or unreadable: ' . GLPI_VAR_DIR . '/' . $docFilepath);
            }
        }
        
        // Sanitize recipients and add safely to avoid RFC errors
        $add_safe_address = function($address) use (&$nmail) {
            $address = trim($address);
            if (empty($address)) { return false; }
            if (preg_match('/<([^>]+)>/', $address, $m)) {
                $candidate = trim($m[1]);
            } else { $candidate = $address; }
            if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                error_log('[deliveryterms] Invalid email address skipped: ' . $address);
                return false;
            }
            try { $nmail->AddAddress($candidate, ''); return true; } catch (\Throwable $e) { error_log('[deliveryterms] AddAddress failed for ' . $candidate . ': ' . $e->getMessage()); return false; }
        };

        if ($send_user == 1) {
            $req2 = $DB->request(['FROM' => 'glpi_useremails', 'WHERE' => ['users_id' => $id, 'is_default' => 1]]);
            if ($row2 = $req2->current()) { $add_safe_address($row2["email"]); }
        }
        
        foreach(explode(';', $recipients) as $recipient) {
            if (!empty(trim($recipient))) { $add_safe_address($recipient); }
        }
        
        $nmail->Subject = $email_subject;
        $nmail->Body = $email_content;
        
        if (!$nmail->Send()) {
            Session::addMessageAfterRedirect(__('Failed to send email', 'deliveryterms'), false, ERROR);
            return false;
        }
        Session::addMessageAfterRedirect(__('Email sent', 'deliveryterms'));
        return true; 
    }

    static function sendOneMail($id=null) {
        global $CFG_GLPI, $DB;
        if (is_null($id) && isset($_POST['user_id'])) { $id = $_POST['user_id']; }
        
        $nmail = new GLPIMailer();
        $nmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"] ?? '', false);
        
        $doc_id = $_POST["doc_id"] ?? 0;
        $recipients = $_POST["em_list"] ?? '';
        $email_subject = $_POST["email_subject"] ?? "GLPI Protocols Manager mail";
        $email_content = $_POST['email_content'] ?? ' ';
        $send_user = 0;

        if (isset($_POST['e_list'])) {
            $result = explode('|', $_POST['e_list']);
            if (count($result) >= 4) {
                $recipients = $result[0]; $email_subject = $result[1]; $email_content = $result[2]; $send_user = $result[3];
            }
        }
        
        $replace = ["{owner}" => $_POST["owner"] ?? '', "{admin}" => $_POST["author"] ?? '', "{cur_date}" => date("d.m.Y")];
        foreach ($replace as $k => $v) {
            $email_content = str_replace($k, $v, $email_content);
            $email_subject = str_replace($k, $v, $email_subject);
        }
        // Also replace placeholders inside recipients (if templates include {owner}, etc.)
        foreach ($replace as $k => $v) {
            $recipients = str_replace($k, $v, $recipients);
        }
        
        $final_recipients = [];
        // Helper to validate and add an address safely
        $add_safe_address = function($address) use (&$nmail) {
            $address = trim($address);
            if (empty($address)) { return null; }
            // If address is in form 'Name <email@domain>' extract email
            if (preg_match('/<([^>]+)>/', $address, $m)) {
                $candidate = trim($m[1]);
            } else {
                $candidate = $address;
            }
            if (!filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                error_log('[deliveryterms] Invalid email address skipped: ' . $address);
                return null;
            }
            try {
                $nmail->AddAddress($candidate, '');
                return $candidate;
            } catch (\Throwable $e) {
                error_log('[deliveryterms] AddAddress failed for ' . $candidate . ': ' . $e->getMessage());
                return null;
            }
        };

        if ($send_user == 1 && !empty($id)) {
            $req2 = $DB->request(['FROM' => 'glpi_useremails', 'WHERE' => ['users_id' => $id, 'is_default' => 1]]);
            if ($row2 = $req2->current()) { 
                $added = $add_safe_address($row2["email"]);
                if ($added) { $final_recipients[] = $added; }
            }
        }
        
        foreach(explode(';', $recipients) as $recipient) {
            $recipient = trim($recipient);
            if (empty($recipient)) { continue; }
            $added = $add_safe_address($recipient);
            if ($added) { $final_recipients[] = $added; }
        }

        if (empty($final_recipients)) {
            Session::addMessageAfterRedirect(__('No recipients specified', 'deliveryterms'), false, ERROR);
            return false;
        }
        
        if (!empty($doc_id)) {
            $req = $DB->request('glpi_documents', ['id' => $doc_id]);
            if ($row = $req->current()) {
                $docFilepath = $row["filepath"] ?? '';
                $docFilename = $row["filename"] ?? '';
                $candidate = rtrim(GLPI_VAR_DIR . '/' . $docFilepath, '/');

                if (is_dir($candidate) && !empty($docFilename)) {
                    $candidate = $candidate . '/' . $docFilename;
                }

                $candidate = preg_replace('#^\./+#', '', $candidate);

                if (!empty($candidate) && is_readable($candidate) && is_file($candidate)) {
                    try {
                        $nmail->addAttachment($candidate, $docFilename);
                    } catch (\Throwable $e) {
                        error_log('[deliveryterms] Failed to attach document (sendOneMail): ' . $e->getMessage());
                    }
                } else {
                    error_log('[deliveryterms] Attachment skipped (sendOneMail); file not found or unreadable: ' . GLPI_VAR_DIR . '/' . $docFilepath);
                }
            }
        }
        
        $nmail->IsHtml(true);
        $nmail->Subject = $email_subject;
        $nmail->Body    = nl2br(stripcslashes($email_content));
        
        if (!$nmail->Send()) {
            Session::addMessageAfterRedirect(__('Failed to send email', 'deliveryterms'), false, ERROR);
            return false;
        }
        Session::addMessageAfterRedirect(__('Email sent', 'deliveryterms')." to ".implode(", ", $final_recipients));
        return true;
    }       
}
?>

<script>
$(function(){
    // Toggle manual/template email
    $(".man_recs").prop('disabled', true);
    $('.send_type').click(function(){
        if($(this).prop('id') == "manually"){
            $(".man_recs").prop('disabled', false);
            $("#auto_recs").prop('disabled', true);
        }else{
            $(".man_recs").prop('disabled', true);
            $("#auto_recs").prop('disabled', false);
        }
    });

    // Checkboxes
    $('.checkall').on('click', function() {
        $('.child').prop('checked', this.checked)
    });
    $('.child').prop('checked', true);

    $('.checkalldoc').on('click', function() {
        $('.docchild').prop('checked', this.checked)
    });

    // Dynamic rows
    var counter = $('.child').length;
    $("#addNewRow").on("click", function () {
        var newRow = $("<tr class='text-center'>");
        var cols = "";
        cols += '<td><button type="button" class="btn btn-sm btn-danger ibtnDel"><i class="fas fa-times"></i></button></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="type_name[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="man_name[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="mod_name[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="item_name[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="serial[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="otherserial[]"></td>';
        cols += '<td><input type="text" class="form-control form-control-sm" name="comments[' + counter + ']"><input type="hidden" name="number[]" value="' + counter + '"></td>';
        
        newRow.append(cols);
        $("#additional_table").append(newRow);
        counter++;
    });
    
    $("#additional_table").on("click", ".ibtnDel", function (event) {
        $(this).closest("tr").remove();
    });

    // Modal data transfer
    $(document).on('click', '.send-email-btn', function() {
        var docId = $(this).data('docid');
        $('#dialogVal').val(docId);
    });
});
</script>