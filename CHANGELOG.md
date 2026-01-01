# Changelog

## [Unreleased] - 2026-01-01
### Added
- Feature: filenames for generated protocols now use `DocumentType-YYYY-<seq>` where `<seq>` is a per-year sequential number with 4-digit zero padding (e.g. `Termo_de_Entrega-2026-0001.pdf`).
- DB: Added `glpi_plugin_deliveryterms_sequence` table to track per-year sequences (created at runtime if missing).
- Test: `tests/integration_check_sequence.php` verifies per-year sequence allocation (with `--cleanup` option to revert).
- Test: `tests/simulate_generate.php` simulates a generation (creates file + `glpi_documents` and `glpi_plugin_deliveryterms_protocols` rows) and supports `--cleanup` to remove test artifacts.
- CI: Added GitHub Actions workflow to run the integration sequence check and simulate generation on push.

### Changed
- `inc/generate.class.php` now allocates a per-year sequence and composes filenames as `DocumentType-YYYY-0001.pdf` with a safe fallback to date-based names if allocation fails.

### Notes
- Sequence allocation uses an atomic `UPDATE ... SET last = last + 1` to avoid collisions; gaps are acceptable on failures.
- For full UI-driven E2E tests, run `tests/ui_generate_curl.sh` against a running GLPI instance with valid credentials.
