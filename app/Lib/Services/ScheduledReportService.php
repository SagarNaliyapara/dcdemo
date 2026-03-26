<?php

class ScheduledReportService {

	public function getDueReports() {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');
		return $ScheduledReport->find('all', array(
			'conditions' => array(
				'ScheduledReport.is_active' => 1,
				'ScheduledReport.next_run_at IS NOT NULL',
				'ScheduledReport.next_run_at <=' => date('Y-m-d H:i:s'),
			),
		));
	}

	public function markSent($reportId) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');
		$report = $ScheduledReport->find('first', array('conditions' => array('ScheduledReport.id' => $reportId)));
		if (!$report) return;
		$r = $report['ScheduledReport'];
		$nextRun = $this->calculateNextRun($r['frequency'], $r['send_time'], $r['day_of_week'], $r['day_of_month']);
		return $ScheduledReport->save(array('ScheduledReport' => array(
			'id' => $reportId,
			'next_run_at' => $nextRun,
			'modified' => date('Y-m-d H:i:s'),
		)));
	}

	public function markFailed($reportId) {
		// Log only — no error column in scheduled_reports table
		CakeLog::write('error', '[SendScheduledReports] Report ID ' . $reportId . ' failed.');
	}

	public function getOrdersForReport($filters) {
		require_once APP . 'Lib' . DS . 'Services' . DS . 'OrderService.php';
		$orderService = new OrderService();
		return $orderService->getAllOrders($filters);
	}

	public function createScheduledReport($userId, $filters, $data) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');

		$frequency = $data['frequency'];
		$sendTime = $data['send_time'];
		$dayOfWeek = isset($data['day_of_week']) ? $data['day_of_week'] : null;
		$dayOfMonth = isset($data['day_of_month']) ? $data['day_of_month'] : null;

		$name = !empty($data['name']) ? $data['name'] : $this->buildAutoName($filters, $frequency);

		$record = array(
			'ScheduledReport' => array(
				'user_id' => $userId,
				'name' => $name,
				'filters_json' => json_encode($filters),
				'frequency' => $frequency,
				'send_time' => $sendTime,
				'day_of_week' => $frequency === 'weekly' ? (int)$dayOfWeek : null,
				'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
				'email' => $data['email'],
				'is_active' => 1,
				'next_run_at' => $this->calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth),
				'created' => date('Y-m-d H:i:s'),
				'modified' => date('Y-m-d H:i:s'),
			),
		);

		$ScheduledReport->create();
		return $ScheduledReport->save($record);
	}

	public function updateReport($reportId, $userId, $data) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');

		$report = $ScheduledReport->find('first', array(
			'conditions' => array('id' => $reportId, 'user_id' => $userId),
		));
		if (!$report) return false;

		$frequency = $data['frequency'];
		$sendTime = $data['send_time'];
		$dayOfWeek = isset($data['day_of_week']) ? $data['day_of_week'] : null;
		$dayOfMonth = isset($data['day_of_month']) ? $data['day_of_month'] : null;

		return $ScheduledReport->save(array(
			'ScheduledReport' => array(
				'id' => $reportId,
				'name' => !empty($data['name']) ? $data['name'] : $report['ScheduledReport']['name'],
				'frequency' => $frequency,
				'send_time' => $sendTime,
				'day_of_week' => $frequency === 'weekly' ? (int)$dayOfWeek : null,
				'day_of_month' => $frequency === 'monthly' ? (int)$dayOfMonth : null,
				'email' => $data['email'],
				'next_run_at' => $this->calculateNextRun($frequency, $sendTime, $dayOfWeek, $dayOfMonth),
				'modified' => date('Y-m-d H:i:s'),
			),
		));
	}

	public function getForUser($userId) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');
		return $ScheduledReport->find('all', array(
			'conditions' => array('user_id' => $userId),
			'order' => array('created DESC'),
		));
	}

	public function delete($reportId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');
		return $ScheduledReport->deleteAll(array('id' => $reportId, 'user_id' => $userId));
	}

	public function toggleActive($reportId, $userId) {
		App::uses('ClassRegistry', 'Utility');
		$ScheduledReport = ClassRegistry::init('ScheduledReport');
		$report = $ScheduledReport->find('first', array(
			'conditions' => array('id' => $reportId, 'user_id' => $userId),
		));
		if (!$report) return false;
		return $ScheduledReport->save(array(
			'ScheduledReport' => array(
				'id' => $reportId,
				'is_active' => !$report['ScheduledReport']['is_active'],
				'modified' => date('Y-m-d H:i:s'),
			),
		));
	}

	public function calculateNextRun($frequency, $sendTime, $dayOfWeek = 1, $dayOfMonth = 1) {
		list($hour, $minute) = explode(':', $sendTime);
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

	private function buildAutoName($filters, $frequency) {
		$dateLabel = isset($filters['dateFilter']) ? $this->dateFilterLabel($filters['dateFilter']) : 'All Orders';
		return ucfirst($frequency) . ' – ' . $dateLabel . ' Order Report';
	}

	private function dateFilterLabel($key) {
		$labels = array(
			'today' => 'Today', 'yesterday' => 'Yesterday',
			'last3days' => 'Last 3 Days', 'last7days' => 'Last 7 Days',
			'thismonth' => 'This Month', 'lastmonth' => 'Last Month',
		);
		return isset($labels[$key]) ? $labels[$key] : 'All Orders';
	}

	private function nextDailyRun($now, $hour, $minute) {
		$candidate = mktime($hour, $minute, 0, date('n'), date('j'), date('Y'));
		if ($candidate <= $now) $candidate = strtotime('+1 day', $candidate);
		return date('Y-m-d H:i:s', $candidate);
	}

	private function nextWeeklyRun($now, $dayOfWeek, $hour, $minute) {
		$currentDow = (int)date('w');
		$daysUntil = ($dayOfWeek - $currentDow + 7) % 7;
		$candidate = mktime($hour, $minute, 0, date('n'), date('j') + $daysUntil, date('Y'));
		if ($candidate <= $now) $candidate = strtotime('+7 days', $candidate);
		return date('Y-m-d H:i:s', $candidate);
	}

	private function nextMonthlyRun($now, $dayOfMonth, $hour, $minute) {
		$daysInMonth = (int)date('t');
		$safeDay = min($dayOfMonth, $daysInMonth);
		$candidate = mktime($hour, $minute, 0, date('n'), $safeDay, date('Y'));
		if ($candidate <= $now) {
			$nextMonth = date('n') + 1;
			$nextYear = date('Y');
			if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
			$daysInNextMonth = cal_days_in_month(CAL_GREGORIAN, $nextMonth, $nextYear);
			$candidate = mktime($hour, $minute, 0, $nextMonth, min($dayOfMonth, $daysInNextMonth), $nextYear);
		}
		return date('Y-m-d H:i:s', $candidate);
	}
}
