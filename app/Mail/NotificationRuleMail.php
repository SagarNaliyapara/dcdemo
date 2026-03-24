<?php

namespace App\Mail;

use App\Models\NotificationRule;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationRuleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly NotificationRule $rule,
        public readonly Collection $orders,
        public readonly int $totalMatches,
        public readonly string $csv,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->rule->name ?: 'Order notification').' - '.now()->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.notification-rule',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->csv, 'notification-rule-orders.csv')
                ->withMime('text/csv'),
        ];
    }
}
