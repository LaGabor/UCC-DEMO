#!/bin/bash
set -euo pipefail

MYSQLD_BIN="$(command -v mysqld)"
MYSQLD_DIR="$(dirname "$MYSQLD_BIN")"
PLUGIN_DIR="$(mysqld --verbose --help 2>/dev/null | awk '$1=="plugin-dir" {print $2; exit}')"

if [ -z "${PLUGIN_DIR:-}" ]; then
  echo "Could not determine MySQL plugin_dir"
  exit 1
fi

mkdir -p /var/lib/mysql-keyring
chown -R mysql:mysql /var/lib/mysql-keyring

install -m 0444 /docker/mysql/keyring-files/mysqld.my "${MYSQLD_DIR}/mysqld.my"
install -m 0444 /docker/mysql/keyring-files/component_keyring_file.cnf "${PLUGIN_DIR}/component_keyring_file.cnf"

exec /usr/local/bin/docker-entrypoint.sh "$@"
