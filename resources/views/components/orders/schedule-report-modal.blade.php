@props(['scheduleForm', 'dateFilter', 'search' => '', 'hasActiveFilters' => false])

@php
    $dateLabels = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        'last3days' => 'Last 3 Days',
        'last7days' => 'Last 7 Days',
        'thismonth' => 'This Month',
        'lastmonth' => 'Last Month',
        'all'       => 'All Time',
    ];

    // Generate 15-minute interval time options
    $timeOptions = [];
    for ($h = 0; $h < 24; $h++) {
        for ($m = 0; $m < 60; $m += 15) {
            $timeOptions[] = sprintf('%02d:%02d', $h, $m);
        }
    }

    $inp = 'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';
    $lbl = 'mb-1.5 block text-xs font-semibold text-zinc-600 dark:text-zinc-400';
    $err = 'mt-1 text-xs text-red-500';
@endphp

<div
    x-show="showScheduleModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showScheduleModal = false"></div>

    <div
        class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        @click.stop
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-white">Schedule Report</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Auto-deliver reports based on current filters</p>
                </div>
            </div>
            <button @click="showScheduleModal = false" class="rounded-xl p-2 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Filter scope banner --}}
        <div class="border-b border-zinc-100 bg-zinc-50 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                <span class="font-semibold text-zinc-700 dark:text-zinc-200">Report scope:</span>
                {{ $dateLabels[$dateFilter] ?? 'All Time' }}
                @if($search) · Search: "<em>{{ $search }}</em>" @endif
                @if($hasActiveFilters) · + Advanced Filters @endif
            </p>
            <p class="mt-1 text-[11px] text-zinc-400 dark:text-zinc-500">
                Each delivery will include only <strong>new orders</strong> placed since the previous run.
            </p>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            <div class="space-y-5">

                {{-- Report name --}}
                <div>
                    <label class="{{ $lbl }}">Report Name <span class="font-normal text-zinc-400">(optional)</span></label>
                    <input type="text" wire:model="scheduleForm.name" placeholder="e.g. Weekly AAH Report" class="{{ $inp }}"/>
                    @error('scheduleForm.name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                {{-- Frequency --}}
                <div>
                    <label class="{{ $lbl }}">Frequency <span class="text-red-500">*</span></label>
                    <select wire:model.live="scheduleForm.frequency" class="{{ $inp }}">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    @error('scheduleForm.frequency') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                {{-- Weekly: Day of week --}}
                @if($scheduleForm->frequency === 'weekly')
                    <div>
                        <label class="{{ $lbl }}">Day of the Week <span class="text-red-500">*</span></label>
                        <select wire:model="scheduleForm.dayOfWeek" class="{{ $inp }}">
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="0">Sunday</option>
                        </select>
                        @error('scheduleForm.dayOfWeek') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Monthly: Day of month --}}
                @if($scheduleForm->frequency === 'monthly')
                    <div>
                        <label class="{{ $lbl }}">Day of the Month <span class="text-red-500">*</span></label>
                        <select wire:model="scheduleForm.dayOfMonth" class="{{ $inp }}">
                            @for($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}">{{ $d }}{{ match(true) { $d===1||$d===21||$d===31=>'st',$d===2||$d===22=>'nd',$d===3||$d===23=>'rd',default=>'th' } }}</option>
                            @endfor
                        </select>
                        @error('scheduleForm.dayOfMonth') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">For shorter months the last day is used automatically.</p>
                    </div>
                @endif

                {{-- Send Time (15-min intervals) --}}
                <div>
                    <label class="{{ $lbl }}">Send Time <span class="text-red-500">*</span></label>
                    <select wire:model="scheduleForm.sendTime" class="{{ $inp }}">
                        @foreach($timeOptions as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('scheduleForm.sendTime') <p class="{{ $err }}">{{ $message }}</p> @enderror
                </div>

                <hr class="border-zinc-100 dark:border-zinc-800"/>

                {{-- Email --}}
                <div>
                    <label class="{{ $lbl }}">Deliver To <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                            <svg class="h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" wire:model="scheduleForm.email" placeholder="you@example.com"
                            class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-9 pr-4 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"/>
                    </div>
                    @error('scheduleForm.email') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Pre-filled with your account email. You may change it.</p>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <button @click="showScheduleModal = false"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                Cancel
            </button>
            <button wire:click="saveScheduledReport"
                wire:loading.attr="disabled" wire:target="saveScheduledReport"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:opacity-60">
                <svg wire:loading wire:target="saveScheduledReport" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <svg wire:loading.remove wire:target="saveScheduledReport" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span wire:loading.remove wire:target="saveScheduledReport">Create Schedule</span>
                <span wire:loading wire:target="saveScheduledReport">Saving…</span>
            </button>
        </div>
    </div>
</div>
