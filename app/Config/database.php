<?php
class DATABASE_CONFIG {

    // Initialize with safe constants only
    public $default = array(
        'datasource' => 'Database/Mysql',
        'persistent' => false,
        'host' => 'localhost',
        'login' => 'root',
        'password' => '',
        'database' => 'cakephp',
        'port' => 3306,
        'prefix' => '',
        'encoding' => 'utf8',
    );

    // Apply environment variables safely
    public function __construct() {
        $host = getenv('DB_HOST');
        $login = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $db   = getenv('DB_NAME');
        $port = getenv('DB_PORT');

        $this->default['host'] = $host !== false ? $host : $this->default['host'];
        $this->default['login'] = $login !== false ? $login : $this->default['login'];
        $this->default['password'] = $pass !== false ? $pass : $this->default['password'];
        $this->default['database'] = $db !== false ? $db : $this->default['database'];
        $this->default['port'] = $port !== false ? $port : $this->default['port'];
    }
}
