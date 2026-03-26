<?php
App::uses('AbstractPasswordHasher', 'Controller/Component/Auth');

class BcryptPasswordHasher extends AbstractPasswordHasher {
    public function hash($password) {
        return password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
    }

    public function check($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }
}
