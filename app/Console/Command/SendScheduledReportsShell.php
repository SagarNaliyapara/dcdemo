<?php
App::uses('AppShell', 'Console/Command');
App::uses('ClassRegistry', 'Utility');
App::uses('CakeEmail', 'Network/Email');

/**
 * Shell to process and send due scheduled reports.
 * Run: php app/Console/cake.php send_scheduled_reports
 * Add to cron every 30 minutes:
 *   30 * * * * cd /path/to/dc-cakephp && php app/Console/cake.php send_scheduled_reports
 */
class SendScheduledReportsShell extends AppShell {

    public $uses = array('ScheduledReport', 'Order');

    public function main() {
        $this->out('[SendScheduledReports] Starting at ' . date('Y-m-d H:i:s'));

        require_once APP . 'Lib' . DS . 'Services' . DS . 'ScheduledReportService.php';
        require_once APP . 'Lib' . DS . 'Services' . DS . 'OrderHistoryFilterService.php';
        require_once APP . 'Lib' . DS . 'Services' . DS . 'OrderService.php';

        $service = new ScheduledReportService();
        $orderService = new OrderService();

        $due = $service->getDueReports();
        $this->out('Found ' . count($due) . ' due report(s).');

        foreach ($due as $reportRow) {
            $report = $reportRow['ScheduledReport'];
            $this->out('Processing: ' . $report['name']);

            try {
                $filters   = !empty($report['filters_json']) ? json_decode($report['filters_json'], true) : array();
                $allOrders = $orderService->getAllOrders($filters);
                $totalCount = count($allOrders);

                // Compute total amount from actual DB fields: approved_qty * price
                $totalAmount = array_sum(array_map(function($o) {
                    $qty   = (float)(isset($o['approved_qty']) ? $o['approved_qty'] : (isset($o['quantity']) ? $o['quantity'] : 0));
                    $price = (float)(isset($o['price']) ? $o['price'] : 0);
                    return $qty * $price;
                }, $allOrders));

                $email = trim($report['email']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->_sendReportEmail($email, $report, $allOrders, $totalCount, $totalAmount);
                }

                $service->markSent($report['id']);
                $this->out('  ✓ Sent to ' . $email . '. Orders: ' . $totalCount);
            } catch (Exception $e) {
                $service->markFailed($report['id']);
                $this->out('  ✗ Error: ' . $e->getMessage());
            }
        }

        $this->out('[SendScheduledReports] Done.');
    }

    protected function _generateCsv($orders) {
        $tmp = tempnam(sys_get_temp_dir(), 'dc_report_');
        $fh  = fopen($tmp, 'w');
        fputcsv($fh, array('Order Number', 'Description', 'Supplier', 'Approved Qty', 'Price', 'DT Price', 'Subtotal', 'Status', 'Order Date'));
        foreach ($orders as $o) {
            $approvedQty = (float)(isset($o['approved_qty']) ? $o['approved_qty'] : (isset($o['quantity']) ? $o['quantity'] : 0));
            $price       = (float)(isset($o['price']) ? $o['price'] : 0);
            $dtPrice     = isset($o['dt_price']) ? $o['dt_price'] : '';
            $dateRaw     = isset($o['orderdate']) ? $o['orderdate'] : '';
            fputcsv($fh, array(
                isset($o['order_number']) ? $o['order_number'] : '',
                isset($o['product_description']) ? $o['product_description'] : '',
                isset($o['supplier_id']) ? $o['supplier_id'] : '',
                number_format($approvedQty, 2),
                number_format($price, 4),
                $dtPrice !== '' ? number_format((float)$dtPrice, 4) : '',
                number_format($approvedQty * $price, 2),
                isset($o['response']) ? $o['response'] : '',
                $dateRaw ? date('Y-m-d H:i:s', strtotime($dateRaw)) : '',
            ));
        }
        fclose($fh);
        return $tmp;
    }

    protected function _sendReportEmail($email, $report, $orders, $totalCount, $totalAmount) {
        $csvTmp = $this->_generateCsv($orders);
        $mailer = new CakeEmail('default');
        $mailer->template('scheduled_report', 'default')
               ->emailFormat('html')
               ->to($email)
               ->subject('📊 ' . $report['name'] . ' — ' . date('j M Y'))
               ->viewVars(array(
                   'report'         => $report,
                   'orders'         => $orders,
                   'totalCount'     => $totalCount,
                   'totalAmount'    => $totalAmount,
                   'recipientEmail' => $email,
               ))
               ->attachments(array(
                   'scheduled-report-orders.csv' => array(
                       'file'     => $csvTmp,
                       'mimetype' => 'text/csv',
                   ),
               ))
               ->send();
        @unlink($csvTmp);
    }
}

