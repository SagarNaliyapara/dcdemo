<?php
App::uses('AppController', 'Controller');

class DashboardReportsController extends AppController {
    public $uses = array('Order');

    public function index() {
        $totalOrders = $this->Order->find('count');
        $recentOrders = $this->Order->find('all', array(
            'order' => array('Order.created' => 'DESC'),
            'limit' => 5,
            'fields' => array('id', 'order_number', 'product_description', 'supplier_id', 'quantity', 'price', 'response', 'orderdate', 'flag'),
        ));
        
        // Stats
        $totalValue = $this->Order->find('first', array(
            'fields' => array('COALESCE(SUM(quantity * price), 0) as total'),
            'recursive' => -1,
        ));
        $totalValue = isset($totalValue[0]['total']) ? (float)$totalValue[0]['total'] : 0.0;
        
        $inStockCount = $this->Order->find('count', array(
            'conditions' => array('Order.stock_status' => 'IN_STOCK'),
        ));
        $outOfStockCount = $this->Order->find('count', array(
            'conditions' => array('Order.stock_status' => 'OUT_OF_STOCK'),
        ));
        
        $this->set(compact('totalOrders', 'recentOrders', 'totalValue', 'inStockCount', 'outOfStockCount'));
        $this->set('title_for_layout', 'Dashboard');
    }
}
