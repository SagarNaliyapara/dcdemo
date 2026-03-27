<?php
class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => null,
        'login' => null,
        'password' => null,
        'database' => null,
        'port' => 3306,
        'prefix' => '',
        'encoding' => 'utf8',
    );

    // Use a static initializer to avoid constructor issues
    public function __construct() {
        $this->default['host'] = getenv('DB_HOST');
        $this->default['login'] = getenv('DB_USER');
        $this->default['password'] = getenv('DB_PASS');
        $this->default['database'] = getenv('DB_NAME');

        // Use standard ternary (not ?:)
        $this->default['port'] = getenv('DB_PORT') ? getenv('DB_PORT') : 3306;
    }
}
