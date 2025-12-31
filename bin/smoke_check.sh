#!/usr/bin/env bash
set -euo pipefail
BASE=${1:-http://127.0.0.1}
COOKIEJAR=${2:-/tmp/glpi_smoke.cookies}
OUT=/tmp/deliveryterms_smoke.html
MISSING=()
# Clear GLPI cache if running locally and permissions allow
if [ -d "/var/www/glpi/files/_cache" ]; then
  rm -rf /var/www/glpi/files/_cache/* || true
fi
if [ -d "/var/www/glpi/files/_locales" ]; then
  rm -rf /var/www/glpi/files/_locales/* || true
fi
# Fetch page using cookie jar (must contain authenticated session cookies)
curl -s -b "$COOKIEJAR" "$BASE/plugins/deliveryterms/front/config.form.php" -o "$OUT"
# Expected Portuguese strings
expected=(
  "Criar modelo"
  "Nome do modelo"
  "Título do documento"
  "Fonte"
  "Tamanho da fonte"
  "Quebra de palavras"
  "Conteúdo superior"
  "Largura do logótipo (px)"
  "Altura do logótipo (px)"
  "Ativar envio automático de e-mail"
  "Eliminar ficheiro"
)
for s in "${expected[@]}"; do
  # Allow for some HTML wrapping (tags or entities). Use grep -i -P to match across tags or entities.
  if ! grep -qiP "${s// /\s+}" "$OUT"; then
    MISSING+=("$s")
  fi
done

# Also check the i18n diagnostic comment for dgettext and __() outputs (helps when rendering differs)
if grep -q "i18n-dgettext-font" "$OUT"; then
  diag=$(grep -oP "i18n-dgettext-font: .* -->" "$OUT" | head -n1)
  echo "Found diagnostic: $diag"
fi
if [ ${#MISSING[@]} -eq 0 ]; then
  echo "SMOKE OK: All translations present"
  exit 0
else
  echo "SMOKE FAIL: Missing translations:" >&2
  for m in "${MISSING[@]}"; do
    echo " - $m" >&2
  done
  exit 2
fi
