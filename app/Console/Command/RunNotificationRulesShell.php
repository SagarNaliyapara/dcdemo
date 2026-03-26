<?php
App::uses('AppShell', 'Console/Command');
App::uses('CakeEmail', 'Network/Email');

/**
 * Shell to process notification rules and send alerts when orders match.
 * Run: php app/Console/cake.php run_notification_rules
 * Add to cron every 30 minutes:
 *   */30 * * * * cd /path/to/dc-cakephp && php app/Console/cake.php run_notification_rules
 */
class RunNotificationRulesShell extends AppShell {

    public $uses = array('NotificationRule');

    public function main() {
        $this->out('[RunNotificationRules] Starting at ' . date('Y-m-d H:i:s'));

        App::import('Lib', 'Services/NotificationRuleService');
        App::import('Lib', 'Services/NotificationRulePreviewService');

        $service = new NotificationRuleService();
        $previewService = new NotificationRulePreviewService();

        $due = $service->getDueRules();
        $this->out('Found ' . count($due) . ' due rule(s).');

        foreach ($due as $ruleRow) {
            $rule = $ruleRow['NotificationRule'];
            $this->out('Processing rule: ' . $rule['name']);

            try {
                $service->markQueued($rule['id']);

                // Get matching orders
                $filters = !empty($rule['filters_json']) ? json_decode($rule['filters_json'], true) : array();
                $result = $previewService->preview($filters);
                $matchCount = $result['count'];
                $orders = $result['orders'];

                $this->out('  Matches: ' . $matchCount);

                if ($matchCount === 0) {
                    $service->markNoMatches($rule['id']);
                    $this->out('  ○ No matches — skipping email.');
                    continue;
                }

                // Send to each email
                $emails = array_filter(array_map('trim', explode("\n", $rule['emails'])));
                foreach ($emails as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
                    $this->_sendRuleEmail($email, $rule, $orders, $matchCount);
                }

                $service->markSuccess($rule['id']);
                $this->out('  ✓ Sent to ' . count($emails) . ' recipient(s).');
            } catch (Exception $e) {
                $service->markFailed($rule['id']);
                $this->out('  ✗ Error: ' . $e->getMessage());
            }
        }

        $this->out('[RunNotificationRules] Done.');
    }

    protected function _sendRuleEmail($email, $rule, $orders, $matchCount) {
        $mailer = new CakeEmail('default');
        $mailer->template('notification_rule', 'default')
               ->emailFormat('html')
               ->to($email)
               ->subject('🔔 ' . $rule['name'] . ' — ' . $matchCount . ' match' . ($matchCount !== 1 ? 'es' : ''))
               ->viewVars(array(
                   'rule'       => $rule,
                   'orders'     => $orders,
                   'matchCount' => $matchCount,
               ))
               ->send();
    }
}
