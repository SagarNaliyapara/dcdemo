@php
    $timeOptions = [];
    for ($hour = 0; $hour < 24; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 15) {
            $timeOptions[] = sprintf('%02d:%02d', $hour, $minute);
        }
    }

    $reports = $this->reports;
    $activeCount = $reports->where('is_active', true)->count();
    $inactiveCount = $reports->where('is_active', false)->count();
@endphp

<div
    x-data="{}"
    class="app-page"
>
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
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Reports</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Scheduled Reports</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Review saved report schedules, pause or reactivate delivery, and adjust recipients or send times without rebuilding the filter logic.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('orders.history') }}" wire:navigate class="app-button">Back To Order History</a>
            <a href="{{ route('orders.notification-rules') }}" wire:navigate class="app-button app-button-soft">Notification Rules</a>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Schedules</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $reports->count() }}</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Active</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $activeCount }}</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Inactive</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $inactiveCount }}</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Last Run Today</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $reports->filter(fn ($report) => $report->last_run_at?->isToday())->count() }}</p>
        </div>
    </div>

    <section class="app-card overflow-hidden">
        @if($reports->isEmpty())
            <div class="app-card-body py-20 text-center">
                <h2 class="text-lg font-semibold text-slate-950">No scheduled reports yet</h2>
                <p class="mt-2 text-sm text-slate-500">Apply grouped filters on Order History and create a schedule from that result set.</p>
                <a href="{{ route('orders.history') }}" wire:navigate class="app-button app-button-primary mt-6">Create First Schedule</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            @foreach(['Schedule', 'Recipient', 'Timing', 'Last Run', 'Status', 'Actions'] as $heading)
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($reports as $report)
                            <tr wire:key="scheduled-report-{{ $report->id }}">
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900">{{ $report->name ?: 'Untitled schedule' }}</div>
                                    <p class="mt-1 max-w-md text-xs leading-5 text-slate-500">
                                        {{ ucfirst($report->frequency) }}
                                        @if($report->frequency === 'weekly' && $report->day_of_week !== null)
                                            · {{ \Carbon\Carbon::now()->startOfWeek()->addDays($report->day_of_week)->format('l') }}
                                        @endif
                                        @if($report->frequency === 'monthly' && $report->day_of_month !== null)
                                            · Day {{ $report->day_of_month }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ $report->email }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div>{{ $report->send_time }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Next: {{ $report->next_run_at?->format('d M Y H:i') ?? 'TBD' }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    {{ $report->last_run_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        wire:click="toggleActive({{ $report->id }})"
                                        class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $report->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-700' }}"
                                    >
                                        {{ $report->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="startEdit({{ $report->id }})" class="app-button px-3 py-2 text-xs">Edit</button>
                                        <button type="button" wire:click="confirmDelete({{ $report->id }})" class="app-button app-button-danger px-3 py-2 text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div
        x-show="$wire.editingId !== null"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="$wire.cancelEdit()"></div>
        <div class="relative w-full max-w-xl rounded-[28px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.16)]">
            <div class="app-card-header">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-950">Edit Schedule</h2>
                        <p class="mt-1 text-sm text-slate-500">Adjust timing and recipient while keeping the saved filter scope intact.</p>
                    </div>
                    <button type="button" @click="$wire.cancelEdit()" class="app-button px-3 py-2 text-xs">Close</button>
                </div>
            </div>

            <div class="app-card-body space-y-4">
                <div>
                    <label class="app-label">Report Name</label>
                    <input type="text" wire:model="editForm.name" class="app-input" />
                    @error('editForm.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="app-label">Frequency</label>
                        <select wire:model.live="editForm.frequency" class="app-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('editForm.frequency') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="app-label">Send Time</label>
                        <select wire:model="editForm.sendTime" class="app-select">
                            @foreach($timeOptions as $timeOption)
                                <option value="{{ $timeOption }}">{{ $timeOption }}</option>
                            @endforeach
                        </select>
                        @error('editForm.sendTime') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if($editForm->frequency === 'weekly')
                    <div>
                        <label class="app-label">Weekly Day</label>
                        <select wire:model="editForm.dayOfWeek" class="app-select">
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="0">Sunday</option>
                        </select>
                        @error('editForm.dayOfWeek') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($editForm->frequency === 'monthly')
                    <div>
                        <label class="app-label">Monthly Day</label>
                        <select wire:model="editForm.dayOfMonth" class="app-select">
                            @for($day = 1; $day <= 31; $day++)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endfor
                        </select>
                        @error('editForm.dayOfMonth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="app-label">Recipient Email</label>
                    <input type="email" wire:model="editForm.email" class="app-input" />
                    @error('editForm.email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                <button type="button" @click="$wire.cancelEdit()" class="app-button">Cancel</button>
                <button type="button" wire:click="saveEdit" class="app-button app-button-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <div
        x-show="$wire.showDeleteConfirm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="$wire.cancelDelete()"></div>
        <div class="relative w-full max-w-md rounded-[28px] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.16)]">
            <div class="app-card-body">
                <h2 class="text-xl font-semibold text-slate-950">Delete Schedule</h2>
                <p class="mt-2 text-sm text-slate-500">This removes the saved report schedule. Filter scope cannot be recovered after deletion.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelDelete" class="app-button">Cancel</button>
                    <button type="button" wire:click="deleteReport" class="app-button app-button-danger">Delete Schedule</button>
                </div>
            </div>
        </div>
    </div>
</div>
