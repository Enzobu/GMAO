#!/usr/bin/env bash

# Cron :
# 0 0 * * * /home/enzo/Dev/GMAO/public/uploads/documents

set -euo pipefail

CONTAINER_NAME="gmao-prod-database"
DOCUMENTS_DIR="/home/enzo/Dev/GMAO/public/uploads/documents"
REMOTE_USER="ubuntu"
REMOTE_HOST="enzo-palermo.com"
REMOTE_DIR="/home/ubuntu/gmao/backup"

DB_HOST_DEFAULT="127.0.0.1"
DB_PORT_DEFAULT="3306"
DB_NAME_DEFAULT="gmao"
DB_USER_DEFAULT="root"
DB_PASSWORD_DEFAULT=""

if [ -f ".env.prod" ]; then
  set -a
  . ./.env.prod
  set +a
fi

DB_HOST="$DB_HOST_DEFAULT"
DB_PORT="$DB_PORT_DEFAULT"
DB_NAME="$DB_NAME_DEFAULT"
DB_USER="$DB_USER_DEFAULT"
DB_PASSWORD="$DB_PASSWORD_DEFAULT"

if [ -n "${DATABASE_URL:-}" ]; then
  DB_URL="${DATABASE_URL%\"}"
  DB_URL="${DB_URL#\"}"

  DB_USER="$(echo "$DB_URL" | sed -E 's|^[a-zA-Z0-9+]+://([^:]+):.*|\1|')"
  DB_PASSWORD="$(echo "$DB_URL" | sed -E 's|^[a-zA-Z0-9+]+://[^:]+:([^@]*)@.*|\1|')"

  if echo "$DB_URL" | grep -Eq '@[^:/?#]+:[0-9]+'; then
    DB_PORT="$(echo "$DB_URL" | sed -E 's|^[a-zA-Z0-9+]+://[^@]+@[^:/?#]+:([0-9]+).*|\1|')"
  fi

  DB_NAME="$(echo "$DB_URL" | sed -E 's|^.*/([^/?#]+)(\?.*)?$|\1|')"
fi

TIMESTAMP="$(date +'%Y-%m-%d_%H-%M-%S')"
BACKUP_NAME="backup_${TIMESTAMP}"
WORK_DIR="$(mktemp -d)"
CONTAINER_DUMP_PATH="/tmp/${BACKUP_NAME}.sql"
LOCAL_DUMP_PATH="${WORK_DIR}/database.sql"
LOCAL_DOCS_PARENT_PATH="${WORK_DIR}/uploads"
ZIP_PATH="${PWD}/${BACKUP_NAME}.zip"

cleanup() {
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT

command -v docker >/dev/null 2>&1 || { echo "Erreur : docker introuvable."; exit 1; }
command -v zip >/dev/null 2>&1 || { echo "Erreur : zip introuvable."; exit 1; }
command -v scp >/dev/null 2>&1 || { echo "Erreur : scp introuvable."; exit 1; }

if [ ! -d "${DOCUMENTS_DIR}" ]; then
  echo "Erreur : le dossier ${DOCUMENTS_DIR} n'existe pas."
  exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "${CONTAINER_NAME}"; then
  echo "Erreur : le conteneur ${CONTAINER_NAME} n'est pas démarré."
  exit 1
fi

echo "Création du dump dans le conteneur ${CONTAINER_NAME}..."
docker exec "${CONTAINER_NAME}" sh -c "
  MYSQL_PWD='${DB_PASSWORD}' mariadb-dump \
    --host='${DB_HOST}' \
    --port='${DB_PORT}' \
    --user='${DB_USER}' \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    '${DB_NAME}' > '${CONTAINER_DUMP_PATH}'
"

echo "Copie du dump en local..."
docker cp "${CONTAINER_NAME}:${CONTAINER_DUMP_PATH}" "${LOCAL_DUMP_PATH}"

echo "Suppression du dump dans le conteneur..."
docker exec "${CONTAINER_NAME}" sh -c "rm -f '${CONTAINER_DUMP_PATH}'"

echo "Copie du dossier ${DOCUMENTS_DIR}..."
mkdir -p "${LOCAL_DOCS_PARENT_PATH}"
cp -R "${DOCUMENTS_DIR}" "${LOCAL_DOCS_PARENT_PATH}/"

echo "Création de l'archive ${ZIP_PATH}..."
(
  cd "${WORK_DIR}"
  zip -r "${ZIP_PATH}" "database.sql" "uploads"
)

echo "Envoi vers ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/ ..."
scp "${ZIP_PATH}" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/"

echo "Suppression de l'archive locale..."
rm -f "${ZIP_PATH}"

echo "Backup terminé avec succès."
