#!/usr/bin/env bash
# Headless script to log in to GLPI and submit a deliveryterms generate form
# Usage: ui_generate_curl.sh <GLPI_URL> <USERNAME> <PASSWORD> <TEMPLATE_ID> <TARGET_USER_ID>
# Example: ./ui_generate_curl.sh http://localhost glpiadmin secret 3 2

set -euo pipefail
GLPI_URL="$1"
USER="$2"
PASS="$3"
TEMPLATE_ID="$4"
TARGET_USER_ID="$5"

COOKIEJAR="/tmp/glpi_cookies_$$.txt"
trap 'rm -f "$COOKIEJAR"' EXIT

# 1) Get login page to fetch CSRF and cookies
PAGE=$(curl -s -c "$COOKIEJAR" "$GLPI_URL/index.php")

# 2) Submit login form
# Find login form token (support both input hidden and meta glpi:csrf_token)
TOKEN=$(echo "$PAGE" | grep -oP "name=['\"]_glpi_csrf_token['\"] value=['\"]\\K[^'\"]+" || true)
if [ -z "$TOKEN" ]; then
  TOKEN=$(echo "$PAGE" | grep -oP 'property="glpi:csrf_token" content="\K[^" ]+' || true)
fi
if [ -z "$TOKEN" ]; then
  echo "Could not find CSRF token on login page. Aborting." >&2
  exit 1
fi

curl -s -c "$COOKIEJAR" -b "$COOKIEJAR" -d "login_name=$USER" -d "login_password=$PASS" -d "_glpi_csrf_token=$TOKEN" "$GLPI_URL/front/login.php" >/dev/null

# 3) Access central page to get a fresh token (fallback to generate page if needed)
CENTRAL=$(curl -s -b "$COOKIEJAR" "$GLPI_URL/front/central.php")
TOKEN2=$(echo "$CENTRAL" | grep -oP 'property="glpi:csrf_token" content="\K[^" ]+' || true)
if [ -z "$TOKEN2" ]; then
  # Fallback: some GLPI deployments still expose the token as a hidden input on the form
  GENPAGE=$(curl -s -b "$COOKIEJAR" "$GLPI_URL/plugins/deliveryterms/front/generate.form.php")
  TOKEN2=$(echo "$GENPAGE" | grep -oP "name=['\"]_glpi_csrf_token['\"] value=['\"]\\K[^'\"]+" || true)
fi
if [ -z "$TOKEN2" ]; then
  echo "Could not find CSRF token on central/generate page. Aborting." >&2
  exit 1
fi
echo "Found token2: $TOKEN2"

# 4) Submit the generate form (minimal fields)
curl -s -b "$COOKIEJAR" -d "_glpi_csrf_token=$TOKEN2" -d "generate=1" -d "list=$TEMPLATE_ID" -d "user_id=$TARGET_USER_ID" -d "notes=Automated+UI+generate" "$GLPI_URL/plugins/deliveryterms/front/generate.form.php"

echo "Submitted generate request via UI (check DB and files)."