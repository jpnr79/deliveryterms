#!/usr/bin/env bash
# Headless script to login and POST HTML to preview endpoint
# Usage: ui_preview_curl.sh <GLPI_URL> <USERNAME> <PASSWORD> <HTML_FILE>
set -euo pipefail
GLPI_URL="$1"
USER="$2"
PASS="$3"
HTML_FILE="$4"
COOKIEJAR="/tmp/glpi_preview_cookies_$$.txt"
trap 'rm -f "$COOKIEJAR"' EXIT

PAGE=$(curl -s -c "$COOKIEJAR" "$GLPI_URL/index.php")
TOKEN=$(echo "$PAGE" | grep -oP 'property="glpi:csrf_token" content="\K[^" ]+' || true)
if [ -z "$TOKEN" ]; then
  TOKEN=$(echo "$PAGE" | grep -oP "name=['\"]_glpi_csrf_token['\"] value=['\"]\\K[^'\"]+" || true)
fi
if [ -z "$TOKEN" ]; then
  echo "Could not find CSRF token on login page" >&2
  exit 1
fi

curl -s -b "$COOKIEJAR" -c "$COOKIEJAR" -d "login_name=$USER" -d "login_password=$PASS" -d "_glpi_csrf_token=$TOKEN" "$GLPI_URL/front/login.php" >/dev/null
# Fetch central to get a fresh token
CENTRAL=$(curl -s -b "$COOKIEJAR" "$GLPI_URL/front/central.php")
TOKEN2=$(echo "$CENTRAL" | grep -oP 'property="glpi:csrf_token" content="\K[^" ]+' || true)
if [ -z "$TOKEN2" ]; then
  echo "Could not find CSRF token on central page" >&2
  exit 1
fi

# Post HTML to preview endpoint
curl -s -b "$COOKIEJAR" -H "Content-Type: application/x-www-form-urlencoded" -d "_glpi_csrf_token=$TOKEN2" --data-urlencode "html@${HTML_FILE}" "$GLPI_URL/plugins/deliveryterms/front/preview.php" -D /tmp/preview_headers_$$ -o /tmp/preview_body_$$ || true
# Check headers
grep -i "Content-Type" /tmp/preview_headers_$$ || true
file /tmp/preview_body_$$ || true

echo "Preview script completed. Check /tmp/preview_body_$$ (PDF binary)"