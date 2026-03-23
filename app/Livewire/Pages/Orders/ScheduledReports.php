<?php

namespace App\Livewire\Pages\Orders;

use App\Livewire\Forms\EditScheduledReportForm;
use App\Models\ScheduledReport;
use App\Services\ScheduledReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Scheduled Reports')]
class ScheduledReports extends Component
{
    public EditScheduledReportForm $editForm;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public bool $showDeleteConfirm = false;

    public function startEdit(int $id): void
    {
        $report = ScheduledReport::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $report) {
            return;
        }

        $this->editingId = $id;
        $this->editForm->populateFrom($report);
    }

    public function saveEdit(): void
    {
        $this->editForm->validate();

        app(ScheduledReportService::class)->updateReport(
            reportId: $this->editingId,
            userId:   auth()->id(),
            form:     $this->editForm,
        );

        $this->editingId = null;
        $this->dispatch('schedule-edit-saved');
        session()->flash('action', 'Schedule updated successfully.');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editForm->reset();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId      = $id;
        $this->showDeleteConfirm = true;
    }

    public function deleteReport(): void
    {
        if ($this->deletingId) {
            app(ScheduledReportService::class)->delete($this->deletingId, auth()->id());
            session()->flash('action', 'Scheduled report deleted.');
        }

        $this->showDeleteConfirm = false;
        $this->deletingId        = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletingId        = null;
    }

    public function toggleActive(int $id): void
    {
        $report = ScheduledReport::query()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if ($report) {
            $report->update(['is_active' => ! $report->is_active]);
        }
    }

    #[Computed]
    public function reports(): \Illuminate\Support\Collection
    {
        return app(ScheduledReportService::class)->getForUser(auth()->id());
    }

    #[Computed]
    public function editingReport(): ?ScheduledReport
    {
        if (! $this->editingId) {
            return null;
        }

        return ScheduledReport::find($this->editingId);
    }

    public function render()
    {
        return view('livewire.orders.scheduled-reports')
            ->layout('layouts.app', ['title' => 'Scheduled Reports']);
    }
}
