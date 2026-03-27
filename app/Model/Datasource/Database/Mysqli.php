<?php
/**
 * MySQLi Datasource for CakePHP 2.9 (for MySQL 5.7+/8.0+ with SSL)
 * Place this in app/Model/Datasource/Database/Mysqli.php
 */

App::uses('DboSource', 'Model/Datasource');
App::uses('Mysql', 'Model/Datasource/Database');

class Mysqli extends Mysql {

    public $description = "MySQLi DBO Driver (CakePHP 2.9 patched for SSL)";

    /**
     * Connects to the database using mysqli
     */
    public function connect() {
        $config = $this->_config;

        // Apply SSL options if provided
        $sslOptions = array();
        if (!empty($config['flags']) || !empty($config['ssl_ca'])) {
            $sslOptions = array(
                'ssl_key'    => isset($config['ssl_key']) ? $config['ssl_key'] : null,
                'ssl_cert'   => isset($config['ssl_cert']) ? $config['ssl_cert'] : null,
                'ssl_ca'     => isset($config['ssl_ca']) ? $config['ssl_ca'] : null,
                'ssl_capath' => isset($config['ssl_capath']) ? $config['ssl_capath'] : null,
                'ssl_cipher' => isset($config['ssl_cipher']) ? $config['ssl_cipher'] : null,
            );
        }

        $this->connected = false;

        // mysqli connection
        $this->_connection = mysqli_init();

        if (!empty($sslOptions['ssl_ca'])) {
            mysqli_ssl_set(
                $this->_connection,
                $sslOptions['ssl_key'],
                $sslOptions['ssl_cert'],
                $sslOptions['ssl_ca'],
                isset($sslOptions['ssl_capath']) ? $sslOptions['ssl_capath'] : null,
                isset($sslOptions['ssl_cipher']) ? $sslOptions['ssl_cipher'] : null
            );
        }

        // Apply client flags
        $flags = isset($config['flags']) ? $config['flags'] : 0;

        $connected = @mysqli_real_connect(
            $this->_connection,
            $config['host'],
            $config['login'],
            $config['password'],
            $config['database'],
            $config['port'],
            null,
            $flags
        );

        if (!$connected) {
            $this->lastError = mysqli_connect_error();
            return false;
        }

        $this->connected = true;

        if (!empty($config['encoding'])) {
            $this->_connection->set_charset($config['encoding']);
        }

        return $this->connected;
    }
}
