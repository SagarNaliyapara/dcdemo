<?php

namespace App\Livewire\Forms;

use App\Models\ScheduledReport;
use Livewire\Form;

class EditScheduledReportForm extends Form
{
    public string $name = '';

    public string $frequency = 'daily';

    public string $sendTime = '08:00';

    public string $dayOfWeek = '1';

    public string $dayOfMonth = '1';

    public string $email = '';

    public function rules(): array
    {
        return [
            'name'        => ['nullable', 'string', 'max:255'],
            'frequency'   => ['required', 'in:daily,weekly,monthly'],
            'sendTime'    => ['required', 'date_format:H:i'],
            'dayOfWeek'   => ['required_if:frequency,weekly', 'nullable', 'integer', 'between:0,6'],
            'dayOfMonth'  => ['required_if:frequency,monthly', 'nullable', 'integer', 'between:1,31'],
            'email'       => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'sendTime.date_format'     => 'Please select a valid time.',
            'dayOfWeek.required_if'    => 'Please select a day of the week.',
            'dayOfMonth.required_if'   => 'Please select a day of the month.',
            'email.required'           => 'An email address is required.',
            'email.email'              => 'Please enter a valid email address.',
        ];
    }

    public function populateFrom(ScheduledReport $report): void
    {
        $this->name       = $report->name ?? '';
        $this->frequency  = $report->frequency;
        $this->sendTime   = $report->send_time;
        $this->dayOfWeek  = (string) ($report->day_of_week ?? '1');
        $this->dayOfMonth = (string) ($report->day_of_month ?? '1');
        $this->email      = $report->email;
    }
}
