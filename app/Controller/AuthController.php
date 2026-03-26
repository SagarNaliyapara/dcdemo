<?php
App::uses('AppController', 'Controller');

class AuthController extends AppController {
    public $uses = array('User');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(array('login'));
    }

    public function login() {
        if ($this->Auth->user()) {
            return $this->redirect(array('controller' => 'dashboard', 'action' => 'index'));
        }
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Session->setFlash('Invalid email or password. Please try again.');
        }
        $this->layout = 'auth';
        $this->set('title_for_layout', 'Login');
    }

    public function logout() {
        $this->Auth->logout();
        return $this->redirect(array('controller' => 'auth', 'action' => 'login'));
    }
}
