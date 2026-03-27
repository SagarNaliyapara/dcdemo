<?php
// Precompute environment variables BEFORE the class
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'cakephp';
$dbPort = getenv('DB_PORT') ?: 3306;

class DATABASE_CONFIG {
    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => $dbHost,
        'login' => $dbUser,
        'password' => $dbPass,
        'database' => $dbName,
        'port' => $dbPort,
        'prefix' => '',
        'encoding' => 'utf8',
    );
}
