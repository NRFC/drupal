## Local dev

### Checkout and initialise

```bash
git clone git@github.com:NRFC/drupal.git dev.norwichrugby.com
cd git@github.com:NRFC/drupal.git dev.norwichrugby.com
yarn add --global gulp-cli
composer install --dev
yarn --cwd $(pwd)/web/themes/custom/nrfc_barrio
touch $(pwd)/web/sites/default/settings.90_local.php
```
`
### Watch SASS files

```bash
cd web/themes/custom/nrfc_barrio
./node_modules/.bin/gulp
```

## Docker

```bash
docker build -t nrfc/www:$HOSTNAME .
docker compose up
```
