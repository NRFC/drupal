#!/bin/bash -e

# VARS
DUMP_FILE=
SQL_FILE=
TEMP_DIR=$(mktemp -d)
ROOT=$(realpath "$(dirname $0)"/..)
ENCRYPTED_SQL_FILE="$TEMP_DIR/dump.sql.gz.enc"
BACKUP_FOLDER="$ROOT/etc/ansible/playbooks/backups/dev/tmp"
FILES_TARGET="$ROOT/web/sites/default/files"
DOCKER_FILE="$ROOT/docker-compose.yml"
DRUSH="$ROOT/vendor/bin/drush"
ENV="$ROOT/.env"
GULP="$ROOT/web/themes/custom/nrfc_barrio/node_modules/.bin/gulp"
THEME_DIR="$ROOT/web/themes/custom/nrfc_barrio"

# WARNING

# FIND BACKUP
files=("$BACKUP_FOLDER"/*)
while [ -z "$DUMP_FILE" ]; do
  echo "Choose back up to extract:"
  for i in "${!files[@]}"; do
    echo "$((i+1)). $(basename ${files[$i]})"
  done

  # Ask user for a selection
  read -p "Select by number: " file_number

  # Validate selection and assign selected file
  if [[ $file_number -gt 0 && $file_number -le ${#files[@]} ]]; then
    DUMP_FILE="${files[$((file_number-1))]}"
  else
    echo "Invalid selection."
  fi
done

SQL_FILE="$TEMP_DIR"/dump.sql

# PRINT STATUS

# SKIP BACKUP?

# UNPACK FILES
tar -C $TEMP_DIR -zxf $DUMP_FILE

# DECRYPT SQL
openssl aes-256-cbc -d -pbkdf2 -pass pass:$(cat .crypt-password) -in "$ENCRYPTED_SQL_FILE" -out "$SQL_FILE".gz
gzip -d "$SQL_FILE".gz

# CLEAR DB
source "${ENV}"
docker compose -f "$DOCKER_FILE" exec mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} -e "DROP DATABASE ${MYSQL_DATABASE};"
docker compose -f "$DOCKER_FILE" exec mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE ${MYSQL_DATABASE};"

# IMPORT SQL
docker compose -f "$DOCKER_FILE" exec -T mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} < "$SQL_FILE"

# CLEAR FILES
chmod 775 "$FILES_TARGET"
rm -rf "$FILES_TARGET"

# COPY IN FILES
cp -r "$TEMP_DIR/files/files" "$FILES_TARGET"

# FLUSH CACHES
$DRUSH cr

# BUILD ASSETS
$GULP --cwd "$THEME_DIR" styles
$GULP --cwd "$THEME_DIR" js

