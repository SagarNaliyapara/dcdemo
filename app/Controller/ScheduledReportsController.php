<?php
App::uses('AppController', 'Controller');
App::uses('ScheduledReportService', 'Lib/Services');

class ScheduledReportsController extends AppController {

	public $uses = array('ScheduledReport');

	public function index() {
		$service = new ScheduledReportService();
		$reports = $service->getForUser($this->Auth->user('id'));

		$activeCount = 0; $inactiveCount = 0; $lastRunTodayCount = 0;
		$today = date('Y-m-d');
		foreach ($reports as $r) {
			$sr = $r['ScheduledReport'];
			if ($sr['is_active']) $activeCount++;
			else $inactiveCount++;
			if ($sr['last_run_at'] && substr($sr['last_run_at'], 0, 10) === $today) $lastRunTodayCount++;
		}

		$timeOptions = array();
		for ($h = 0; $h < 24; $h++) {
			for ($m = 0; $m < 60; $m += 15) {
				$timeOptions[] = sprintf('%02d:%02d', $h, $m);
			}
		}

		$this->set(compact('reports', 'activeCount', 'inactiveCount', 'lastRunTodayCount', 'timeOptions'));
		$this->set('title_for_layout', 'Scheduled Reports');
	}

	public function toggleActive($id) {
		$service = new ScheduledReportService();
		$service->toggleActive($id, $this->Auth->user('id'));
		return $this->redirect(array('action' => 'index'));
	}

	public function edit($id) {
		$this->request->allowMethod('post');
		$service = new ScheduledReportService();
		$service->updateReport($id, $this->Auth->user('id'), $this->request->data);
		$this->flashSuccess('Schedule updated successfully.');
		return $this->redirect(array('action' => 'index'));
	}

	public function delete($id) {
		$this->request->allowMethod('post');
		$service = new ScheduledReportService();
		$service->delete($id, $this->Auth->user('id'));
		$this->flashSuccess('Scheduled report deleted.');
		return $this->redirect(array('action' => 'index'));
	}
}
