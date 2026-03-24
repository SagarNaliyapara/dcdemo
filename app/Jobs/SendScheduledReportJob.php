<?php

namespace App\Jobs;

use App\Mail\ScheduledReportMail;
use App\Models\ScheduledReport;
use App\Services\OrderService;
use App\Services\ScheduledReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying a failed job.
     */
    public int $backoff = 60;

    /**
     * Seconds the job can run before timing out.
     */
    public int $timeout = 120;

    public function __construct(
        public readonly ScheduledReport $report,
    ) {}

    public function handle(OrderService $orderService, ScheduledReportService $reportService): void
    {
        $filters = $this->report->filters_json ?? [];

        // Include only new orders placed after the last run
        if ($this->report->last_run_at) {
            $filters['minOrderDate'] = $this->report->last_run_at->toDateTimeString();
        }

        $orders = $orderService->getAllOrders($filters);
        $csv = $this->generateCsv($orders);

        Mail::to($this->report->email)->send(new ScheduledReportMail($this->report, $orders, $csv));

        // Update timestamps after successful send
        $this->report->update([
            'last_run_at' => Carbon::now(),
            'next_run_at' => $reportService->calculateNextRun(
                $this->report->frequency,
                $this->report->send_time,
                (string) ($this->report->day_of_week ?? '1'),
                (string) ($this->report->day_of_month ?? '1'),
            ),
        ]);

        Log::info('[ScheduledReport] Sent report #'.$this->report->id.' "'.$this->report->name.'" to '.$this->report->email);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[ScheduledReport] Failed to send report #'.$this->report->id.': '.$e->getMessage());
    }

    private function generateCsv(Collection $orders): string
    {
        $headers = [
            'Order No', 'PIP Code', 'Description', 'Ordered Qty', 'Approved Qty',
            'Price (£)', 'DT Price (£)', 'Sub Total (£)', 'Category', 'Supplier',
            'Response', 'Notes', 'Date',
        ];

        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, $headers);

        foreach ($orders as $order) {
            fputcsv($buffer, [
                $order->order_number ?? $order->ordernumber ?? '',
                $order->pipcode ?? '',
                $order->product_description ?? '',
                $order->quantity ?? '',
                $order->approved_qty ?? '',
                $order->price !== null ? number_format((float) $order->price, 4) : '',
                $order->dt_price !== null ? number_format((float) $order->dt_price, 4) : '',
                number_format((float) $order->price * (float) $order->quantity, 2),
                $order->category ?? '',
                $order->supplier_id ?? '',
                $order->response ?? '',
                $order->notes ?? '',
                $order->orderdate?->format('d M Y H:i') ?? '',
            ]);
        }

        rewind($buffer);
        $csv = stream_get_contents($buffer);
        fclose($buffer);

        return $csv;
    }
}
