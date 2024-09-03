yarn add --global gulp-cli
yarn --cwd $(pwd)/web/themes/custom/nrfc_barrio
touch $(pwd)/web/sites/default/settings.local.php

cd web/themes/custom/nrfc_barrio
./node_modules/.bin/gulp
