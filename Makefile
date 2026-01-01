.PHONY: e2e-preview e2e

# Run the local Playwright preview spec (helper will attempt to start PHP built-in server if needed)
e2e-preview:
	@echo "Running Playwright preview locally..."
	@GLPI_URL=${GLPI_URL} GLPI_UI_USER=${GLPI_UI_USER} GLPI_UI_PASS=${GLPI_UI_PASS} GLPI_TEMPLATE_ID=${GLPI_TEMPLATE_ID} ./tests/e2e/run_local_preview.sh

# Run full Playwright suite (requires npm deps installed)
e2e:
	@echo "Installing npm deps and running Playwright tests..."
	@npm ci && npm run test:e2e
