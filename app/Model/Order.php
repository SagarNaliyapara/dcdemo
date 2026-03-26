<?php
App::uses('AppModel', 'Model');

class Order extends AppModel {
    public $useTable = 'orders';
    public $order = 'Order.orderdate DESC';
}
