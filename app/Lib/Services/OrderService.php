<?php
App::uses('OrderHistoryFilterService', 'Lib/Services');

class OrderService {

	private $filterService;
	private $db;

	public function __construct() {
		$this->filterService = new OrderHistoryFilterService();
		App::uses('ConnectionManager', 'Model');
		$this->db = ConnectionManager::getDataSource('default');
	}

	public function updateOrderFlag($orderId, $flag) {
		$db = $this->db;
		$db->execute('UPDATE orders SET flag = ?, modified = NOW() WHERE id = ?', array($flag ?: null, $orderId));
	}

	public function updateOrderNote($orderId, $note) {
		$db = $this->db;
		$db->execute('UPDATE orders SET notes = ?, modified = NOW() WHERE id = ?', array($note, $orderId));
	}

	public function getPaginatedOrders($filters, $perPage = 25, $page = 1) {
		$sql = $this->buildSelectSql($filters);
		$countSql = $this->buildCountSql($filters);
		$params = $this->buildParams($filters);

		$offset = ($page - 1) * $perPage;
		$sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

		$rows = $this->db->fetchAll($sql, $params);
		$countRow = $this->db->fetchAll($countSql, $params);
		$total = isset($countRow[0][0]['total']) ? (int)$countRow[0][0]['total'] : 0;

		return array(
			'data' => array_map(function($r) { return isset($r[0]) ? $r[0] : $r; }, $rows),
			'total' => $total,
			'perPage' => $perPage,
			'page' => $page,
			'lastPage' => max(1, ceil($total / $perPage)),
		);
	}

	public function getTotalAmount($filters) {
		$where = $this->buildWhereSql($filters);
		$params = $this->buildParams($filters);
		$sql = 'SELECT COALESCE(SUM(quantity * price), 0) as total FROM orders' . ($where ? ' WHERE ' . $where : '');
		$result = $this->db->fetchAll($sql, $params);
		return isset($result[0][0]['total']) ? (float)$result[0][0]['total'] : 0.0;
	}

	public function getAllOrders($filters) {
		$sql = $this->buildSelectSql($filters);
		$params = $this->buildParams($filters);
		$rows = $this->db->fetchAll($sql, $params);
		return array_map(function($r) { return isset($r[0]) ? $r[0] : $r; }, $rows);
	}

	private function buildSelectSql($filters) {
		$where = $this->buildWhereSql($filters);
		$sortField = $this->sanitizeSortField($filters['sortField'] ?? 'orderdate');
		$sortDir = strtolower($filters['sortDirection'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
		$sql = 'SELECT * FROM orders';
		if ($where) $sql .= ' WHERE ' . $where;
		$sql .= ' ORDER BY ' . $sortField . ' ' . $sortDir;
		return $sql;
	}

	private function buildCountSql($filters) {
		$where = $this->buildWhereSql($filters);
		$sql = 'SELECT COUNT(*) as total FROM orders';
		if ($where) $sql .= ' WHERE ' . $where;
		return $sql;
	}

	private function buildWhereSql($filters) {
		$conditions = array();
		$params = array();

		$this->applySearch($conditions, $params, $filters['search'] ?? '');
		$this->applyDateRange($conditions, $params, $filters['dateFilter'] ?? 'all', $filters['startDate'] ?? '', $filters['endDate'] ?? '');

		$groupedFilters = $filters['groupedFilters'] ?? array();
		if (!empty($groupedFilters['groups'])) {
			$this->filterService->buildGroupedConditions($groupedFilters, $conditions, $joins, $params);
		}

		$this->_lastParams = $params;
		return implode(' AND ', $conditions);
	}

	private $_lastParams = array();

	private function buildParams($filters) {
		$this->buildWhereSql($filters);
		return $this->_lastParams;
	}

	private function applySearch(&$conditions, &$params, $search) {
		if (trim($search) === '') return;
		$like = '%' . $search . '%';
		$conditions[] = '(order_number LIKE ? OR product_description LIKE ? OR pipcode LIKE ? OR supplier_id LIKE ? OR response LIKE ? OR notes LIKE ?)';
		for ($i = 0; $i < 6; $i++) $params[] = $like;
	}

	private function applyDateRange(&$conditions, &$params, $dateFilter, $startDate, $endDate) {
		list($start, $end) = $this->resolveDateRange($dateFilter, $startDate, $endDate);
		if ($start && $end) {
			$conditions[] = 'orderdate BETWEEN ? AND ?';
			$params[] = $start;
			$params[] = $end;
		}
	}

	private function resolveDateRange($dateFilter, $startDate, $endDate) {
		$now = date('Y-m-d H:i:s');
		$today = date('Y-m-d');
		switch ($dateFilter) {
			case 'today':
				return array($today . ' 00:00:00', $today . ' 23:59:59');
			case 'yesterday':
				$y = date('Y-m-d', strtotime('-1 day'));
				return array($y . ' 00:00:00', $y . ' 23:59:59');
			case 'last3days':
				return array(date('Y-m-d', strtotime('-3 days')) . ' 00:00:00', $today . ' 23:59:59');
			case 'last7days':
				return array(date('Y-m-d', strtotime('-7 days')) . ' 00:00:00', $today . ' 23:59:59');
			case 'thismonth':
				return array(date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59');
			case 'lastmonth':
				$firstLast = date('Y-m-01', strtotime('first day of last month'));
				$lastLast = date('Y-m-t', strtotime('last day of last month'));
				return array($firstLast . ' 00:00:00', $lastLast . ' 23:59:59');
			case 'custom':
				if ($startDate && $endDate) {
					return array($startDate . ' 00:00:00', $endDate . ' 23:59:59');
				}
				return array(null, null);
			default:
				return array(null, null);
		}
	}

	private function sanitizeSortField($field) {
		$allowed = array(
			'id', 'order_number', 'product_description', 'supplier_id', 'pipcode',
			'category', 'quantity', 'approved_qty', 'price', 'dt_price', 'max_price',
			'orderdate', 'response', 'stock_status', 'flag', 'notes', 'created', 'modified'
		);
		return in_array($field, $allowed) ? $field : 'orderdate';
	}
}
