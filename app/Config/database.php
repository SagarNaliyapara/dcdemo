<?php
$dbPort = getenv('DB_PORT');
$dbPort = $dbPort !== false ? $dbPort : 3306;

class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => getenv('DB_HOST'),
        'login' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'database' => getenv('DB_NAME'),
        'port' => $dbPort,
        'prefix' => '',
        'encoding' => 'utf8',
    );
}
