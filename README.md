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
yarn --cwd $(pwd)/web/themes/custom/nrfc_barrio
web/themes/custom/nrfc_barrio/node_modules/.bin/gulp --cwd web/themes/custom/nrfc_barrio styles
web/themes/custom/nrfc_barrio/node_modules/.bin/gulp --cwd web/themes/custom/nrfc_barrio js
```

#### Set up environment

```bash
cp env.example .env
if [ -z "$(grep www.nrfc.test /etc/hosts)" ]; then echo '127.0.0.1 www.nrfc.test nrfc.test' | sudo tee -a /etc/hosts; fi
touch $(pwd)/web/sites/default/settings.90_local.php
```

Edit `web/sites/default/settings.90_local.php`

```php
<?php
$database = "drupal";
$dbUser = "drupal";
$dbPass = "drupal";
$dbPort = "3306";
$dbHost = "127.0.0.1";

$envFile = __DIR__ . '../../../.env';
if (file_exists($envFile)) {
  $env = parse_ini_file($envFile);
  $database = $env["MYSQL_DATABASE"];
  $dbUser = $env["MYSQL_USER"];
  $dbPass = $env["MYSQL_PASSWORD"];
  $dbPort = $env["MYSQL_PORT"];
  $dbHost = $env["MYSQL_HOST"];
}
```

### Watch SASS files

```bash
web/themes/custom/nrfc_barrio/node_modules/.bin/gulp --cwd web/themes/custom/nrfc_barrio
```


## DEV STUFF, DON'T USE

docker exec devnorwichrugbycom-mysql-1 mysqldump -uroot -proot_password drupal > dump.20240914.sql
docker volume create drupal_files
