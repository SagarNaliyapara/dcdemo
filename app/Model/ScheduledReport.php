<?php
App::uses('AppModel', 'Model');

class ScheduledReport extends AppModel {
    public $useTable = 'scheduled_reports';
    public $belongsTo = array('User');
}
