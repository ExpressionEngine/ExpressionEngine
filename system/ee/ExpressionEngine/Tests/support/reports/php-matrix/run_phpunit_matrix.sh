#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../../../.." && pwd)"
REPORT_DIR="${ROOT_DIR}/system/ee/ExpressionEngine/Tests/support/reports/php-matrix"
mkdir -p "${REPORT_DIR}"

PHPUNIT_CMD="system/ee/ExpressionEngine/Tests/vendor/bin/phpunit -c system/ee/ExpressionEngine/Tests/phpunit.xml"
HERD_BIN="/Users/tomjaeger/Library/Application Support/Herd/bin"
VERSIONS=("74" "80" "81" "82" "83" "84")

if [[ ! -f "${ROOT_DIR}/system/ee/ExpressionEngine/Tests/phpunit.xml" ]]; then
    echo "Could not resolve repository root from script location: ${ROOT_DIR}" >&2
    exit 1
fi

run_one() {
    local version="$1"
    local php_bin="${HERD_BIN}/php${version}"
    local log_file="${REPORT_DIR}/php${version}-deprecations.log"
    local status_file="${REPORT_DIR}/php${version}-status.txt"

    echo "Running php${version}..."
    set +e
    EE_SHOW_DEPRECATIONS=1 "${php_bin}" ${PHPUNIT_CMD} >"${log_file}" 2>&1
    local exit_code=$?
    set -e
    printf "EXIT:%s\n" "${exit_code}" >"${status_file}"
}

print_summary() {
    echo
    echo "Matrix summary:"
    for version in "${VERSIONS[@]}"; do
        local log_file="${REPORT_DIR}/php${version}-deprecations.log"
        local status_file="${REPORT_DIR}/php${version}-status.txt"
        local status_line tests_line total first_party runtime_party test_party vendor_party

        status_line="$(cat "${status_file}" 2>/dev/null || echo "EXIT:missing")"
        tests_line="$(grep -aE '^Tests:' "${log_file}" | tail -n 1 || true)"

        set +e
        total="$(grep -a -c 'Deprecated:' "${log_file}")"
        vendor_party="$(grep -a 'Deprecated:' "${log_file}" | grep -a -c '/system/ee/ExpressionEngine/Tests/vendor/')"
        test_party="$(grep -a 'Deprecated:' "${log_file}" | grep -a '/system/ee/ExpressionEngine/Tests/' | grep -a -v '/system/ee/ExpressionEngine/Tests/vendor/' | wc -l | tr -d ' ')"
        runtime_party="$(grep -a 'Deprecated:' "${log_file}" | grep -a '/system/ee/ExpressionEngine/' | grep -a -v '/system/ee/ExpressionEngine/Tests/' | wc -l | tr -d ' ')"
        set -e
        total="${total:-0}"
        vendor_party="${vendor_party:-0}"
        test_party="${test_party:-0}"
        runtime_party="${runtime_party:-0}"
        first_party=$((test_party + runtime_party))

        echo "php${version} ${status_line} ${tests_line}"
        echo "  Deprecated total=${total:-0} first_party=${first_party:-0} runtime=${runtime_party:-0} tests=${test_party:-0} vendor=${vendor_party:-0}"
    done
}

cd "${ROOT_DIR}"
for version in "${VERSIONS[@]}"; do
    run_one "${version}"
done
print_summary | tee "${REPORT_DIR}/matrix-summary.txt"
