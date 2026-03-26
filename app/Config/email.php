<?php
class EmailConfig {
    public $default = array(
        'transport'  => 'Smtp',
        'from'       => array('hello@example.com' => 'DC Orders'),
        'host'       => '',
        'port'       => 2525,
        'username'   => '',
        'password'   => '',
        'timeout'    => 30,
        'client'     => null,
        'log'        => false,
        'charset'    => 'utf-8',
        'headerCharset' => 'utf-8',
    );
}
