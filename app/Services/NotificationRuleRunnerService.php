<?php

namespace App\Services;

use App\Mail\NotificationRuleMail;
use App\Models\NotificationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationRuleRunnerService
{
    public function __construct(
        private readonly NotificationRulePreviewService $previewService,
        private readonly NotificationRuleService $notificationRuleService,
    ) {}

    public function run(NotificationRule $rule): int
    {
        $matchedOrders = $this->previewService->matchedOrders($rule);
        $matchCount = $matchedOrders->count();

        if ($matchCount === 0) {
            $this->notificationRuleService->markSuccessfulRun($rule, 0);

            return 0;
        }

        $emailRows = $matchedOrders->take($rule->email_row_limit);
        $csv = $this->generateCsv($matchedOrders);

        Mail::to($rule->recipient_email)->send(
            new NotificationRuleMail($rule, $emailRows, $matchCount, $csv),
        );

        $this->notificationRuleService->markSuccessfulRun($rule, $matchCount);

        return $matchCount;
    }

    private function generateCsv(Collection $orders): string
    {
        $buffer = fopen('php://temp', 'r+');

        fputcsv($buffer, [
            'Order Number',
            'Description',
            'Supplier',
            'Category',
            'Approved Qty',
            'Price',
            'DT Price',
            'Subtotal',
            'Response',
            'Order Date',
        ]);

        foreach ($orders as $order) {
            fputcsv($buffer, [
                $order->order_number,
                $order->product_description,
                $order->supplier_id,
                $order->category,
                $order->approved_qty,
                $order->price,
                $order->dt_price,
                $order->sub_total,
                $order->response,
                $order->orderdate?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($buffer);
        $csv = stream_get_contents($buffer) ?: '';
        fclose($buffer);

        return $csv;
    }
}
