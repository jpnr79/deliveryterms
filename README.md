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
  - The workflow now includes a **CLI UI render check** (verifies the generate page wording and icon spacing) and an **optional E2E step** that runs the `ui_generate_curl.sh` script against a live GLPI instance (guarded by secrets).

**Optional E2E in CI (how to enable)**
- Set the following repository secrets in GitHub (Repository → Settings → Secrets):
  - `GLPI_UI_USER` — username to log in to GLPI (e.g., `glpi`)
  - `GLPI_UI_PASS` — password for that user
  - `GLPI_TEMPLATE_ID` — ID of an existing deliveryterms template (used by the test)
  - `GLPI_TARGET_USER_ID` — ID of the user to which the term applies
- When these secrets are present, the workflow will start a local PHP server and run the headless E2E script. The step is intentionally guarded to avoid failures on repositories without a test GLPI instance.

**Running the UI E2E locally**
- Start a local PHP server serving GLPI's public dir (example):
  - `nohup php -S 127.0.0.1:8000 -t /var/www/glpi/public &`
- Run the headless script (example):
  - `./plugins/deliveryterms/tests/ui_generate_curl.sh http://127.0.0.1:8000 <USERNAME> <PASSWORD> <TEMPLATE_ID> <TARGET_USER_ID>`
- The script will:
  - login, extract a CSRF token (supports both the meta `glpi:csrf_token` and `<input name="_glpi_csrf_token">`),
  - submit the generation form, and
  - report whether a generation was triggered (check DB `glpi_plugin_deliveryterms_protocols` and `files/PDF` for the generated file).

**Note:** Keep secrets safe — prefer GitHub repository secrets over passing credentials on the command line or in PRs.

**Note:** The simulated generation test uses Dompdf (bundled in the plugin) and requires system fonts; the CI workflow installs fonts-dejavu to ensure rendering.

