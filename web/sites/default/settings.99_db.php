<?php

$databases['default']['default'] = array (
  'database' => 'drupal',
  'username' => 'drupal_database_user',
  'password' => 'drupal_database_password',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);
