<?php
App::uses('AppShell', 'Console/Command');
App::uses('CakeEmail', 'Network/Email');

/**
 * Shell to process notification rules and send alerts when orders match.
 * Run: php app/Console/cake.php run_notification_rules
 * Add to cron every 30 minutes:
 *   30 * * * * cd /path/to/dc-cakephp && php app/Console/cake.php run_notification_rules
 */
class RunNotificationRulesShell extends AppShell {

    public $uses = array('NotificationRule');

    public function main() {
        $this->out('[RunNotificationRules] Starting at ' . date('Y-m-d H:i:s'));

        require_once APP . 'Lib' . DS . 'Services' . DS . 'NotificationRuleService.php';
        require_once APP . 'Lib' . DS . 'Services' . DS . 'OrderHistoryFilterService.php';
        require_once APP . 'Lib' . DS . 'Services' . DS . 'NotificationRulePreviewService.php';

        $service = new NotificationRuleService();
        $previewService = new NotificationRulePreviewService();

        $due = $service->dueRules();
        $this->out('Found ' . count($due) . ' due rule(s).');

        foreach ($due as $ruleRow) {
            $rule = $ruleRow['NotificationRule'];
            $this->out('Processing rule: ' . $rule['name']);

            try {
                $service->markQueued($rule['id']);

                $orders     = $previewService->matchedOrders($rule, $rule['email_row_limit'] ?: 300);
                $matchCount = count($orders);
                $this->out('  Matches: ' . $matchCount);

                if ($matchCount === 0) {
                    $service->markSuccessfulRun($rule['id'], 0);
                    $this->out('  ○ No matches — skipping email.');
                    continue;
                }

                $recipient = trim($rule['recipient_email']);
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $this->_sendRuleEmail($recipient, $rule, $orders, $matchCount);
                }

                $service->markSuccessfulRun($rule['id'], $matchCount);
                $this->out('  ✓ Sent to ' . $recipient . '.');
            } catch (Exception $e) {
                $service->markFailed($rule['id'], $e->getMessage());
                $this->out('  ✗ Error: ' . $e->getMessage());
            }
        }

        $this->out('[RunNotificationRules] Done.');
    }

    protected function _generateCsv($orders) {
        $tmp = tempnam(sys_get_temp_dir(), 'dc_notif_');
        $fh  = fopen($tmp, 'w');
        fputcsv($fh, array('Order Number', 'Description', 'Supplier', 'Approved Qty', 'Price', 'DT Price', 'Subtotal', 'Response', 'Order Date'));
        foreach ($orders as $o) {
            $approvedQty = (float)(isset($o['approved_qty']) ? $o['approved_qty'] : (isset($o['quantity']) ? $o['quantity'] : 0));
            $price       = (float)(isset($o['price']) ? $o['price'] : 0);
            $dtPrice     = isset($o['dt_price']) ? $o['dt_price'] : '';
            $dateRaw     = isset($o['orderdate']) ? $o['orderdate'] : (isset($o['order_date']) ? $o['order_date'] : '');
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

    protected function _sendRuleEmail($email, $rule, $orders, $matchCount) {
        $csvTmp = $this->_generateCsv($orders);
        $mailer = new CakeEmail('default');
        $mailer->template('notification_rule', 'default')
               ->emailFormat('html')
               ->to($email)
               ->subject('🔔 ' . $rule['name'] . ' — ' . $matchCount . ' match' . ($matchCount !== 1 ? 'es' : ''))
               ->viewVars(array(
                   'rule'           => $rule,
                   'orders'         => $orders,
                   'matchCount'     => $matchCount,
                   'recipientEmail' => $email,
               ))
               ->attachments(array(
                   'notification-rule-orders.csv' => array(
                       'file'     => $csvTmp,
                       'mimetype' => 'text/csv',
                   ),
               ))
               ->send();
        @unlink($csvTmp);
    }
}
