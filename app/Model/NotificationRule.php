<?php
App::uses('AppModel', 'Model');

class NotificationRule extends AppModel {
    public $useTable = 'notification_rules';
    public $belongsTo = array('User');
}
