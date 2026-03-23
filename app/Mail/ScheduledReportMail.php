<?php

namespace App\Mail;

use App\Models\ScheduledReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ScheduledReport $report,
        public readonly Collection      $orders,
        public readonly string          $csv,
    ) {}

    public function envelope(): Envelope
    {
        $subject = ($this->report->name ?: 'Order Report')
            .' — '.Carbon::now()->format('d M Y');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.scheduled-report');
    }

    public function attachments(): array
    {
        $filename = 'orders-'.Carbon::now()->format('Y-m-d').'.csv';

        return [
            Attachment::fromData(fn () => $this->csv, $filename)
                ->withMime('text/csv'),
        ];
    }
}
