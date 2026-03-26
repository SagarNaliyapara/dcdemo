<?php
App::uses('Controller', 'Controller');

class AppController extends Controller {
    public $components = array(
        'Session',
        'Auth' => array(
            'loginRedirect' => array('controller' => 'dashboard', 'action' => 'index'),
            'logoutRedirect' => array('controller' => 'auth', 'action' => 'login'),
            'loginAction' => array('controller' => 'auth', 'action' => 'login'),
            'authenticate' => array(
                'Form' => array(
                    'fields' => array('username' => 'email', 'password' => 'password'),
                    'passwordHasher' => 'Blowfish',
                )
            ),
            'authorize' => array('Controller'),
        ),
        'Flash',
    );

    public function isAuthorized($user) {
        return true;
    }

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->loginAction = array('controller' => 'auth', 'action' => 'login');
        $this->Auth->loginRedirect = array('controller' => 'dashboard', 'action' => 'index');
        $this->Auth->logoutRedirect = array('controller' => 'auth', 'action' => 'login');
    }

    protected function currentUser() {
        return $this->Auth->user();
    }
}
