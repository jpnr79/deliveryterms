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
* Inline PDF preview modal in template editor ("Preview" button) — sends current editor content to preview endpoint and displays the generated PDF without opening a new tab.
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

- Playwright E2E preview test (clicks Preview, waits for preview.php and asserts PDF):
  - File: `tests/e2e/preview.spec.js`
  - Run locally:
    - `npm init -y && npm i -D @playwright/test`
    - `npx playwright install`
    - Set env vars: `GLPI_URL`, `GLPI_UI_USER`, `GLPI_UI_PASS`, `GLPI_TEMPLATE_ID`
    - Run: `GLPI_URL=http://127.0.0.1:8000 GLPI_UI_USER=glpi GLPI_UI_PASS=glpi GLPI_TEMPLATE_ID=1 npx playwright test tests/e2e/preview.spec.js`
  - The test logs in, opens a template edit page, clicks the **Preview** button, waits for the `/plugins/deliveryterms/front/preview.php` response and asserts the `Content-Type` header contains `application/pdf`, then verifies the modal shows an iframe with a `blob:` URL.

- CI:
  - GitHub Actions workflow runs on push to `main` and executes the integration sequence test and the simulate generation test in a MySQL service environment.
  - The workflow now includes a **CLI UI render check** (verifies the generate page wording and icon spacing) and an **optional E2E step** that runs the `ui_generate_curl.sh` script against a live GLPI instance (guarded by secrets).

**Optional E2E in CI (how to enable)**
- Set the following repository secrets in GitHub (Repository → Settings → Secrets):
  - `GLPI_UI_USER` — username to log in to GLPI (e.g., `glpi`)
  - `GLPI_UI_PASS` — password for that user
  - `GLPI_TEMPLATE_ID` — ID of an existing deliveryterms template (used by the test)
  - `GLPI_TARGET_USER_ID` — ID of the user to which the term applies
- When these secrets are present, the workflow will start a local PHP server and run the headless E2E script (curl-based). The step is intentionally guarded to avoid failures on repositories without a test GLPI instance.

- The workflow also includes an optional **Playwright** E2E job that runs `tests/e2e/preview.spec.js` when the secrets `GLPI_UI_USER`, `GLPI_UI_PASS`, and `GLPI_TEMPLATE_ID` are set; the job installs Node and Playwright on the runner and executes the preview spec. The job is configured to retain video, trace and screenshots on failure and uploads these artifacts to the workflow artifacts (look for `playwright-preview-artifacts`). The Playwright job now also generates an **HTML report** (`playwright-report/index.html`) which is included in the uploaded artifact for easy offline inspection. The Playwright job (and Node setup) also use **npm caching** (`actions/cache`) to speed up Node/Playwright installs when a `package-lock.json` is present. A minimal `package.json` and `package-lock.json` including `@playwright/test` are included in the repo so CI caching works out of the box.

- Quick local smoke test
  - Ensure dependencies: `npm ci`
  - Set env vars and run the helper script:
    - `GLPI_URL=http://127.0.0.1:8000 GLPI_UI_USER=glpi GLPI_UI_PASS=glpi GLPI_TEMPLATE_ID=1 ./plugins/deliveryterms/tests/e2e/run_local_preview.sh`
  - Or use the Makefile helper (from the plugin folder):
    - `make e2e-preview GLPI_URL=http://127.0.0.1:8000 GLPI_UI_USER=glpi GLPI_UI_PASS=glpi GLPI_TEMPLATE_ID=1`
  - The script will attempt to start the PHP built-in server (serving `/var/www/glpi/public`) if the site is not reachable and `$GLPI_DIR` exists; it installs Playwright browsers and runs only the `preview` spec, writing an HTML report to `playwright-report/index.html` and results to `playwright-results`.

- TipTap PoC / Bundled build
  - A minimal TipTap PoC editor is available in the template edit page (toggle near the "Content" textarea). It provides a simple toolbar (Insert Header, Insert placeholder) and syncs HTML into the `template_content` textarea for saving.
  - For development, the PoC previously used CDN UMD builds; the plugin now supports a **bundled build** for production. To build the bundled editor locally run:
    - `npm ci`
    - `npm run build:editor` (this creates `public/js/tiptap_bundle.js`)
  - CI: The GitHub Actions workflow will use npm caching and the Playwright job installs npm deps; the workflow now executes `npm run build:editor` in CI before running the Playwright spec so the bundled editor is available during E2E. The CI build also performs a quick smoke check to verify `public/js/tiptap_bundle.js` exists and contains TipTap-related markers to catch build regressions early.

- Linting & Formatting (ESLint + Prettier)
  - Run ESLint on JS source and test files: `npm run lint` (fails on errors).
  - Auto-fix lintable issues: `npm run lint:fix`.
  - Format files with Prettier: `npm run format`.
  - CI: The workflow runs `npm run lint` and `npm run format:check` during the Playwright job to enforce code style and catch issues early.
  - PR checks: A separate workflow (`.github/workflows/pr-lint.yml`) runs on pull requests and updates the PR body with a compact lint status summary (inserted between HTML markers). The summary now includes a small status badge (green `Passed` or red `Failed`). Detailed excerpts are available inside a collapsible details block within the PR body, preventing comment spam and keeping the discussion cleaner.
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

