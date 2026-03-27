<?php
class EmailConfig {
    public $default = array(
        'transport'  => 'Smtp',
        'from'       => array('hello@example.com' => 'DC Orders'),
        'host'       => '127.0.0.1',
        'port'       => 2525,
        'username'   => 'dcCakephp',
		'name'       => 'DC CakePHP',
        'password'   => null,
        'timeout'    => 30,
        'client'     => null,
        'log'        => false,
        'charset'    => 'utf-8',
        'headerCharset' => 'utf-8',
    );
}
