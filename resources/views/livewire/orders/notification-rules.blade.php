<div class="app-page">
    @if(session('action'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 3500)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
        >
            {{ session('action') }}
        </div>
    @endif

    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Notifications</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Email Notification Rules</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Manage saved rule logic, preview matching orders, run rules on demand, and control which rules stay active in the schedule.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.history') }}" wire:navigate class="app-button">
                Order History
            </a>
            <a href="{{ route('orders.notification-rules.create') }}" wire:navigate class="app-button app-button-primary">
                Create New Rule
            </a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Rules</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $this->summary['total'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Saved notification rule records</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Active Rules</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $this->summary['active'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Currently scheduled to run</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Inactive Rules</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $this->summary['inactive'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Paused without deleting logic</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Needs Attention</p>
            <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $this->summary['attention'] }}</p>
            <p class="mt-1 text-sm text-slate-500">Draft or failed rules</p>
        </div>
    </div>

    <section class="app-card">
        <div class="app-card-body space-y-4">
            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_180px_180px_180px]">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search by rule, recipient, or status..." class="app-input pl-11" />
                </div>

                <select wire:model.live="statusFilter" class="app-select">
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                    <option value="error">Error</option>
                </select>

                <select wire:model.live="frequencyFilter" class="app-select">
                    <option value="all">All frequencies</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>

                <div class="flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                    <button type="button" wire:click="$set('viewMode', 'table')" class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold {{ $viewMode === 'table' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Table</button>
                    <button type="button" wire:click="$set('viewMode', 'cards')" class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold {{ $viewMode === 'cards' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Cards</button>
                </div>
            </div>

            <p class="text-sm text-slate-500">Preview shows the first 20 matching rows. Run now queues the rule immediately through the scheduler pipeline.</p>
        </div>
    </section>

    @php
        $statusStyles = [
            'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'inactive' => 'border-slate-200 bg-slate-100 text-slate-700',
            'draft' => 'border-amber-200 bg-amber-50 text-amber-700',
            'error' => 'border-rose-200 bg-rose-50 text-rose-700',
        ];
    @endphp

    @if($viewMode === 'table')
        <section class="app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Rule</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Recipient</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Schedule</th>
                            <th class="w-36 px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Last Result</th>
                            <th class="w-24 px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($this->rules as $rule)
                            <tr wire:key="notification-rule-{{ $rule->id }}" class="bg-white align-top">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900">{{ $rule->name ?: 'Untitled rule' }}</div>
                                    <p class="mt-1 max-w-md text-xs leading-5 text-slate-500">
                                        {{ ucfirst(str_replace('_', ' ', $rule->date_scope_type)) }} · {{ ucfirst($rule->frequency) }} at {{ $rule->send_time }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $rule->recipient_email }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">
                                    <div>Next: {{ $rule->next_run_at?->format('d M Y H:i') ?? 'Not scheduled' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Last: {{ $rule->last_run_at?->diffForHumans() ?? 'Never' }}</div>
                                </td>
                                <td class="w-36 px-4 py-4">
                                    <span class="whitespace-nowrap rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $rule->last_result_count !== null ? number_format($rule->last_result_count).' matches' : 'No run yet' }}
                                    </span>
                                    @if($rule->last_error_message)
                                        <p class="mt-2 text-xs leading-4 text-rose-600 break-words" title="{{ $rule->last_error_message }}">
                                            {{ \Illuminate\Support\Str::limit($rule->last_error_message, 60) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $statusStyles[$rule->status] ?? $statusStyles['draft'] }}">
                                        {{ ucfirst($rule->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('orders.notification-rules.edit', $rule) }}" wire:navigate class="app-button px-3 py-2 text-xs">Edit</a>
                                        <button type="button" wire:click="previewRule({{ $rule->id }})" class="app-button app-button-soft px-3 py-2 text-xs">Preview</button>
                                        <button type="button" wire:click="runNow({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Run</button>
                                        @if($rule->status === 'active')
                                            <button type="button" wire:click="deactivate({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Deactivate</button>
                                        @else
                                            <button type="button" wire:click="activate({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Activate</button>
                                        @endif
                                        <button type="button" wire:click="duplicateRule({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Duplicate</button>
                                        <button type="button" wire:click="confirmDelete({{ $rule->id }})" class="app-button app-button-danger px-3 py-2 text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-20 text-center text-sm text-slate-500">No rules found for the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            @forelse($this->rules as $rule)
                <section wire:key="notification-rule-card-{{ $rule->id }}" class="app-card">
                    <div class="app-card-body">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950">{{ $rule->name ?: 'Untitled rule' }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $rule->recipient_email }}</p>
                            </div>
                            <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $statusStyles[$rule->status] ?? $statusStyles['draft'] }}">
                                {{ ucfirst($rule->status) }}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Schedule</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ ucfirst($rule->frequency) }} at {{ $rule->send_time }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $rule->date_scope_type)) }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Result</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $rule->last_result_count !== null ? number_format($rule->last_result_count).' matches' : 'No run yet' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Next: {{ $rule->next_run_at?->format('d M Y H:i') ?? 'Not scheduled' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('orders.notification-rules.edit', $rule) }}" wire:navigate class="app-button px-3 py-2 text-xs">Edit</a>
                            <button type="button" wire:click="previewRule({{ $rule->id }})" class="app-button app-button-soft px-3 py-2 text-xs">Preview</button>
                            <button type="button" wire:click="runNow({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Run</button>
                            @if($rule->status === 'active')
                                <button type="button" wire:click="deactivate({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Deactivate</button>
                            @else
                                <button type="button" wire:click="activate({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Activate</button>
                            @endif
                            <button type="button" wire:click="duplicateRule({{ $rule->id }})" class="app-button px-3 py-2 text-xs">Duplicate</button>
                            <button type="button" wire:click="confirmDelete({{ $rule->id }})" class="app-button app-button-danger px-3 py-2 text-xs">Delete</button>
                        </div>
                    </div>
                </section>
            @empty
                <section class="app-card">
                    <div class="app-card-body py-16 text-center text-sm text-slate-500">No rules found for the current filters.</div>
                </section>
            @endforelse
        </div>
    @endif

    @if($this->rules->hasPages())
        <div class="flex justify-end">
            {{ $this->rules->links() }}
        </div>
    @endif

    @if($this->previewingRule && $this->previewPayload)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="closePreview"></div>
            <div class="relative max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-[28px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.16)]">
                <div class="app-card-header">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-950">Preview: {{ $this->previewingRule->name ?: 'Untitled rule' }}</h2>
                            <p class="mt-1 text-sm text-slate-500">First 20 matching rows based on current saved rule settings.</p>
                        </div>
                        <button type="button" wire:click="closePreview" class="app-button px-3 py-2 text-xs">Close</button>
                    </div>
                </div>
                <div class="app-card-body space-y-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="app-stat-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Matches</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($this->previewPayload['match_count']) }}</p>
                        </div>
                        <div class="app-stat-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Preview Rows</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($this->previewPayload['preview_count']) }}</p>
                        </div>
                        <div class="app-stat-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Email Cap</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ number_format($this->previewingRule->email_row_limit) }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[22px] border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach(['Date', 'Order', 'Description', 'Supplier', 'Approved Qty', 'Subtotal'] as $heading)
                                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($this->previewPayload['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600">{{ $row->orderdate?->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row->order_number }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row->product_description }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row->supplier_id }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ number_format((float) $row->approved_qty, 2) }}</td>
                                        <td class="px-4 py-3 text-slate-600">£{{ number_format((float) $row->sub_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-500">No rows match this rule right now.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($deletingRuleId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="cancelDelete"></div>
            <div class="relative w-full max-w-md rounded-[28px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.16)]">
                <div class="app-card-body">
                    <h2 class="text-xl font-semibold text-slate-950">Delete Rule</h2>
                    <p class="mt-2 text-sm text-slate-500">This removes the saved rule and its schedule. This action cannot be undone.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" wire:click="cancelDelete" class="app-button">Cancel</button>
                        <button type="button" wire:click="deleteRule" class="app-button app-button-danger">Delete Rule</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
