#!/usr/bin/env bash
set -euo pipefail

# Run Playwright preview spec locally with minimal setup
# Usage:
# GLPI_URL=http://127.0.0.1:8000 GLPI_UI_USER=glpi GLPI_UI_PASS=glpi GLPI_TEMPLATE_ID=1 ./tests/e2e/run_local_preview.sh

: ${GLPI_URL:='http://127.0.0.1:8000'}
: ${GLPI_UI_USER:?'glpi'}
: ${GLPI_UI_PASS:?'glpi'}
: ${GLPI_TEMPLATE_ID:?'1'}
: ${GLPI_DIR:='/var/www/glpi'}

echo "Using GLPI_URL=$GLPI_URL"

# Ensure npm deps are installed
if [ -f package-lock.json ]; then
  echo "Installing npm dependencies (npm ci)..."
  npm ci
else
  echo "No package-lock.json found, installing @playwright/test dev dependency..."
  npm init -y >/dev/null
  npm i -D @playwright/test
fi

# Install browsers if needed
npx playwright install --with-deps

# Start PHP built-in server if the GLPI site isn't responding and GLPI_DIR exists
if ! curl -sSf "$GLPI_URL/index.php" >/dev/null 2>&1; then
  if [ -d "$GLPI_DIR/public" ]; then
    echo "Starting PHP built-in server serving $GLPI_DIR/public on 127.0.0.1:8000"
    nohup php -S 127.0.0.1:8000 -t "$GLPI_DIR/public" > /tmp/php_server.log 2>&1 &
    # wait for it
    for i in {1..30}; do
      if curl -sSf "$GLPI_URL/index.php" >/dev/null 2>&1; then
        echo "Server up"; break
      fi
      sleep 1
    done
  else
    echo "GLPI not responding at $GLPI_URL and $GLPI_DIR/public not found. Please start your GLPI server and retry." >&2
    exit 1
  fi
fi

# Run the preview spec
echo "Running Playwright preview spec..."
npx playwright test tests/e2e/preview.spec.js --reporter=list,html --output=playwright-results --video=retain-on-failure --trace=retain-on-failure --screenshot=only-on-failure

if [ -d playwright-report ]; then
  echo "Playwright report available at: $(pwd)/playwright-report/index.html"
fi

exit 0
