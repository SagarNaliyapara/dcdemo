@php
    $timeOptions = [];
    for ($hour = 0; $hour < 24; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 15) {
            $timeOptions[] = sprintf('%02d:%02d', $hour, $minute);
        }
    }
@endphp

<div class="app-page">
    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Rule Builder</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
                {{ $ruleId !== null ? 'Edit Notification Rule' : 'Create Notification Rule' }}
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Configure schedule, grouped filters, recipients, and a live preview before you save. Preview always shows the first 20 matching rows.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.notification-rules') }}" wire:navigate class="app-button">Back To Rules</a>
            <button type="button" wire:click="refreshPreview" class="app-button app-button-soft">Refresh Preview</button>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(360px,0.8fr)]">
        <div class="space-y-6">
            <section class="app-card">
                <div class="app-card-header">
                    <h2 class="text-lg font-semibold text-slate-900">1. Rule Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Set the rule name, status, and notification channel.</p>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Rule Name</label>
                            <input type="text" wire:model.live.debounce.400ms="form.name" class="app-input" placeholder="Daily high value supplier orders" />
                            @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="app-label">Channel</label>
                            <select wire:model.live="form.channel" class="app-select">
                                <option value="email">Email</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="app-label">Status</label>
                        <div class="inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                            <button type="button" wire:click="$set('form.status', 'active')" class="rounded-xl px-4 py-2 text-sm font-semibold {{ $form->status === 'active' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Active</button>
                            <button type="button" wire:click="$set('form.status', 'inactive')" class="rounded-xl px-4 py-2 text-sm font-semibold {{ $form->status === 'inactive' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Inactive</button>
                            <button type="button" wire:click="$set('form.status', 'draft')" class="rounded-xl px-4 py-2 text-sm font-semibold {{ $form->status === 'draft' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">Draft</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="app-card">
                <div class="app-card-header">
                    <h2 class="text-lg font-semibold text-slate-900">2. Data Scope</h2>
                    <p class="mt-1 text-sm text-slate-500">Pick the rolling order history window that each run should evaluate.</p>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="app-label">Data Source</label>
                            <select wire:model.live="form.dataSource" class="app-select">
                                <option value="orders">Orders</option>
                            </select>
                        </div>
                        <div>
                            <label class="app-label">Date Range</label>
                            <select wire:model.live="form.dateScopeType" class="app-select">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="last_7_days">Last 7 days</option>
                                <option value="last_30_days">Last 30 days</option>
                                <option value="this_week">This week</option>
                                <option value="this_month">This month</option>
                                <option value="custom_rolling">Custom rolling range</option>
                            </select>
                        </div>
                        <div>
                            <label class="app-label">Match Mode</label>
                            <select wire:model.live="form.matchType" class="app-select">
                                <option value="all">Match all groups</option>
                                <option value="any">Match any group</option>
                            </select>
                        </div>
                    </div>

                    @if($form->dateScopeType === 'custom_rolling')
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="app-label">Rolling Value</label>
                                <input type="number" min="1" max="365" wire:model.live="form.dateScopeValue" class="app-input" />
                                @error('form.dateScopeValue') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="app-label">Rolling Unit</label>
                                <select wire:model.live="form.dateScopeUnit" class="app-select">
                                    <option value="day">Days</option>
                                    <option value="week">Weeks</option>
                                    <option value="month">Months</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Only rows with approved quantity above zero are included in the preview, email body, and CSV attachment.
                    </div>
                </div>
            </section>

            <x-orders.filter-builder
                :groups="$form->filters['groups']"
                :field-definitions="$filterFieldDefinitions"
                match-type-binding="form.matchType"
                groups-binding="form.filters.groups"
                add-group-method="addFilterGroup"
                remove-group-method="removeFilterGroup"
                add-filter-method="addFilter"
                remove-filter-method="removeFilter"
                title="3. Filters"
                caption="Create grouped conditions with plus and minus actions. Use group logic to build more precise rule matching."
            />

            <section class="app-card">
                <div class="app-card-header">
                    <h2 class="text-lg font-semibold text-slate-900">4. Schedule</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose when this notification should run.</p>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="app-label">Frequency</label>
                            <select wire:model.live="form.frequency" class="app-select">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            @error('form.frequency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="app-label">Time</label>
                            <select wire:model.live="form.sendTime" class="app-select">
                                @foreach($timeOptions as $timeOption)
                                    <option value="{{ $timeOption }}">{{ $timeOption }}</option>
                                @endforeach
                            </select>
                            @error('form.sendTime') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="app-label">Time Zone</label>
                            <input type="text" value="Application time" class="app-input" readonly />
                        </div>
                    </div>

                    @if($form->frequency === 'weekly')
                        <div>
                            <label class="app-label">Weekly Day</label>
                            <select wire:model.live="form.dayOfWeek" class="app-select">
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                                <option value="0">Sunday</option>
                            </select>
                            @error('form.dayOfWeek') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($form->frequency === 'monthly')
                        <div>
                            <label class="app-label">Monthly Day</label>
                            <select wire:model.live="form.dayOfMonth" class="app-select">
                                @for($day = 1; $day <= 31; $day++)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endfor
                            </select>
                            @error('form.dayOfMonth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            </section>

            <section class="app-card">
                <div class="app-card-header">
                    <h2 class="text-lg font-semibold text-slate-900">5. Email Output</h2>
                    <p class="mt-1 text-sm text-slate-500">Set the recipient and how many rows appear in the email body.</p>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Recipient Email</label>
                            <input type="email" wire:model.live.debounce.400ms="form.recipientEmail" class="app-input" />
                            @error('form.recipientEmail') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="app-label">Email Row Limit</label>
                            <input type="number" min="1" max="1000" wire:model.live="form.emailRowLimit" class="app-input" />
                            @error('form.emailRowLimit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        If more rows match than the email limit, the message shows the first rows and the full set is attached as CSV.
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('orders.notification-rules') }}" wire:navigate class="app-button">Cancel</a>
                <button type="button" wire:click="save" class="app-button app-button-primary">
                    {{ $ruleId !== null ? 'Save Changes' : 'Save Rule' }}
                </button>
            </div>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-4">
            <section class="app-card">
                <div class="app-card-header">
                    <h2 class="text-lg font-semibold text-slate-900">6. Preview</h2>
                    <p class="mt-1 text-sm text-slate-500">Live preview of the first 20 matching rows.</p>
                </div>
                <div class="app-card-body space-y-5">
                    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-1">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Matching Rows</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($preview['match_count']) }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Preview Rows</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($preview['preview_count']) }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Email Cap</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ number_format($form->emailRowLimit) }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Subject preview: <span class="font-semibold text-slate-900">{{ $form->name !== '' ? $form->name : 'Order notification' }}</span>
                    </div>

                    <div class="overflow-hidden rounded-[22px] border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach(['Date', 'Order', 'Supplier', 'Subtotal'] as $heading)
                                        <th class="px-3 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse($preview['rows'] as $row)
                                    <tr>
                                        <td class="px-3 py-3 text-slate-600">{{ $row->orderdate?->format('d M Y') }}</td>
                                        <td class="px-3 py-3 font-medium text-slate-900">{{ $row->order_number }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $row->supplier_id }}</td>
                                        <td class="px-3 py-3 text-slate-600">£{{ number_format((float) $row->sub_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-10 text-center text-sm text-slate-500">No rows match the current rule settings.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
