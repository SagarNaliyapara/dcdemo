<?php
class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysqli', // must use Mysqli for MySQL 8
        'persistent' => false,
        'host' => 'mysql-1791f388-sagarnaliyapara78-b2bb.e.aivencloud.com',
        'login' => 'avnadmin',
        'password' => 'AVNS_jOpfMZL8w9gkiKM7MYB',
        'database' => 'defaultdb',
        'port' => 24659,
        'prefix' => '',
        'encoding' => 'utf8',
        'flags' => MYSQLI_CLIENT_SSL,
        'ssl_key' => null,
        'ssl_cert' => null,
        'ssl_ca' => APP . 'Config/ca.pem',
    );
}
