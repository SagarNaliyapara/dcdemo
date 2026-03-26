<?php
App::uses('AppModel', 'Model');

class User extends AppModel {
    public $useTable = 'users';

    public $validate = array(
        'email' => array(
            'email' => array('rule' => 'email', 'message' => 'Please enter a valid email address'),
        ),
        'password' => array(
            'required' => array('rule' => array('minLength', 1), 'message' => 'Password is required'),
        ),
    );
}
