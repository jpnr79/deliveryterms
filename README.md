# Delivery Terms
GLPI Plugin to make PDF reports with user inventory.  
**Only supports for glpi v10.0 and PHP version 8.0.15**   
**Removed parts of the original code using additionnal fields plugin**
## Features
* Making PDFs with all or selected user inventory
* Saving protocols in GLPI Documents
* Possibility to create different protocol templates
* Templates have configurable name, font, orientation, logo image, city, content and footer
* Possibility to make comments to any selected item
* Showing Manufacturer (only first word to be clearly) and Model of item
* Showing serial number or inventory number in one or two columns
* Possibility to add custom rows
* Possibility to add notes to export
* Menu to access easily to protocols Manager

## Compatibility
GLPI 11.0
PHP 8.0.15
## Installation
1. Download and extract package
2. Copy deliveryterms folder to GLPI plugins directory
3. Go to GLPI Plugin Menu and click 'install' and then 'activate'

4. If translations do not appear after updating locales, clear GLPI cache (Administration → Maintenance → Clear cache) or remove files in `files/_cache` and `files/_locales`. The installer will place compiled `.mo` files under `locales/<lang>/LC_MESSAGES/deliveryterms.mo`.

![Setup](https://raw.githubusercontent.com/mateusznitka/protocolsmanager/master/docs/img/setup.gif)
## Updating
1. Extract package and copy to plguins directory (replace old protocolsmanager folder)
2. Go to GLPI Plugin Menu, you should see 'to update' status.
3. Click on 'install' and then 'activate'
## Preparing
1. Go to Profiles and click on profile you want to add permissions to plugin
2. Select permissions and save
3. Go to Plugins -> Protocols manager
4. Edit default or create new template: Fill all or some textboxes, choose your font and logo if you want
5. Save template / templates

![Preparing](https://raw.githubusercontent.com/mateusznitka/protocolsmanager/master/docs/img/config.gif)
## Using the plugin
1. Go to Administration -> Users and click on user login
2. Go to Protocols Manager tab
3. Select some or all items
4. Write a comment to an item (optional)
5. Add and fill custom rows (optional)
6. Write a note to export (optional)
7. Select your template from list and click "Create"
8. Your protocol is on list above now, you can open it in new tab. It is available in Managament -> Documents too.
9. You can delete all or some protocols by selecting them and click "Delete".

![Generate](https://raw.githubusercontent.com/mateusznitka/protocolsmanager/master/docs/img/generate_standard.gif)
## Notes
1. Generated items depends on what you assign to the user in GLPI
2. You can edit template core in HTML by editing template.php file in deliveryterms/inc directory
## To do
1. More customization
2. Give an idea...
## Contact 
mateusznitka01@gmail.com
## Buy me a coffee :)
If you like my work, you can support me by a donate here:

<a href="https://www.buymeacoffee.com/mateusznitka" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-yellow.png" alt="Buy Me A Coffee" height="51px" width="210px"></a>

## Supporters
Thanks to Nomino for supporting this project - [nomino.pl](https://nomino.pl/)

![Nomino](https://raw.githubusercontent.com/mateusznitka/protocolsmanager/master/docs/img/logo-nomino.png)
