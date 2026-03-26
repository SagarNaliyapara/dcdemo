<?php

class OrderHistoryFilterService {

	public function availableFilterFields() {
		return array(
			'order_number'      => array('column' => 'order_number', 'type' => 'string'),
			'description'       => array('column' => 'product_description', 'type' => 'string'),
			'pipcode'           => array('column' => 'pipcode', 'type' => 'string'),
			'supplier'          => array('column' => 'supplier_id', 'type' => 'string'),
			'category'          => array('column' => 'category', 'type' => 'string'),
			'stock_status'      => array('column' => 'stock_status', 'type' => 'string'),
			'flag'              => array('column' => 'flag', 'type' => 'string'),
			'response'          => array('column' => 'response', 'type' => 'string'),
			'quantity'          => array('column' => 'quantity', 'type' => 'number'),
			'approved_quantity' => array('column' => 'approved_qty', 'type' => 'number'),
			'price'             => array('column' => 'price', 'type' => 'number'),
			'dt_price'          => array('column' => 'dt_price', 'type' => 'number'),
			'max_price'         => array('column' => 'max_price', 'type' => 'number'),
			'subtotal'          => array('expression' => 'COALESCE(quantity, 0) * COALESCE(price, 0)', 'type' => 'number'),
			'order_date'        => array('column' => 'orderdate', 'type' => 'datetime'),
		);
	}

	public function getFieldDefinitionsForView() {
		$fields = $this->availableFilterFields();
		$result = array();
		foreach ($fields as $key => $def) {
			$type = $def['type'];
			$label = ucwords(str_replace('_', ' ', $key));
			$operators = array();
			if ($type === 'number') {
				$operators = array('equals', 'gt', 'gte', 'lt', 'lte', 'between');
			} elseif ($type === 'datetime') {
				$operators = array('gte', 'lte', 'between');
			} else {
				$operators = array('equals', 'not_equals', 'contains', 'starts_with', 'ends_with', 'in', 'not_in');
			}
			$result[] = array('key' => $key, 'label' => $label, 'type' => $type, 'operators' => $operators);
		}
		return $result;
	}

	public function buildGroupedConditions($groupedFilters, &$conditions, &$joins, &$params) {
		$groups = isset($groupedFilters['groups']) ? $groupedFilters['groups'] : array();
		$matchType = isset($groupedFilters['match_type']) ? $groupedFilters['match_type'] : 'all';

		if (empty($groups)) {
			return;
		}

		$groupSqls = array();
		foreach ($groups as $group) {
			$filters = isset($group['filters']) ? $group['filters'] : array();
			if (empty($filters)) continue;

			$filterSqls = array();
			$groupLogic = (isset($group['logic']) && $group['logic'] === 'or') ? 'OR' : 'AND';

			foreach ($filters as $filter) {
				$sql = $this->buildSingleFilterSql($filter, $params);
				if ($sql !== null) {
					$filterSqls[] = $sql;
				}
			}

			if (!empty($filterSqls)) {
				$groupSqls[] = '(' . implode(' ' . $groupLogic . ' ', $filterSqls) . ')';
			}
		}

		if (!empty($groupSqls)) {
			$topLogic = ($matchType === 'any') ? 'OR' : 'AND';
			$conditions[] = '(' . implode(' ' . $topLogic . ' ', $groupSqls) . ')';
		}
	}

	private function buildSingleFilterSql($filter, &$params) {
		$fields = $this->availableFilterFields();
		$fieldKey = isset($filter['field']) ? $filter['field'] : '';
		$operator = isset($filter['operator']) ? $filter['operator'] : '';
		$value = isset($filter['value']) ? $filter['value'] : '';

		if (!isset($fields[$fieldKey]) || $operator === '') {
			return null;
		}

		$def = $fields[$fieldKey];
		$type = $def['type'];
		$col = isset($def['column']) ? '`' . $def['column'] . '`' : null;
		$expr = isset($def['expression']) ? '(' . $def['expression'] . ')' : null;
		$target = $col ?: $expr;

		switch ($operator) {
			case 'equals':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' = ?';

			case 'not_equals':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' != ?';

			case 'contains':
				if ($value === '') return null;
				$params[] = '%' . $value . '%';
				return $target . ' LIKE ?';

			case 'starts_with':
				if ($value === '') return null;
				$params[] = $value . '%';
				return $target . ' LIKE ?';

			case 'ends_with':
				if ($value === '') return null;
				$params[] = '%' . $value;
				return $target . ' LIKE ?';

			case 'in':
				$items = is_array($value) ? $value : array_filter(array_map('trim', explode(',', (string)$value)));
				if (empty($items)) return null;
				$placeholders = implode(',', array_fill(0, count($items), '?'));
				foreach ($items as $item) $params[] = $item;
				return $target . ' IN (' . $placeholders . ')';

			case 'not_in':
				$items = is_array($value) ? $value : array_filter(array_map('trim', explode(',', (string)$value)));
				if (empty($items)) return null;
				$placeholders = implode(',', array_fill(0, count($items), '?'));
				foreach ($items as $item) $params[] = $item;
				return $target . ' NOT IN (' . $placeholders . ')';

			case 'gt':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' > ?';

			case 'gte':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' >= ?';

			case 'lt':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' < ?';

			case 'lte':
				if ($value === '' || $value === null) return null;
				$params[] = $this->castValue($value, $type);
				return $target . ' <= ?';

			case 'between':
				$vals = is_array($value) ? $value : array($value, isset($filter['secondary_value']) ? $filter['secondary_value'] : '');
				$from = isset($vals[0]) ? $vals[0] : '';
				$to = isset($vals[1]) ? $vals[1] : '';
				if ($from === '' || $to === '') return null;
				$params[] = $this->castValue($from, $type);
				$params[] = $this->castValue($to, $type);
				return $target . ' BETWEEN ? AND ?';

			default:
				return null;
		}
	}

	private function castValue($value, $type) {
		if ($type === 'number') return (float)$value;
		return (string)$value;
	}
}
