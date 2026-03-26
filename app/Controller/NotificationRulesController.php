<?php
App::uses('AppController', 'Controller');

class NotificationRulesController extends AppController {
    public $uses = array('NotificationRule', 'Order');
    public $components = array('Paginator', 'Session', 'Auth', 'Flash');

    public function index() {
        $this->set('title_for_layout', 'Notification Rules');
        $userId = $this->Auth->user('id');
        $conditions = array('NotificationRule.user_id' => $userId);
        $search = isset($this->request->query['search']) ? trim($this->request->query['search']) : '';
        $statusFilter = isset($this->request->query['status']) ? $this->request->query['status'] : 'all';
        $freqFilter = isset($this->request->query['frequency']) ? $this->request->query['frequency'] : 'all';
        if (!empty($search)) {
            $like = '%' . $search . '%';
            $conditions['OR'] = array(
                'NotificationRule.name LIKE' => $like,
                'NotificationRule.recipient_email LIKE' => $like,
            );
        }
        if ($statusFilter !== 'all') $conditions['NotificationRule.status'] = $statusFilter;
        if ($freqFilter !== 'all') $conditions['NotificationRule.frequency'] = $freqFilter;
        $this->Paginator->settings = array(
            'NotificationRule' => array(
                'conditions' => $conditions,
                'order' => array('NotificationRule.created' => 'DESC'),
                'limit' => 15, 'recursive' => -1,
            )
        );
        $rules = $this->Paginator->paginate('NotificationRule');
        $this->set(compact('rules', 'search', 'statusFilter', 'freqFilter'));
    }

    public function create() {
        $this->set('title_for_layout', 'Create Notification Rule');
        $this->set('isEdit', false);
        $this->set('rule', array());
        $this->set('filterFieldDefinitions', $this->_filterFieldDefinitions());
        $userEmail = $this->Auth->user('email');
        $this->set('userEmail', $userEmail);
        $this->set('validationError', null);
        if ($this->request->is('post')) {
            $userId = $this->Auth->user('id');
            $validationError = $this->_validateGroupFilter($this->request->data);
            if ($validationError) {
                $this->set('validationError', $validationError);
                // Re-populate rule with submitted data so form fields retain values
                $this->set('rule', array_merge(array('filters_json' => isset($this->request->data['filters_json']) ? $this->request->data['filters_json'] : '{}'), $this->request->data));
            } else {
                $data = $this->_extractRuleData($this->request->data, $userId);
                $this->NotificationRule->create();
                if ($this->NotificationRule->save($data)) {
                    $this->Session->setFlash('Notification rule created successfully!');
                    return $this->redirect(array('action' => 'index'));
                }
                $this->Session->setFlash('Failed to save notification rule.');
            }
        }
        $this->render('form');
    }

    public function edit($id) {
        $this->set('title_for_layout', 'Edit Notification Rule');
        $this->set('isEdit', true);
        $userId = $this->Auth->user('id');
        $rule = $this->NotificationRule->find('first', array(
            'conditions' => array('NotificationRule.id' => (int)$id, 'NotificationRule.user_id' => $userId),
        ));
        if (!$rule) {
            $this->Session->setFlash('Rule not found.');
            return $this->redirect(array('action' => 'index'));
        }
        $this->set('rule', $rule['NotificationRule']);
        $this->set('filterFieldDefinitions', $this->_filterFieldDefinitions());
        $userEmail = $this->Auth->user('email');
        $this->set('userEmail', $userEmail);
        $this->set('validationError', null);
        if ($this->request->is('post')) {
            $validationError = $this->_validateGroupFilter($this->request->data);
            if ($validationError) {
                $this->set('validationError', $validationError);
                // Re-populate rule with submitted data so form fields retain values
                $this->set('rule', array_merge($rule['NotificationRule'], $this->request->data));
            } else {
                $data = $this->_extractRuleData($this->request->data, $userId);
                $data['id'] = (int)$id;
                if ($this->NotificationRule->save($data)) {
                    $this->Session->setFlash('Notification rule updated successfully!');
                    return $this->redirect(array('action' => 'index'));
                }
                $this->Session->setFlash('Failed to update notification rule.');
            }
        }
        $this->render('form');
    }

    public function delete($id) {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $this->NotificationRule->deleteAll(array(
            'NotificationRule.id' => (int)$id, 'NotificationRule.user_id' => $userId,
        ));
        $this->Session->setFlash('Notification rule deleted.');
        return $this->redirect(array('action' => 'index'));
    }

    public function toggleStatus($id) {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $rule   = $this->NotificationRule->find('first', array(
            'conditions' => array('NotificationRule.id' => (int)$id, 'NotificationRule.user_id' => $userId),
        ));
        if ($rule) {
            $currentStatus = $rule['NotificationRule']['status'];
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            $this->NotificationRule->id = (int)$id;
            $this->NotificationRule->saveField('status', $newStatus);
            if ($newStatus === 'active') {
                $nextRun = $this->_calculateNextRun(
                    $rule['NotificationRule']['frequency'],
                    $rule['NotificationRule']['send_time'],
                    $rule['NotificationRule']['day_of_week'],
                    $rule['NotificationRule']['day_of_month']
                );
                $this->NotificationRule->saveField('next_run_at', $nextRun);
            } else {
                $this->NotificationRule->saveField('next_run_at', null);
            }
        }
        return $this->redirect(array('action' => 'index'));
    }

    public function duplicate($id) {
        $this->request->allowMethod('post');
        $userId = $this->Auth->user('id');
        $rule   = $this->NotificationRule->find('first', array(
            'conditions' => array('NotificationRule.id' => (int)$id, 'NotificationRule.user_id' => $userId),
        ));
        if ($rule) {
            $data = $rule['NotificationRule'];
            unset($data['id'], $data['created'], $data['modified']);
            $data['name'] = trim(($data['name'] ?? 'Rule') . ' Copy');
            $data['status'] = 'draft';
            $data['last_queued_at'] = $data['last_run_at'] = $data['next_run_at'] = null;
            $data['last_result_count'] = null;
            $data['last_error_message'] = null;
            $this->NotificationRule->create();
            $this->NotificationRule->save($data);
            $this->Session->setFlash('Rule duplicated.');
        }
        return $this->redirect(array('action' => 'index'));
    }

    public function previewOrders() {
        $this->request->allowMethod('post');
        $this->autoRender = false;
        $this->response->type('json');
        $filtersJson    = isset($this->request->data['filters_json']) ? $this->request->data['filters_json'] : '{}';
        $dateScopeType  = isset($this->request->data['date_scope_type']) ? $this->request->data['date_scope_type'] : 'last_30_days';
        $matchType      = isset($this->request->data['match_type']) ? $this->request->data['match_type'] : 'all';
        $groupedFilters = json_decode($filtersJson, true) ?: array();
        $conditions = $this->_buildDateScope($dateScopeType,
            isset($this->request->data['date_scope_value']) ? $this->request->data['date_scope_value'] : null,
            isset($this->request->data['date_scope_unit']) ? $this->request->data['date_scope_unit'] : null);
        if (!empty($groupedFilters['groups'])) {
            $groupSql = $this->_buildGroupedFilterSql(array_merge($groupedFilters, array('match_type' => $matchType)));
            if ($groupSql) $conditions[] = $groupSql;
        }
        $orders = $this->Order->find('all', array(
            'conditions' => $conditions, 'limit' => 20,
            'order' => array('Order.orderdate' => 'DESC'),
            'fields' => array('id', 'order_number', 'product_description', 'supplier_id', 'quantity', 'price', 'response', 'orderdate'),
            'recursive' => -1,
        ));
        $this->response->body(json_encode(array('success' => true, 'orders' => $orders, 'count' => count($orders))));
        return $this->response;
    }

    private function _validateGroupFilter($postData) {
        $filtersJson    = isset($postData['filters_json']) ? $postData['filters_json'] : '{}';
        $groupedFilters = json_decode($filtersJson, true);
        if (empty($groupedFilters['groups'])) {
            return 'Please add at least one Group Filter before saving. Use the "Add Group" button to define filter conditions.';
        }
        foreach ($groupedFilters['groups'] as $group) {
            if (!empty($group['filters'])) {
                foreach ($group['filters'] as $f) {
                    if (!empty($f['field']) && !empty($f['operator'])) {
                        return null; // valid — at least one complete filter found
                    }
                }
            }
        }
        return 'Your Group Filter is empty. Please select a field and operator for at least one filter row.';
    }

    private function _extractRuleData($postData, $userId) {
        $status    = isset($postData['status']) ? $postData['status'] : 'draft';
        $frequency = isset($postData['frequency']) ? $postData['frequency'] : 'daily';
        $sendTime  = isset($postData['send_time']) ? $postData['send_time'] : '08:00';
        $dayOfWeek = isset($postData['day_of_week']) ? $postData['day_of_week'] : null;
        $dayOfMonth = isset($postData['day_of_month']) ? $postData['day_of_month'] : null;
        $nextRun = $status === 'active'
            ? $this->_calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth) : null;
        $name = isset($postData['name']) ? trim($postData['name']) : '';
        if (empty($name)) {
            $dst = isset($postData['date_scope_type']) ? $postData['date_scope_type'] : 'last_30_days';
            $labels = array('last_30_days' => 'Last 30 Days', 'last_7_days' => 'Last 7 Days',
                'this_week' => 'This Week', 'this_month' => 'This Month', 'today' => 'Today', 'yesterday' => 'Yesterday');
            $dateLabel = isset($labels[$dst]) ? $labels[$dst] : 'Recent';
            $name = ucfirst($frequency) . ' ' . $dateLabel . ' Notification';
        }
        return array(
            'user_id' => $userId, 'name' => $name,
            'channel'          => isset($postData['channel']) ? $postData['channel'] : 'email',
            'data_source'      => isset($postData['data_source']) ? $postData['data_source'] : 'orders',
            'status'           => $status,
            'date_scope_type'  => isset($postData['date_scope_type']) ? $postData['date_scope_type'] : 'last_30_days',
            'date_scope_value' => isset($postData['date_scope_value']) && $postData['date_scope_value'] !== '' ? (int)$postData['date_scope_value'] : null,
            'date_scope_unit'  => isset($postData['date_scope_unit']) ? $postData['date_scope_unit'] : null,
            'match_type'       => isset($postData['match_type']) ? $postData['match_type'] : 'all',
            'filters_json'     => isset($postData['filters_json']) ? $postData['filters_json'] : '{}',
            'recipient_email'  => isset($postData['recipient_email']) ? trim($postData['recipient_email']) : '',
            'email_row_limit'  => isset($postData['email_row_limit']) ? (int)$postData['email_row_limit'] : 300,
            'frequency'        => $frequency, 'send_time' => $sendTime,
            'day_of_week'  => $frequency === 'weekly' ? (int)$dayOfWeek : null,
            'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
            'next_run_at'  => $nextRun,
        );
    }

    private function _buildDateScope($type, $scopeValue = null, $scopeUnit = null) {
        $conditions = array();
        $now = new DateTime();
        switch ($type) {
            case 'today':
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $now)->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $now)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'yesterday':
                $y = (clone $now)->modify('-1 day');
                $conditions['Order.orderdate BETWEEN ? AND ?'] = array(
                    (clone $y)->setTime(0,0,0)->format('Y-m-d H:i:s'),
                    (clone $y)->setTime(23,59,59)->format('Y-m-d H:i:s')); break;
            case 'last_7_days':
                $conditions['Order.orderdate >='] = (clone $now)->modify('-7 days')->format('Y-m-d H:i:s'); break;
            case 'this_week':
                $conditions['Order.orderdate >='] = (clone $now)->modify('monday this week')->setTime(0,0,0)->format('Y-m-d H:i:s'); break;
            case 'this_month':
                $conditions['Order.orderdate >='] = (clone $now)->modify('first day of this month')->setTime(0,0,0)->format('Y-m-d H:i:s'); break;
            case 'custom_rolling':
                $unit = $scopeUnit ?: 'days';
                $value = (int)($scopeValue ?? 30);
                $conditions['Order.orderdate >='] = (clone $now)->modify("-$value $unit")->format('Y-m-d H:i:s'); break;
            default: // last_30_days
                $conditions['Order.orderdate >='] = (clone $now)->modify('-30 days')->format('Y-m-d H:i:s'); break;
        }
        return $conditions;
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
