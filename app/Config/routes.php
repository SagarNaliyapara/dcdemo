<?php
CakePlugin::routes();

Router::connect('/', array('controller' => 'auth', 'action' => 'login'));
Router::connect('/login', array('controller' => 'auth', 'action' => 'login'));
Router::connect('/logout', array('controller' => 'auth', 'action' => 'logout'));
Router::connect('/dashboard', array('controller' => 'dashboard', 'action' => 'index'));
Router::connect('/orders/history', array('controller' => 'orders', 'action' => 'history'));
Router::connect('/orders/update-flag', array('controller' => 'orders', 'action' => 'updateFlag'));
Router::connect('/orders/update-note', array('controller' => 'orders', 'action' => 'updateNote'));
Router::connect('/orders/save-scheduled-report', array('controller' => 'orders', 'action' => 'saveScheduledReport'));
Router::connect('/orders/scheduled-reports', array('controller' => 'orders', 'action' => 'scheduledReports'));
Router::connect('/orders/scheduled-reports/:id/delete',
    array('controller' => 'orders', 'action' => 'deleteScheduledReport'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/orders/scheduled-reports/:id/toggle',
    array('controller' => 'orders', 'action' => 'toggleScheduledReport'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/notification-rules', array('controller' => 'notification_rules', 'action' => 'index'));
Router::connect('/notification-rules/create', array('controller' => 'notification_rules', 'action' => 'create'));
Router::connect('/notification-rules/preview-orders', array('controller' => 'notification_rules', 'action' => 'previewOrders'));
Router::connect('/notification-rules/:id/edit',
    array('controller' => 'notification_rules', 'action' => 'edit'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/notification-rules/:id/delete',
    array('controller' => 'notification_rules', 'action' => 'delete'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/notification-rules/:id/toggle-status',
    array('controller' => 'notification_rules', 'action' => 'toggleStatus'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/notification-rules/:id/duplicate',
    array('controller' => 'notification_rules', 'action' => 'duplicate'),
    array('pass' => array('id'), 'id' => '[0-9]+'));
Router::connect('/notification-rules/:id/run',
    array('controller' => 'notification_rules', 'action' => 'runNow'),
    array('pass' => array('id'), 'id' => '[0-9]+'));

require CAKE . 'Config' . DS . 'routes.php';
