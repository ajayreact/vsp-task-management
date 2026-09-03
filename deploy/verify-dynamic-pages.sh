#!/usr/bin/env bash
#
# Verify the dynamic-page 502 fix on the production VPS.
# Run from the project root after deploying application + Nginx changes:
#   bash deploy/verify-dynamic-pages.sh
#
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NGINX_SITE="${NGINX_SITE:-/etc/nginx/sites-available/app.vspcrm.in}"
APP_URL="${APP_URL:-https://app.vspcrm.in}"
TASK_ID="${TASK_ID:-8}"
PROJECT_ID="${PROJECT_ID:-1}"
EMPLOYEE_ID="${EMPLOYEE_ID:-1}"

echo "== Application middleware check =="
if grep -q "AddLinkHeadersForPreloadedAssets::class" "${PROJECT_ROOT}/bootstrap/app.php"; then
    echo "FAIL: AddLinkHeadersForPreloadedAssets is still enabled in bootstrap/app.php"
    exit 1
fi
echo "OK: AddLinkHeadersForPreloadedAssets is not in web middleware"

echo
echo "== Nginx site config check =="
if [[ ! -f "${NGINX_SITE}" ]]; then
    echo "WARN: Nginx site file not found at ${NGINX_SITE}"
else
    for setting in fastcgi_buffer_size fastcgi_buffers fastcgi_busy_buffers_size; do
        if ! grep -q "${setting}" "${NGINX_SITE}"; then
            echo "FAIL: ${NGINX_SITE} is missing ${setting}"
            exit 1
        fi
    done

    if ! grep -q 'try_files \$uri \$uri/ /index.php?\$query_string;' "${NGINX_SITE}"; then
        echo "FAIL: Laravel try_files front controller is missing"
        exit 1
    fi

    if grep -q 'index\.html' "${NGINX_SITE}"; then
        echo "FAIL: SPA index.html fallback detected — remove it for Laravel routes"
        exit 1
    fi

    echo "OK: Nginx site contains fastcgi buffer settings and Laravel try_files"
fi

echo
echo "== Nginx syntax check =="
sudo nginx -t

echo
echo "== Recent Nginx too-big-header errors =="
if sudo grep -i "too big header" /var/log/nginx/error.log | tail -n 5; then
    echo "WARN: Recent 'too big header' entries found above. Retest after reload."
else
    echo "OK: No recent 'too big header' entries in tail of error.log"
fi

echo
echo "== Unauthenticated route smoke checks (expect redirect, not 502) =="
check_url() {
    local path="$1"
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' "${APP_URL}${path}")"
    if [[ "${code}" == "502" ]]; then
        echo "FAIL: ${path} returned 502"
        exit 1
    fi
    echo "OK: ${path} returned HTTP ${code}"
}

check_url "/tasks/${TASK_ID}"
check_url "/tasks/projects/${PROJECT_ID}"
check_url "/admin/employees/${EMPLOYEE_ID}/edit"

echo
echo "Manual authenticated browser checks still required:"
echo "  1. /tasks -> click task -> /tasks/${TASK_ID}"
echo "  2. Refresh /tasks/${TASK_ID}"
echo "  3. Open /tasks/${TASK_ID} in a new tab"
echo "  4. Hard refresh /tasks/${TASK_ID}"
echo "  5. Repeat for /tasks/projects/{id} and /admin/employees/{id}/edit"
echo
echo "While testing, watch:"
echo "  sudo tail -f /var/log/nginx/error.log"
