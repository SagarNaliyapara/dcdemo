<?php

class NotificationRuleService {

	public function listForUser($userId, $filters = array(), $perPage = 15, $page = 1) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');

		$conditions = array('NotificationRule.user_id' => $userId);
		if (!empty($filters['search'])) {
			$s = '%' . trim($filters['search']) . '%';
			$conditions['OR'] = array(
				'NotificationRule.name LIKE' => $s,
				'NotificationRule.recipient_email LIKE' => $s,
				'NotificationRule.status LIKE' => $s,
			);
		}
		if (!empty($filters['status']) && $filters['status'] !== 'all') {
			$conditions['NotificationRule.status'] = $filters['status'];
		}
		if (!empty($filters['frequency']) && $filters['frequency'] !== 'all') {
			$conditions['NotificationRule.frequency'] = $filters['frequency'];
		}

		$total = $NotificationRule->find('count', array('conditions' => $conditions));
		$rules = $NotificationRule->find('all', array(
			'conditions' => $conditions,
			'order' => array('NotificationRule.created DESC'),
			'limit' => $perPage,
			'offset' => ($page - 1) * $perPage,
		));

		return array(
			'data' => array_map(function($r) { return $r['NotificationRule']; }, $rules),
			'total' => $total,
			'perPage' => $perPage,
			'page' => $page,
			'lastPage' => max(1, ceil($total / $perPage)),
		);
	}

	public function getSummaryForUser($userId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$all = $NotificationRule->find('all', array('conditions' => array('user_id' => $userId)));
		$counts = array('total' => 0, 'active' => 0, 'inactive' => 0, 'attention' => 0);
		foreach ($all as $r) {
			$counts['total']++;
			$status = $r['NotificationRule']['status'];
			if ($status === 'active') $counts['active']++;
			elseif ($status === 'inactive') $counts['inactive']++;
			if (in_array($status, array('draft', 'error'))) $counts['attention']++;
		}
		return $counts;
	}

	public function create($userId, $data) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$NotificationRule->create();

		$frequency = $data['frequency'];
		$sendTime = $data['send_time'];
		$dayOfWeek = isset($data['day_of_week']) ? $data['day_of_week'] : 1;
		$dayOfMonth = isset($data['day_of_month']) ? $data['day_of_month'] : 1;
		$status = $data['status'];

		$record = array('NotificationRule' => array(
			'user_id' => $userId,
			'name' => !empty($data['name']) ? $data['name'] : $this->buildAutoName($data),
			'channel' => $data['channel'] ?? 'email',
			'data_source' => $data['data_source'] ?? 'orders',
			'status' => $status,
			'date_scope_type' => $data['date_scope_type'] ?? 'last_30_days',
			'date_scope_value' => !empty($data['date_scope_value']) ? (int)$data['date_scope_value'] : null,
			'date_scope_unit' => $data['date_scope_unit'] ?? 'day',
			'match_type' => $data['match_type'] ?? 'all',
			'filters_json' => is_string($data['filters_json']) ? $data['filters_json'] : json_encode($data['filters_json']),
			'recipient_email' => $data['recipient_email'],
			'email_row_limit' => (int)($data['email_row_limit'] ?? 300),
			'frequency' => $frequency,
			'send_time' => $sendTime,
			'day_of_week' => $frequency === 'weekly' ? (int)$dayOfWeek : null,
			'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
			'next_run_at' => $status === 'active' ? $this->calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth) : null,
			'created' => date('Y-m-d H:i:s'),
			'modified' => date('Y-m-d H:i:s'),
		));

		return $NotificationRule->save($record);
	}

	public function update($ruleId, $userId, $data) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$rule = $NotificationRule->find('first', array('conditions' => array('id' => $ruleId, 'user_id' => $userId)));
		if (!$rule) return false;

		$frequency = $data['frequency'];
		$sendTime = $data['send_time'];
		$dayOfWeek = isset($data['day_of_week']) ? $data['day_of_week'] : 1;
		$dayOfMonth = isset($data['day_of_month']) ? $data['day_of_month'] : 1;
		$status = $data['status'];

		return $NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'name' => !empty($data['name']) ? $data['name'] : $rule['NotificationRule']['name'],
			'channel' => $data['channel'] ?? 'email',
			'data_source' => $data['data_source'] ?? 'orders',
			'status' => $status,
			'date_scope_type' => $data['date_scope_type'] ?? 'last_30_days',
			'date_scope_value' => !empty($data['date_scope_value']) ? (int)$data['date_scope_value'] : null,
			'date_scope_unit' => $data['date_scope_unit'] ?? 'day',
			'match_type' => $data['match_type'] ?? 'all',
			'filters_json' => is_string($data['filters_json']) ? $data['filters_json'] : json_encode($data['filters_json']),
			'recipient_email' => $data['recipient_email'],
			'email_row_limit' => (int)($data['email_row_limit'] ?? 300),
			'frequency' => $frequency,
			'send_time' => $sendTime,
			'day_of_week' => $frequency === 'weekly' ? (int)$dayOfWeek : null,
			'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
			'next_run_at' => $status === 'active' ? $this->calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth) : null,
			'last_error_message' => null,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function activate($ruleId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$rule = $NotificationRule->find('first', array('conditions' => array('id' => $ruleId, 'user_id' => $userId)));
		if (!$rule) return false;
		$r = $rule['NotificationRule'];
		return $NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'status' => 'active',
			'next_run_at' => $this->calculateNextRun($r['frequency'], $r['send_time'], $r['day_of_week'], $r['day_of_month']),
			'last_error_message' => null,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function deactivate($ruleId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		return $NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'status' => 'inactive',
			'next_run_at' => null,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function delete($ruleId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		return $NotificationRule->deleteAll(array('id' => $ruleId, 'user_id' => $userId));
	}

	public function duplicate($ruleId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$rule = $NotificationRule->find('first', array('conditions' => array('id' => $ruleId, 'user_id' => $userId)));
		if (!$rule) return false;
		$copy = $rule['NotificationRule'];
		unset($copy['id']);
		$copy['name'] = trim($copy['name'] . ' Copy');
		$copy['status'] = 'draft';
		$copy['last_queued_at'] = null;
		$copy['last_run_at'] = null;
		$copy['next_run_at'] = null;
		$copy['last_result_count'] = null;
		$copy['last_error_message'] = null;
		$copy['created'] = date('Y-m-d H:i:s');
		$copy['modified'] = date('Y-m-d H:i:s');
		$NotificationRule->create();
		return $NotificationRule->save(array('NotificationRule' => $copy));
	}

	public function dueRules() {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		return $NotificationRule->find('all', array(
			'conditions' => array(
				'status' => 'active',
				'next_run_at IS NOT NULL' => true,
				'next_run_at <=' => date('Y-m-d H:i:s'),
				'OR' => array(
					'last_queued_at IS NULL' => true,
					'last_queued_at < next_run_at' => true,
				),
			),
		));
	}

	public function markQueued($ruleId) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		return $NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'last_queued_at' => date('Y-m-d H:i:s'),
			'last_error_message' => null,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function markSuccessfulRun($ruleId, $resultCount) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$rule = $NotificationRule->findById($ruleId);
		if (!$rule) return;
		$r = $rule['NotificationRule'];
		$NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'last_queued_at' => null,
			'last_run_at' => date('Y-m-d H:i:s'),
			'next_run_at' => $this->calculateNextRun($r['frequency'], $r['send_time'], $r['day_of_week'], $r['day_of_month']),
			'last_result_count' => $resultCount,
			'last_error_message' => null,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function markFailed($ruleId, $message) {
		App::uses('ClassRegistry', 'Utility');
		$NotificationRule = ClassRegistry::init('NotificationRule');
		$NotificationRule->save(array('NotificationRule' => array(
			'id' => $ruleId,
			'status' => 'error',
			'last_queued_at' => null,
			'last_error_message' => $message,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function calculateNextRun($frequency, $sendTime, $dayOfWeek = 1, $dayOfMonth = 1) {
		list($hour, $minute) = explode(':', (string)$sendTime);
		$now = time();
		switch ($frequency) {
			case 'weekly':
				return $this->nextWeeklyRun($now, (int)$dayOfWeek, (int)$hour, (int)$minute);
			case 'monthly':
				return $this->nextMonthlyRun($now, (int)$dayOfMonth, (int)$hour, (int)$minute);
			default:
				return $this->nextDailyRun($now, (int)$hour, (int)$minute);
		}
	}

	private function buildAutoName($data) {
		$scopeLabel = isset($data['date_scope_type']) ? ucfirst(str_replace('_', ' ', $data['date_scope_type'])) : 'Last 30 Days';
		return ucfirst($data['frequency'] ?? 'daily') . ' ' . $scopeLabel . ' Notification';
	}

	private function nextDailyRun($now, $hour, $minute) {
		$c = mktime($hour, $minute, 0, date('n'), date('j'), date('Y'));
		return date('Y-m-d H:i:s', $c <= $now ? strtotime('+1 day', $c) : $c);
	}

	private function nextWeeklyRun($now, $dow, $hour, $minute) {
		$cur = (int)date('w');
		$days = ($dow - $cur + 7) % 7;
		$c = mktime($hour, $minute, 0, date('n'), date('j') + $days, date('Y'));
		return date('Y-m-d H:i:s', $c <= $now ? strtotime('+7 days', $c) : $c);
	}

	private function nextMonthlyRun($now, $dom, $hour, $minute) {
		$safeDay = min($dom, (int)date('t'));
		$c = mktime($hour, $minute, 0, date('n'), $safeDay, date('Y'));
		if ($c <= $now) {
			$nm = date('n') + 1; $ny = date('Y');
			if ($nm > 12) { $nm = 1; $ny++; }
			$c = mktime($hour, $minute, 0, $nm, min($dom, cal_days_in_month(CAL_GREGORIAN, $nm, $ny)), $ny);
		}
		return date('Y-m-d H:i:s', $c);
	}
}
