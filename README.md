# Delivery Terms
GLPI Plugin to make PDF reports with user inventory.  
**Only supports for glpi v10.0 and PHP version 8.0.15**   
**Removed parts of the original code using additionnal fields plugin**
## Features
* Making PDFs with all or selected user inventory
* Saving protocols in GLPI Documents
* Possibility to create different protocol templates
* Templates have configurable name, font, orientation, logo image, city, content and footer
* Filename patterns per-template using placeholders (e.g. `{type}-{YYYY}-{seq}`). Supported placeholders: `{type}`, `{YYYY}`, `{seq}`, `{owner}`, `{date}`, `{docmodel}`
* TinyMCE enhancements: the plugin adds a small "Table" menu to the editor used in templates to make table editing (insert/delete rows/columns) easier for users.
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

## Testing & CI
- Integration sequence check (local):
  - php plugins/deliveryterms/tests/integration_check_sequence.php
  - php plugins/deliveryterms/tests/integration_check_sequence.php --cleanup (restores sequence counter)

- Simulated generation (local):
  - php plugins/deliveryterms/tests/simulate_generate.php
  - php plugins/deliveryterms/tests/simulate_generate.php --cleanup (deletes created DB rows and file)

- Headless UI helper (login + submit form using curl):
  - ./plugins/deliveryterms/tests/ui_generate_curl.sh <GLPI_URL> <USERNAME> <PASSWORD> <TEMPLATE_ID> <TARGET_USER_ID>

- CI:
  - GitHub Actions workflow runs on push to `main` and executes the integration sequence test and the simulate generation test in a MySQL service environment.

**Note:** The simulated generation test uses Dompdf (bundled in the plugin) and requires system fonts; the CI workflow installs fonts-dejavu to ensure rendering.

