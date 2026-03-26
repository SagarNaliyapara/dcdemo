<?php
App::uses('OrderHistoryFilterService', 'Lib/Services');

class NotificationRulePreviewService {

	private $filterService;
	private $db;

	public function __construct() {
		$this->filterService = new OrderHistoryFilterService();
		App::uses('ConnectionManager', 'Model');
		$this->db = ConnectionManager::getDataSource('default');
	}

	public function preview($data, $limit = 20) {
		$query = $this->buildQuery($data);
		$countSql = 'SELECT COUNT(*) as total FROM orders WHERE approved_qty > 0' . ($query['where'] ? ' AND ' . $query['where'] : '');
		$countRow = $this->db->fetchAll($countSql, $query['params']);
		$countFirst = !empty($countRow[0]) ? self::extractRow($countRow[0]) : array();
		$matchCount = isset($countFirst['total']) ? (int)$countFirst['total'] : 0;

		$sql = 'SELECT * FROM orders WHERE approved_qty > 0' . ($query['where'] ? ' AND ' . $query['where'] : '') . ' ORDER BY orderdate DESC LIMIT ' . (int)$limit;
		$rows = $this->db->fetchAll($sql, $query['params']);
		$rows = array_map(function($r) { return self::extractRow($r); }, $rows);

		return array(
			'match_count' => $matchCount,
			'preview_count' => count($rows),
			'rows' => $rows,
		);
	}

	public function matchedOrders($rule, $limit = null) {
		$data = array(
			'match_type' => $rule['match_type'],
			'filters_json' => is_string($rule['filters_json']) ? json_decode($rule['filters_json'], true) : $rule['filters_json'],
			'date_scope_type' => $rule['date_scope_type'],
			'date_scope_value' => $rule['date_scope_value'],
			'date_scope_unit' => $rule['date_scope_unit'],
		);
		$query = $this->buildQuery($data);
		$sql = 'SELECT * FROM orders WHERE approved_qty > 0' . ($query['where'] ? ' AND ' . $query['where'] : '') . ' ORDER BY orderdate DESC';
		if ($limit !== null) $sql .= ' LIMIT ' . (int)$limit;
		$rows = $this->db->fetchAll($sql, $query['params']);
		return array_map(function($r) { return self::extractRow($r); }, $rows);
	}

	/**
	 * CakePHP 2.x fetchAll returns rows keyed by table name: ['orders' => ['col' => 'val', ...]]
	 * This helper flattens it to just ['col' => 'val', ...]
	 */
	public static function extractRow($r) {
		if (isset($r[0]) && is_array($r[0])) return $r[0];
		$first = reset($r);
		return is_array($first) ? $first : $r;
	}

	private function buildQuery($data) {
		$conditions = array();
		$params = array();

		list($start, $end) = $this->resolveDateScope($data);
		if ($start && $end) {
			$conditions[] = 'orderdate BETWEEN ? AND ?';
			$params[] = $start;
			$params[] = $end;
		}

		$filtersJson = isset($data['filters_json']) ? $data['filters_json'] : array();
		$groups = isset($filtersJson['groups']) ? $filtersJson['groups'] : array();
		if (!empty($groups)) {
			$this->filterService->buildGroupedConditions(array(
				'match_type' => $data['match_type'] ?? 'all',
				'groups' => $groups,
			), $conditions, $joins, $params);
		}

		return array('where' => implode(' AND ', $conditions), 'params' => $params);
	}

	private function resolveDateScope($data) {
		$type = $data['date_scope_type'] ?? 'last_30_days';
		$today = date('Y-m-d');
		switch ($type) {
			case 'today': return array($today . ' 00:00:00', $today . ' 23:59:59');
			case 'yesterday':
				$y = date('Y-m-d', strtotime('-1 day'));
				return array($y . ' 00:00:00', $y . ' 23:59:59');
			case 'last_7_days':
				return array(date('Y-m-d', strtotime('-7 days')) . ' 00:00:00', $today . ' 23:59:59');
			case 'this_week':
				return array(date('Y-m-d', strtotime('monday this week')) . ' 00:00:00', $today . ' 23:59:59');
			case 'this_month':
				return array(date('Y-m-01') . ' 00:00:00', date('Y-m-t') . ' 23:59:59');
			case 'custom_rolling':
				$val = max(1, (int)($data['date_scope_value'] ?? 30));
				$unit = $data['date_scope_unit'] ?? 'day';
				$map = array('month' => 'months', 'week' => 'weeks', 'day' => 'days');
				$unitStr = isset($map[$unit]) ? $map[$unit] : 'days';
				return array(date('Y-m-d', strtotime("-{$val} {$unitStr}")) . ' 00:00:00', $today . ' 23:59:59');
			default: // last_30_days
				return array(date('Y-m-d', strtotime('-30 days')) . ' 00:00:00', $today . ' 23:59:59');
		}
	}
}
