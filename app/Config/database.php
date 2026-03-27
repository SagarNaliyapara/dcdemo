<?php
class DATABASE_CONFIG {
    public $default = array();

    public function __construct() {
        $this->default = array(
            'datasource' => 'Database/Mysql',
            'persistent' => false,
            'host' => getenv('DB_HOST'),
            'login' => getenv('DB_USER'),
            'password' => getenv('DB_PASS'),
            'database' => getenv('DB_NAME'),
            'port' => getenv('DB_PORT') ? getenv('DB_PORT') : 3306,
            'prefix' => '',
            'encoding' => 'utf8',
        );
    }
}
