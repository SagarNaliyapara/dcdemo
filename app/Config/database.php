<?php
class DATABASE_CONFIG {

    public $default = array(
        'datasource' => 'Database/Mysqli',
        'persistent' => false,
        'host' => 'mysql-1791f388-sagarnaliyapara78-b2bb.e.aivencloud.com',
        'login' => 'avnadmin',
        'password' => 'AVNS_jOpfMZL8w9gkiKM7MYB',
        'database' => 'defaultdb',
        'port' => '24659', // default fallback
        'prefix' => '',
        'encoding' => 'utf8',
		'flags' => [MYSQLI_CLIENT_SSL], // optional if SSL required
    );
}
