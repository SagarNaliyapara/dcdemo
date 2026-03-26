<?php
App::uses('AppShell', 'Console/Command');
App::uses('ClassRegistry', 'Utility');
App::uses('CakeEmail', 'Network/Email');

/**
 * Shell to process and send due scheduled reports.
 * Run: php app/Console/cake.php send_scheduled_reports
 * Add to cron every 30 minutes:
 *   */30 * * * * cd /path/to/dc-cakephp && php app/Console/cake.php send_scheduled_reports
 */
class SendScheduledReportsShell extends AppShell {

    public $uses = array('ScheduledReport', 'Order');

    public function main() {
        $this->out('[SendScheduledReports] Starting at ' . date('Y-m-d H:i:s'));

        App::import('Lib', 'Services/ScheduledReportService');
        App::import('Lib', 'Services/OrderService');

        $service = new ScheduledReportService();
        $orderService = new OrderService();

        $due = $service->getDueReports();
        $this->out('Found ' . count($due) . ' due report(s).');

        foreach ($due as $reportRow) {
            $report = $reportRow['ScheduledReport'];
            $this->out('Processing: ' . $report['name']);

            try {
                // Mark as queued
                $service->markQueued($report['id']);

                // Build filters from filters_json
                $filters = !empty($report['filters_json']) ? json_decode($report['filters_json'], true) : array();

                // Fetch matching orders
                $allOrders = $orderService->getOrdersForReport($filters);
                $totalCount = count($allOrders);
                $totalAmount = array_sum(array_column(array_column($allOrders, 'Order'), 'total_amount'));

                // Send to each email
                $emails = array_filter(array_map('trim', explode("\n", $report['emails'])));
                foreach ($emails as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
                    $this->_sendReportEmail($email, $report, $allOrders, $totalCount, $totalAmount);
                }

                // Mark sent and update next_run_at
                $service->markSent($report['id']);
                $this->out('  ✓ Sent to ' . count($emails) . ' recipient(s). Orders: ' . $totalCount);
            } catch (Exception $e) {
                $service->markFailed($report['id']);
                $this->out('  ✗ Error: ' . $e->getMessage());
            }
        }

        $this->out('[SendScheduledReports] Done.');
    }

    protected function _sendReportEmail($email, $report, $orders, $totalCount, $totalAmount) {
        $mailer = new CakeEmail('default');
        $mailer->template('scheduled_report', 'default')
               ->emailFormat('html')
               ->to($email)
               ->subject('📊 ' . $report['name'] . ' — ' . date('M j, Y'))
               ->viewVars(array(
                   'report'      => $report,
                   'orders'      => $orders,
                   'totalCount'  => $totalCount,
                   'totalAmount' => $totalAmount,
               ))
               ->send();
    }
}
