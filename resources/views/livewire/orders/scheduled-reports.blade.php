@php
    $timeOptions = [];
    for ($h = 0; $h < 24; $h++) {
        for ($m = 0; $m < 60; $m += 15) {
            $timeOptions[] = sprintf('%02d:%02d', $h, $m);
        }
    }
    $inp = 'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500';
    $lbl = 'mb-1.5 block text-xs font-semibold text-zinc-600 dark:text-zinc-400';
    $err = 'mt-1 text-xs text-red-500';

    $freqBadge = fn(string $f) => match($f) {
        'daily'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'weekly'  => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
        'monthly' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
        default   => 'bg-zinc-100 text-zinc-600',
    };
@endphp

<div
    x-data="{ showEditModal: false, showDeleteModal: false }"
    class="relative flex flex-col gap-5 p-4 lg:p-6"
>

    {{-- Flash --}}
    @if(session('action'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,3500)"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('action') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">Scheduled Reports</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage your automated order history email reports.</p>
        </div>
        <a href="{{ route('orders.history') }}" wire:navigate
            class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Order History
        </a>
    </div>

    {{-- Table card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        @if($this->reports->isEmpty())
            <div class="flex flex-col items-center gap-3 py-20 text-zinc-400 dark:text-zinc-500">
                <svg class="h-12 w-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm font-medium">No scheduled reports yet</p>
                <p class="text-xs">Apply filters on the Order History page and click "Schedule Report".</p>
                <a href="{{ route('orders.history') }}" wire:navigate class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create First Schedule
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            @foreach(['Schedule Name','Frequency','Send Time','Email','Created','Status','Actions'] as $col)
                                <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->reports as $report)
                            <tr wire:key="report-{{ $report->id }}" class="{{ $loop->even ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900' }}">
                                {{-- Name --}}
                                <td class="px-4 py-3">
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $report->name ?: '—' }}</p>
                                    <p class="mt-0.5 max-w-[260px] truncate text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ collect($report->filters_json)->filter()->implode(fn($v,$k)=>is_array($v)?implode(', ',$v):$v, ' · ') ?: 'All orders' }}
                                    </p>
                                </td>
                                {{-- Frequency --}}
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $freqBadge($report->frequency) }}">
                                        {{ ucfirst($report->frequency) }}
                                    </span>
                                    @if($report->frequency === 'weekly' && $report->day_of_week !== null)
                                        <p class="mt-0.5 text-xs text-zinc-400">{{ \Carbon\Carbon::now()->startOfWeek()->addDays($report->day_of_week)->format('l') }}</p>
                                    @elseif($report->frequency === 'monthly' && $report->day_of_month)
                                        <p class="mt-0.5 text-xs text-zinc-400">Day {{ $report->day_of_month }}</p>
                                    @endif
                                </td>
                                {{-- Send Time --}}
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $report->send_time }}</td>
                                {{-- Email --}}
                                <td class="max-w-[200px] truncate px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">{{ $report->email }}</td>
                                {{-- Created --}}
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $report->created_at->format('d M Y') }}
                                    @if($report->last_run_at)
                                        <p class="text-[11px] text-zinc-400">Last: {{ $report->last_run_at->diffForHumans() }}</p>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    <button wire:click="toggleActive({{ $report->id }})" class="group flex items-center gap-1.5">
                                        @if($report->is_active)
                                            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Active</span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                                            <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">Paused</span>
                                        @endif
                                    </button>
                                    @if($report->next_run_at)
                                        <p class="mt-0.5 text-[11px] text-zinc-400">Next: {{ $report->next_run_at->diffForHumans() }}</p>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="startEdit({{ $report->id }})"
                                            @click="showEditModal = true"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414a2 2 0 01.586-1.414z"/></svg>
                                            Edit
                                        </button>
                                        <button
                                            wire:click="confirmDelete({{ $report->id }})"
                                            @click="showDeleteModal = true"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100 dark:border-red-800/50 dark:bg-red-900/30 dark:text-red-400">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ─── Edit Modal ──────────────────────────────────────────────────── --}}
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditModal=false; $wire.cancelEdit()"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900"
            x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150 transform" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-white">Edit Schedule</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Filters cannot be changed — create a new schedule instead.</p>
                </div>
                <button @click="showEditModal=false; $wire.cancelEdit()" class="rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Filters reference (read-only) --}}
            @if($this->editingReport)
                <div class="border-b border-zinc-100 bg-zinc-50 px-6 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                    <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Saved Filters (read-only)</p>
                    <div class="mt-1.5 flex flex-wrap gap-1">
                        @php
                            $f = $this->editingReport->filters_json;
                            $dl=['today'=>'Today','yesterday'=>'Yesterday','last3days'=>'Last 3 Days','last7days'=>'Last 7 Days','thismonth'=>'This Month','lastmonth'=>'Last Month'];
                        @endphp
                        @if(($f['dateFilter']??'all')!=='all')
                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700">{{ $dl[$f['dateFilter']]??$f['dateFilter'] }}</span>
                        @endif
                        @if(!empty($f['search']))
                            <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700">Search: {{ $f['search'] }}</span>
                        @endif
                        @foreach($f['supplierFilter']??[] as $s)
                            <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700">{{ $s }}</span>
                        @endforeach
                        @foreach($f['categoryFilter']??[] as $c)
                            <span class="inline-flex rounded-full bg-teal-100 px-2 py-0.5 text-[11px] font-medium text-teal-700">{{ $c }}</span>
                        @endforeach
                        @foreach($f['stockFilter']??[] as $st)
                            <span class="inline-flex rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-medium text-orange-700">{{ $st }}</span>
                        @endforeach
                        @if(empty(array_filter([$f['dateFilter']??null!=='all'?'x':null,$f['search']??null,...($f['supplierFilter']??[]),...($f['categoryFilter']??[]),...($f['stockFilter']??[])])))
                            <span class="text-xs text-zinc-400">All orders (no filters)</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="space-y-4">
                    <div>
                        <label class="{{ $lbl }}">Report Name</label>
                        <input type="text" wire:model="editForm.name" placeholder="e.g. Weekly AAH Report" class="{{ $inp }}"/>
                        @error('editForm.name') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Frequency <span class="text-red-500">*</span></label>
                        <select wire:model.live="editForm.frequency" class="{{ $inp }}">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        @error('editForm.frequency') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                    @if($editForm->frequency === 'weekly')
                        <div>
                            <label class="{{ $lbl }}">Day of the Week <span class="text-red-500">*</span></label>
                            <select wire:model="editForm.dayOfWeek" class="{{ $inp }}">
                                <option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option>
                                <option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="0">Sunday</option>
                            </select>
                            @error('editForm.dayOfWeek') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    @if($editForm->frequency === 'monthly')
                        <div>
                            <label class="{{ $lbl }}">Day of the Month <span class="text-red-500">*</span></label>
                            <select wire:model="editForm.dayOfMonth" class="{{ $inp }}">
                                @for($d=1;$d<=31;$d++)
                                    <option value="{{ $d }}">{{ $d }}{{ match(true){$d===1||$d===21||$d===31=>'st',$d===2||$d===22=>'nd',$d===3||$d===23=>'rd',default=>'th'} }}</option>
                                @endfor
                            </select>
                            @error('editForm.dayOfMonth') <p class="{{ $err }}">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label class="{{ $lbl }}">Send Time <span class="text-red-500">*</span></label>
                        <select wire:model="editForm.sendTime" class="{{ $inp }}">
                            @foreach($timeOptions as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                        @error('editForm.sendTime') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Deliver To <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="editForm.email" class="{{ $inp }}"/>
                        @error('editForm.email') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <button @click="showEditModal=false; $wire.cancelEdit()"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                    Cancel
                </button>
                <button wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit"
                    @click="$wire.on('schedule-edit-saved', ()=>showEditModal=false)"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <svg wire:loading wire:target="saveEdit" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    {{-- ─── Delete Confirm Modal ────────────────────────────────────────── --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal=false; $wire.cancelDelete()"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900" @click.stop
            x-transition:enter="transition ease-out duration-200 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Delete Schedule?</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">This scheduled report will be permanently deleted and no further emails will be sent.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button @click="showDeleteModal=false; $wire.cancelDelete()"
                    class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                    Cancel
                </button>
                <button wire:click="deleteReport" @click="showDeleteModal=false"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>

</div>
