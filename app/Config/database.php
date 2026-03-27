<?php
class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => '',
        'login' => '',
        'password' => '',
        'database' => '',
        'port' => 3306, // default fallback
        'prefix' => '',
        'encoding' => 'utf8',
    );

    public function __construct() {
        $this->default['host'] = getenv('DB_HOST');
        $this->default['login'] = getenv('DB_USER');
        $this->default['password'] = getenv('DB_PASS');
        $this->default['database'] = getenv('DB_NAME');
        $this->default['port'] = getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3306;
    }
}
