<?php
App::uses('AppController', 'Controller');

class OrdersController extends AppController {
    public $uses = array('Order', 'ScheduledReport');
    public $components = array('Paginator', 'RequestHandler', 'Session', 'Auth', 'Flash');

    public function history() {
        $this->set('title_for_layout', 'Order History');

        $filters = array(
            'search'       => isset($this->request->query['search']) ? trim($this->request->query['search']) : '',
            'dateFilter'   => isset($this->request->query['dateFilter']) ? $this->request->query['dateFilter'] : 'all',
            'startDate'    => isset($this->request->query['startDate']) ? $this->request->query['startDate'] : '',
            'endDate'      => isset($this->request->query['endDate']) ? $this->request->query['endDate'] : '',
            'sortField'    => isset($this->request->query['sortField']) ? $this->request->query['sortField'] : 'orderdate',
            'sortDir'      => isset($this->request->query['sortDir']) ? $this->request->query['sortDir'] : 'desc',
            'perPage'      => isset($this->request->query['perPage']) ? max(10, (int)$this->request->query['perPage']) : 25,
            'groupedFilters' => array(),
        );

        if (!empty($this->request->query['groupedFilters'])) {
            $gf = $this->request->query['groupedFilters'];
            if (is_string($gf)) {
                $decoded = json_decode($gf, true);
                if (is_array($decoded)) {
                    $filters['groupedFilters'] = $decoded;
                }
            } elseif (is_array($gf)) {
                $filters['groupedFilters'] = $gf;
            }
        }

        if ($this->request->is('post') && !empty($this->request->data['_apply_filters'])) {
            $postData = $this->request->data;
            $gfJson = isset($postData['groupedFiltersJson']) ? $postData['groupedFiltersJson'] : '{}';
            return $this->redirect(array(
                'action' => 'history',
                '?' => array(
                    'search'         => isset($postData['search']) ? $postData['search'] : '',
                    'dateFilter'     => isset($postData['dateFilter']) ? $postData['dateFilter'] : 'all',
                    'startDate'      => isset($postData['startDate']) ? $postData['startDate'] : '',
                    'endDate'        => isset($postData['endDate']) ? $postData['endDate'] : '',
                    'sortField'      => isset($postData['sortField']) ? $postData['sortField'] : 'orderdate',
                    'sortDir'        => isset($postData['sortDir']) ? $postData['sortDir'] : 'desc',
                    'perPage'        => isset($postData['perPage']) ? $postData['perPage'] : 25,
                    'groupedFilters' => $gfJson,
                )
            ));
        }

        $allowedSortFields = array('order_number', 'product_description', 'supplier_id', 'pipcode',
            'category', 'quantity', 'approved_qty', 'price', 'dt_price',
            'orderdate', 'response', 'stock_status', 'flag');
        if (!in_array($filters['sortField'], $allowedSortFields)) {
            $filters['sortField'] = 'orderdate';
        }
        $filters['sortDir'] = strtolower($filters['sortDir']) === 'asc' ? 'asc' : 'desc';

        $conditions = $this->_buildConditions($filters);

        $this->Paginator->settings = array(
            'Order' => array(
                'conditions' => $conditions,
                'order' => array('Order.' . $filters['sortField'] => $filters['sortDir']),
                'limit' => $filters['perPage'],
                'recursive' => -1,
            )
        );

        $orders = $this->Paginator->paginate('Order');

        $totalAmountResult = $this->Order->find('first', array(
            'conditions' => $conditions,
            'fields' => array('COALESCE(SUM(Order.quantity * Order.price), 0) as total'),
            'recursive' => -1,
        ));
        $totalAmount = isset($totalAmountResult[0]['total']) ? (float)$totalAmountResult[0]['total'] : 0.0;
        $totalCount = $this->Order->find('count', array('conditions' => $conditions));

        $hasActiveFilters = !empty($filters['groupedFilters']['groups']);
        $this->set(compact('orders', 'filters', 'totalAmount', 'totalCount', 'hasActiveFilters'));
        $this->set('filterFieldDefinitions', $this->_filterFieldDefinitions());
    }

    public function updateFlag() {
        $this->request->allowMethod('post');
        $orderId = (int)$this->request->data('id');
        $flag = $this->request->data('flag');
        $allowedFlags = array('red', 'green', 'black', 'blue', '');
        if (!in_array($flag, $allowedFlags)) $flag = '';
        if ($orderId > 0) {
            $this->Order->id = $orderId;
            $this->Order->saveField('flag', $flag ?: null);
        }
        if ($this->request->is('ajax')) {
            $this->autoRender = false;
            $this->response->type('json');
            $this->response->body(json_encode(array('success' => true)));
            return $this->response;
        }
        return $this->redirect($this->referer());
    }

    public function updateNote() {
        $this->request->allowMethod('post');
        $orderId = (int)$this->request->data('id');
        $note = $this->request->data('note');
        if ($orderId > 0) {
            $this->Order->id = $orderId;
            $this->Order->saveField('notes', $note);
        }
        if ($this->request->is('ajax')) {
            $this->autoRender = false;
            $this->response->type('json');
            $this->response->body(json_encode(array('success' => true)));
            return $this->response;
        }
        return $this->redirect($this->referer());
    }

    public function saveScheduledReport() {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $name      = isset($this->request->data['name']) ? trim($this->request->data['name']) : '';
        $frequency = isset($this->request->data['frequency']) ? $this->request->data['frequency'] : 'daily';
        $sendTime  = isset($this->request->data['send_time']) ? $this->request->data['send_time'] : '08:00';
        $dayOfWeek = isset($this->request->data['day_of_week']) ? $this->request->data['day_of_week'] : null;
        $dayOfMonth = isset($this->request->data['day_of_month']) ? $this->request->data['day_of_month'] : null;
        $email      = isset($this->request->data['email']) ? trim($this->request->data['email']) : '';
        $filtersJson = isset($this->request->data['filters_json']) ? $this->request->data['filters_json'] : '{}';
        if (empty($name)) {
            $filtersArr = json_decode($filtersJson, true) ?: array();
            $dateFilter = isset($filtersArr['dateFilter']) ? $filtersArr['dateFilter'] : 'all';
            $dateLabels = array('today' => 'Today', 'yesterday' => 'Yesterday',
                'last3days' => 'Last 3 Days', 'last7days' => 'Last 7 Days',
                'thismonth' => 'This Month', 'lastmonth' => 'Last Month');
            $dateLabel = isset($dateLabels[$dateFilter]) ? $dateLabels[$dateFilter] : 'All Orders';
            $name = ucfirst($frequency) . ' – ' . $dateLabel . ' Order Report';
        }
        $nextRun = $this->_calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth);
        $this->ScheduledReport->create();
        $saved = $this->ScheduledReport->save(array(
            'user_id' => $userId, 'name' => $name, 'filters_json' => $filtersJson,
            'frequency' => $frequency, 'send_time' => $sendTime,
            'day_of_week'  => $frequency === 'weekly' ? (int)$dayOfWeek : null,
            'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
            'email' => $email, 'is_active' => 1, 'next_run_at' => $nextRun,
        ));
        if ($this->request->is('ajax')) {
            $this->autoRender = false;
            $this->response->type('json');
            $this->response->body(json_encode(array('success' => (bool)$saved,
                'message' => $saved ? 'Scheduled report saved!' : 'Failed to save.')));
            return $this->response;
        }
        $this->Session->setFlash($saved ? 'Scheduled report saved!' : 'Failed to save report.');
        return $this->redirect(array('action' => 'scheduledReports'));
    }

    public function scheduledReports() {
        $this->set('title_for_layout', 'Scheduled Reports');
        $userId = $this->Auth->user('id');
        $reports = $this->ScheduledReport->find('all', array(
            'conditions' => array('ScheduledReport.user_id' => $userId),
            'order' => array('ScheduledReport.created' => 'DESC'),
        ));
        $this->set(compact('reports'));
    }

    public function deleteScheduledReport($id) {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $this->ScheduledReport->deleteAll(array(
            'ScheduledReport.id' => (int)$id, 'ScheduledReport.user_id' => $userId,
        ));
        $this->Session->setFlash('Scheduled report deleted.');
        return $this->redirect(array('action' => 'scheduledReports'));
    }

    public function toggleScheduledReport($id) {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $report = $this->ScheduledReport->find('first', array(
            'conditions' => array('ScheduledReport.id' => (int)$id, 'ScheduledReport.user_id' => $userId),
        ));
        if ($report) {
            $this->ScheduledReport->id = (int)$id;
            $this->ScheduledReport->saveField('is_active', $report['ScheduledReport']['is_active'] ? 0 : 1);
        }
        return $this->redirect(array('action' => 'scheduledReports'));
    }

    private function _buildConditions($filters) {
        $conditions = array();
        if (!empty($filters['search'])) {
            $like = '%' . addcslashes($filters['search'], '%_') . '%';
            $conditions['OR'] = array(
                'Order.order_number LIKE' => $like, 'Order.product_description LIKE' => $like,
                'Order.pipcode LIKE' => $like, 'Order.supplier_id LIKE' => $like,
                'Order.response LIKE' => $like, 'Order.notes LIKE' => $like,
            );
        }
        $now = new DateTime();
        switch ($filters['dateFilter']) {
            case 'today':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'yesterday':
                $y = (clone $now)->modify('-1 day');
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $y)->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $y)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'last3days':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->modify('-3 days')->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'last7days':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->modify('-7 days')->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'thismonth':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->modify('first day of this month')->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->modify('last day of this month')->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'lastmonth':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->modify('first day of last month')->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->modify('last day of last month')->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'custom':
                if (!empty($filters['startDate'])) $conditions['Order.orderdate >='] = $filters['startDate'] . ' 00:00:00';
                if (!empty($filters['endDate'])) $conditions['Order.orderdate <='] = $filters['endDate'] . ' 23:59:59';
                break;
        }
        if (!empty($filters['groupedFilters']['groups'])) {
            $groupSql = $this->_buildGroupedFilterSql($filters['groupedFilters']);
            if ($groupSql) $conditions[] = $groupSql;
        }
        return $conditions;
    }

    private function _buildGroupedFilterSql($groupedFilters) {
        $groups    = isset($groupedFilters['groups']) ? $groupedFilters['groups'] : array();
        $matchType = isset($groupedFilters['match_type']) ? $groupedFilters['match_type'] : 'all';
        $db        = $this->Order->getDataSource();
        $groupSqls = array();
        foreach ($groups as $group) {
            if (empty($group['filters'])) continue;
            $logic = (isset($group['logic']) && $group['logic'] === 'or') ? ' OR ' : ' AND ';
            $filterSqls = array();
            foreach ($group['filters'] as $filter) {
                $sql = $this->_buildSingleFilterSql($filter, $db);
                if ($sql !== null) $filterSqls[] = $sql;
            }
            if (!empty($filterSqls)) $groupSqls[] = '(' . implode($logic, $filterSqls) . ')';
        }
        if (empty($groupSqls)) return null;
        $topLogic = $matchType === 'any' ? ' OR ' : ' AND ';
        return '(' . implode($topLogic, $groupSqls) . ')';
    }

    private function _filterFieldDefinitions() {
        return array(
            'order_number'      => array('column' => 'Order.order_number',        'type' => 'string'),
            'description'       => array('column' => 'Order.product_description', 'type' => 'string'),
            'pipcode'           => array('column' => 'Order.pipcode',             'type' => 'string'),
            'supplier'          => array('column' => 'Order.supplier_id',         'type' => 'string'),
            'category'          => array('column' => 'Order.category',            'type' => 'string'),
            'stock_status'      => array('column' => 'Order.stock_status',        'type' => 'string'),
            'flag'              => array('column' => 'Order.flag',                'type' => 'string'),
            'response'          => array('column' => 'Order.response',            'type' => 'string'),
            'quantity'          => array('column' => 'Order.quantity',            'type' => 'number'),
            'approved_quantity' => array('column' => 'Order.approved_qty',        'type' => 'number'),
            'price'             => array('column' => 'Order.price',               'type' => 'number'),
            'dt_price'          => array('column' => 'Order.dt_price',            'type' => 'number'),
            'max_price'         => array('column' => 'Order.max_price',           'type' => 'number'),
            'subtotal'          => array('expression' => 'COALESCE(quantity, 0) * COALESCE(price, 0)', 'type' => 'number'),
            'order_date'        => array('column' => 'Order.orderdate',           'type' => 'datetime'),
        );
    }

    private function _buildSingleFilterSql($filter, $db) {
        $field    = isset($filter['field']) ? $filter['field'] : null;
        $operator = isset($filter['operator']) ? $filter['operator'] : null;
        $value    = isset($filter['value']) ? $filter['value'] : null;
        if (!$field || !$operator) return null;
        $defs = $this->_filterFieldDefinitions();
        if (!isset($defs[$field])) return null;
        $def    = $defs[$field];
        $type   = isset($def['type']) ? $def['type'] : 'string';
        $col    = isset($def['column']) ? $def['column'] : null;
        $expr   = isset($def['expression']) ? $def['expression'] : null;
        $target = $col ? $col : ($expr ? "($expr)" : null);
        if (!$target) return null;
        $qv = function($v) use ($db, $type) {
            if ($v === null || $v === '') return null;
            if ($type === 'number') return (float)$v;
            if ($type === 'datetime') return $db->value(date('Y-m-d H:i:s', strtotime((string)$v)));
            return $db->value((string)$v);
        };
        switch ($operator) {
            case 'equals':     $qval = $qv($value); return $qval !== null ? "$target = $qval" : null;
            case 'not_equals': $qval = $qv($value); return $qval !== null ? "$target != $qval" : null;
            case 'contains':   return ($value !== '' && $value !== null) ? "$target LIKE " . $db->value('%' . $value . '%') : null;
            case 'starts_with': return ($value !== '' && $value !== null) ? "$target LIKE " . $db->value($value . '%') : null;
            case 'ends_with':   return ($value !== '' && $value !== null) ? "$target LIKE " . $db->value('%' . $value) : null;
            case 'in': case 'not_in':
                $vals = array_filter(array_map('trim', explode(',', (string)$value)));
                if (empty($vals)) return null;
                $inList = implode(',', array_map(function($v) use ($db) { return $db->value($v); }, $vals));
                return $operator === 'not_in' ? "$target NOT IN ($inList)" : "$target IN ($inList)";
            case 'gt':  $qval = $qv($value); return $qval !== null ? "$target > $qval" : null;
            case 'gte': $qval = $qv($value); return $qval !== null ? "$target >= $qval" : null;
            case 'lt':  $qval = $qv($value); return $qval !== null ? "$target < $qval" : null;
            case 'lte': $qval = $qv($value); return $qval !== null ? "$target <= $qval" : null;
            case 'between':
                $vals = is_array($value) ? $value : array();
                $v1 = $qv(isset($vals[0]) ? $vals[0] : null);
                $v2 = $qv(isset($vals[1]) ? $vals[1] : null);
                if ($v1 === null || $v2 === null) return null;
                return "$target BETWEEN $v1 AND $v2";
            default: return null;
        }
    }

    private function _calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth) {
        $parts  = explode(':', $sendTime . ':00');
        $hour   = (int)$parts[0];
        $minute = (int)$parts[1];
        $now    = new DateTime();
        if ($frequency === 'weekly') {
            $dow  = (int)($dayOfWeek ?? 1);
            $next = clone $now;
            $next->setTime($hour, $minute, 0);
            while ((int)$next->format('N') % 7 != $dow % 7) $next->modify('+1 day');
            if ($next <= $now) $next->modify('+7 days');
        } elseif ($frequency === 'monthly') {
            $dom  = min((int)($dayOfMonth ?? 1), (int)$now->format('t'));
            $next = new DateTime(date('Y-m-') . sprintf('%02d', $dom) . ' ' . sprintf('%02d:%02d:00', $hour, $minute));
            if ($next <= $now) {
                $next->modify('+1 month');
                $next->setDate($next->format('Y'), $next->format('m'), min((int)($dayOfMonth ?? 1), (int)$next->format('t')));
                $next->setTime($hour, $minute, 0);
            }
        } else {
            $next = new DateTime('today ' . sprintf('%02d:%02d:00', $hour, $minute));
            if ($next <= $now) $next->modify('+1 day');
        }
        return $next->format('Y-m-d H:i:s');
    }
}
