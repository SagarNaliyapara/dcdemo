<?php

namespace App\Livewire\Pages\Orders;

use App\Jobs\SendNotificationRuleJob;
use App\Models\NotificationRule;
use App\Services\NotificationRulePreviewService;
use App\Services\NotificationRuleService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notification Rules')]
class NotificationRules extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $frequencyFilter = 'all';

    public string $viewMode = 'table';

    public ?int $previewingRuleId = null;

    public ?int $deletingRuleId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFrequencyFilter(): void
    {
        $this->resetPage();
    }

    public function previewRule(int $ruleId): void
    {
        $this->previewingRuleId = $this->findOwnedRule($ruleId)?->id;
    }

    public function closePreview(): void
    {
        $this->previewingRuleId = null;
    }

    public function activate(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);

        if ($rule === null) {
            return;
        }

        app(NotificationRuleService::class)->activate($rule);
        session()->flash('action', 'Rule activated.');
    }

    public function deactivate(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);

        if ($rule === null) {
            return;
        }

        app(NotificationRuleService::class)->deactivate($rule);
        session()->flash('action', 'Rule deactivated.');
    }

    public function duplicateRule(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);

        if ($rule === null) {
            return;
        }

        app(NotificationRuleService::class)->duplicate($rule);
        session()->flash('action', 'Rule duplicated.');
    }

    public function runNow(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);

        if ($rule === null) {
            return;
        }

        $service = app(NotificationRuleService::class);

        if ($service->isQueued($rule)) {
            session()->flash('action', 'Rule is already queued.');

            return;
        }

        $service->markQueued($rule);
        SendNotificationRuleJob::dispatch($rule->fresh());

        session()->flash('action', 'Rule queued to run now.');
    }

    public function confirmDelete(int $ruleId): void
    {
        $this->deletingRuleId = $this->findOwnedRule($ruleId)?->id;
    }

    public function deleteRule(): void
    {
        if ($this->deletingRuleId === null) {
            return;
        }

        $rule = $this->findOwnedRule($this->deletingRuleId);

        if ($rule !== null) {
            app(NotificationRuleService::class)->delete($rule);
            session()->flash('action', 'Rule deleted.');
        }

        $this->deletingRuleId = null;
    }

    public function cancelDelete(): void
    {
        $this->deletingRuleId = null;
    }

    #[Computed]
    public function rules()
    {
        return app(NotificationRuleService::class)->listForUser(
            userId: auth()->id(),
            filters: [
                'search' => $this->search,
                'status' => $this->statusFilter,
                'frequency' => $this->frequencyFilter,
            ],
        );
    }

    #[Computed]
    public function summary(): array
    {
        $rules = NotificationRule::query()
            ->where('user_id', auth()->id())
            ->get();

        return [
            'total' => $rules->count(),
            'active' => $rules->where('status', 'active')->count(),
            'inactive' => $rules->where('status', 'inactive')->count(),
            'attention' => $rules->whereIn('status', ['draft', 'error'])->count(),
        ];
    }

    #[Computed]
    public function previewPayload(): ?array
    {
        if ($this->previewingRuleId === null) {
            return null;
        }

        $rule = $this->findOwnedRule($this->previewingRuleId);

        if ($rule === null) {
            return null;
        }

        return app(NotificationRulePreviewService::class)->previewFromRule($rule);
    }

    #[Computed]
    public function previewingRule(): ?NotificationRule
    {
        if ($this->previewingRuleId === null) {
            return null;
        }

        return $this->findOwnedRule($this->previewingRuleId);
    }

    private function findOwnedRule(int $ruleId): ?NotificationRule
    {
        return NotificationRule::query()
            ->where('id', $ruleId)
            ->where('user_id', auth()->id())
            ->first();
    }

    public function render()
    {
        return view('livewire.orders.notification-rules')
            ->layout('layouts.app', ['title' => 'Notification Rules']);
    }
}
