<?php
$database = getenv("MYSQL_DATABASE") ?? 'drupal';
$dbUser = getenv("MYSQL_USER") ?? "drupal";
$dbPass = getenv("MYSQL_PASSWORD") ?? "drupal";
$dbPort = getenv("MYSQL_PORT") ?? "3306";
$dbHost = getenv("MYSQL_HOST") ?? "127.0.0.1";

$databases['default']['default'] = array (
  'database' => $database,
  'username' => $dbUser,
  'password' => $dbPass,
  'prefix' => '',
  'host' => $dbHost,
  'port' => $dbPort,
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
);
//die(print_r($databases, true));
