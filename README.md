## Local dev

### Checkout and initialise

#### Check out and build the php deps

```bash
git clone git@github.com:NRFC/drupal.git dev.norwichrugby.com
cd git@github.com:NRFC/drupal.git dev.norwichrugby.com
composer install --dev
```

#### Set up JS deps and build the SASS

```bash
yarn add --global gulp-cli
yarn
```

#### Set up environment

```bash
cp env.example .env
if [ -z "$(grep www.nrfc.test /etc/hosts)" ]; then echo '127.0.0.1 www.nrfc.test nrfc.test' | sudo tee -a /etc/hosts; fi
touch $(pwd)/web/sites/default/settings.90_local.php
```

### Watch SASS files

```bash
npx gulp
```

## Sync down DB

### Set up ansible

You will need to set up `.ansible_vault_passwd_file` and `.crypt-password`. The vault password is super secret, check with a lead dev. The crypt password is in the vault.

```bash
cd etc/ansible/
echo ANSIBLE_VAULT_PASSWD > .ansible_vault_passwd_file
ansible-vault view vault.yaml
# Set that crypt passwd in .crypt-password
echo CRYPT_PASSWD > .crypt-password
cd -
```

### Run the get site playbook

```bash
cd etc/ansible/
ansible-playbook -i hosts.yaml -i vault.yaml playbooks/get-site.playbook.yaml
tar zxf $(ls playbooks/backups/dev/tmp/20* -1|tail -n 1)
sudo cp -r files ../../web/sites/default
openssl aes-256-cbc -d -pbkdf2 -pass pass:$(cat .crypt-password) -in dump.sql.gz.enc | gzip -d > ../../etc/docker/initdb.d/dump.sql
rm -rf files dump*
sudo chown -R 33:33 ../../web/sites/default/files/
cd -
```

#### First install

**This needs testing**

```bash
docker compose up --build
# Wait, this will take 5 minutes
```

#### Update existing

**This will loose ALL local changes in the DB**

```bash
source .env
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -P3396 -h127.0.0.1 -e "drop database ${MYSQL_DATABASE}"
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -P3396 -h127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE}"
mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -P3396 -h127.0.1 drupal < etc/docker/initdb.d/dump.sql
docker compose exec drupal /opt/drupal/vendor/bin/drush cr

docker compose exec drupal /opt/drupal/vendor/bin/drush upwd $USER password
#  or
 docker compose exec drupal /opt/drupal/vendor/bin/drush user:create $USER --mail='$USER@$HOSTNAME' --password='password'
```
