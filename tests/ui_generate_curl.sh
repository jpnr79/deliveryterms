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
curl -s -c "$COOKIEJAR" "$GLPI_URL/index.php" >/dev/null

# 2) Submit login form
# Find login form token
TOKEN=$(curl -s -b "$COOKIEJAR" "$GLPI_URL/index.php" | grep -oP "name=\'_glpi_csrf_token' value=\'\K[^']+")
if [ -z "$TOKEN" ]; then
  echo "Could not find CSRF token on login page. Aborting." >&2
  exit 1
fi

curl -s -c "$COOKIEJAR" -b "$COOKIEJAR" -d "login_name=$USER" -d "login_password=$PASS" -d "_glpi_csrf_token=$TOKEN" "$GLPI_URL/front/login.php" >/dev/null

# 3) Access generation page to get a fresh token
GENPAGE=$(curl -s -b "$COOKIEJAR" "$GLPI_URL/plugins/deliveryterms/front/generate.form.php")
TOKEN2=$(echo "$GENPAGE" | grep -oP "name=\'_glpi_csrf_token' value=\'\K[^']+")
if [ -z "$TOKEN2" ]; then
  echo "Could not find CSRF token on generate page. Aborting." >&2
  exit 1
fi

# 4) Submit the generate form (minimal fields)
curl -s -b "$COOKIEJAR" -d "_glpi_csrf_token=$TOKEN2" -d "generate=1" -d "list=$TEMPLATE_ID" -d "user_id=$TARGET_USER_ID" -d "notes=Automated+UI+generate" "$GLPI_URL/plugins/deliveryterms/front/generate.form.php"

echo "Submitted generate request via UI (check DB and files)."