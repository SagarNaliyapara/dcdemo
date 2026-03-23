@props(['orders', 'sortField', 'sortDirection'])

<div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

    {{-- Loading overlay --}}
    <div wire:loading class="absolute inset-0 z-20 flex items-center justify-center bg-white/60 backdrop-blur-[1px] dark:bg-zinc-900/60">
        <div class="flex items-center gap-3 rounded-lg bg-white px-5 py-3 shadow-lg dark:bg-zinc-800">
            <svg class="h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Loading…</span>
        </div>
    </div>

    <div class="max-h-[calc(100vh-380px)] overflow-auto">
        <table class="min-w-max w-full text-sm">

            {{-- ── Sticky header ── --}}
            <thead class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                <tr>
                    <th class="w-10 px-3 py-3 text-center">
                        <input type="checkbox" @change="$event.target.checked ? $wire.selectAll() : $wire.deselectAll()" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"/>
                    </th>
                    <th class="w-10 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
                    <th class="w-14 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Flag</th>

                    @php
                        $sortCols = [
                            ['field'=>'orderdate',   'label'=>'Date & Time',  'align'=>'left'],
                            ['field'=>'order_number','label'=>'Order No',     'align'=>'left'],
                        ];
                        $plainCols = ['PIP Code','Description','Ordered Qty','Approved Qty'];
                        $priceCols = [
                            ['field'=>'price',    'label'=>'Price',    'align'=>'right'],
                            ['field'=>'dt_price', 'label'=>'DT Price', 'align'=>'right'],
                        ];
                        $catSupCols = [
                            ['field'=>'category',    'label'=>'Category', 'align'=>'left'],
                            ['field'=>'supplier_id', 'label'=>'Supplier', 'align'=>'left'],
                        ];
                        $sortIcon = function(string $field) use ($sortField, $sortDirection): string {
                            if ($sortField === $field) {
                                return $sortDirection === 'asc'
                                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>'
                                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>';
                            }
                            return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>';
                        };
                        $sortColor = fn(string $f) => $sortField === $f ? 'text-blue-500' : 'text-zinc-300 dark:text-zinc-600';
                    @endphp

                    @foreach($sortCols as $col)
                        <th wire:click="sortBy('{{ $col['field'] }}')"
                            class="cursor-pointer select-none whitespace-nowrap px-3 py-3 text-{{ $col['align'] }} text-xs font-semibold uppercase tracking-wider text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                            <span class="inline-flex items-center gap-1">{{ $col['label'] }}
                                <svg class="h-3.5 w-3.5 {{ $sortColor($col['field']) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $sortIcon($col['field']) !!}</svg>
                            </span>
                        </th>
                    @endforeach

                    @foreach($plainCols as $label)
                        <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</th>
                    @endforeach

                    @foreach($priceCols as $col)
                        <th wire:click="sortBy('{{ $col['field'] }}')"
                            class="cursor-pointer select-none whitespace-nowrap px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                            <span class="inline-flex items-center justify-end gap-1">{{ $col['label'] }}
                                <svg class="h-3.5 w-3.5 {{ $sortColor($col['field']) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $sortIcon($col['field']) !!}</svg>
                            </span>
                        </th>
                    @endforeach

                    <th class="whitespace-nowrap px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sub Total</th>

                    @foreach($catSupCols as $col)
                        <th wire:click="sortBy('{{ $col['field'] }}')"
                            class="cursor-pointer select-none whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                            <span class="inline-flex items-center gap-1">{{ $col['label'] }}
                                <svg class="h-3.5 w-3.5 {{ $sortColor($col['field']) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $sortIcon($col['field']) !!}</svg>
                            </span>
                        </th>
                    @endforeach

                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Response</th>
                    <th class="whitespace-nowrap px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Discount</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Notes</th>
                    <th class="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order Ref</th>
                </tr>
            </thead>

            {{-- ── Body ── --}}
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($orders as $order)
                    <tr wire:key="order-{{ $order->id }}"
                        class="{{ $loop->even ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900' }} transition-colors duration-100 hover:bg-blue-50/40 dark:hover:bg-zinc-700/30">

                        {{-- Checkbox --}}
                        <td class="w-10 px-3 py-2.5 text-center">
                            <input type="checkbox" wire:model.live="selectedOrders" value="{{ $order->id }}"
                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"/>
                        </td>

                        {{-- SN --}}
                        <td class="px-3 py-2.5 text-center font-mono text-xs text-zinc-400 dark:text-zinc-500">
                            {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                        </td>

                        {{-- Flag picker --}}
                        <td class="px-3 py-2.5 text-center">
                            <div
                                x-data="{
                                    open: false, flag: @js($order->flag ?? 'none'), top: 0, left: 0,
                                    dotClass(f) {
                                        return {red:'bg-red-500',green:'bg-green-500',black:'bg-zinc-800',blue:'bg-blue-500',none:'bg-zinc-300'}[f]??'bg-zinc-300';
                                    },
                                    labelText(f) {
                                        return {red:'Red Flag',green:'Green Flag',black:'Black Flag',blue:'Blue Flag',none:'No Flag'}[f]??'No Flag';
                                    },
                                    toggle(btn) {
                                        const r=btn.getBoundingClientRect(); this.top=r.bottom+6; this.left=Math.max(4,r.left-56); this.open=!this.open;
                                    },
                                    pick(val) { this.flag=val; this.open=false; $wire.updateFlag({{ $order->id }},val); }
                                }"
                                class="relative flex items-center justify-center"
                            >
                                <button @click="toggle($el)" :title="labelText(flag)" :class="dotClass(flag)"
                                    class="group flex h-6 w-6 items-center justify-center rounded-full shadow-sm ring-2 ring-white transition-transform hover:scale-110 focus:outline-none dark:ring-zinc-800">
                                    <svg class="h-3 w-3 text-white/80 opacity-0 transition-opacity group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak @click.outside="open=false"
                                        :style="`position:fixed;top:${top}px;left:${left}px;z-index:9999`"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="w-36 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                                        <p class="mb-1 px-2 pt-0.5 text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Set Flag</p>
                                        @foreach(['red'=>['label'=>'Red Flag','cls'=>'bg-red-500'],'green'=>['label'=>'Green Flag','cls'=>'bg-green-500'],'black'=>['label'=>'Black Flag','cls'=>'bg-zinc-800'],'blue'=>['label'=>'Blue Flag','cls'=>'bg-blue-500'],'none'=>['label'=>'No Flag','cls'=>'bg-zinc-300']] as $fVal=>$fCfg)
                                            <button @click="pick('{{ $fVal }}')"
                                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700"
                                                :class="flag==='{{ $fVal }}'?'bg-zinc-100 dark:bg-zinc-700':''">
                                                <span class="h-3 w-3 flex-shrink-0 rounded-full {{ $fCfg['cls'] }}"></span>
                                                {{ $fCfg['label'] }}
                                                <svg x-show="flag==='{{ $fVal }}'" class="ml-auto h-3 w-3 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        @endforeach
                                    </div>
                                </template>
                            </div>
                        </td>

                        {{-- Date & Time --}}
                        <td class="whitespace-nowrap px-3 py-2.5">
                            @if($order->orderdate)
                                <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $order->orderdate->format('d M Y') }}</span><br>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $order->orderdate->format('H:i') }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </td>

                        {{-- Order No --}}
                        <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $order->order_number ?? $order->ordernumber ?? '—' }}</td>

                        {{-- PIP Code --}}
                        <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $order->pipcode ?? '—' }}</td>

                        {{-- Description --}}
                        <td class="max-w-[220px] px-3 py-2.5">
                            <span class="block truncate text-xs text-zinc-800 dark:text-zinc-200" title="{{ $order->product_description }}">{{ $order->product_description ?? '—' }}</span>
                        </td>

                        {{-- Ordered Qty --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-center text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $order->quantity !== null ? number_format((float)$order->quantity, 0) : '—' }}
                        </td>

                        {{-- Approved Qty --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-center text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $order->approved_qty !== null ? number_format((float)$order->approved_qty, 0) : '—' }}
                        </td>

                        {{-- Price --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $order->price !== null ? '£'.number_format((float)$order->price, 4) : '—' }}
                        </td>

                        {{-- DT Price --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs {{ ($order->price && $order->dt_price && (float)$order->price > (float)$order->dt_price) ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                            {{ $order->dt_price !== null ? '£'.number_format((float)$order->dt_price, 4) : '—' }}
                        </td>

                        {{-- Sub Total --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                            £{{ number_format($order->sub_total, 2) }}
                        </td>

                        {{-- Category --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">{{ $order->category ?? '—' }}</td>

                        {{-- Supplier --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $order->supplier_id ?? '—' }}</td>

                        {{-- Response --}}
                        <td class="px-3 py-2.5">
                            @if($order->response)
                                <span class="inline-flex max-w-[200px] items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold leading-tight {{ $order->response_badge_class }}">{{ $order->response }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </td>

                        {{-- Discount --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-right text-xs {{ $order->discount !== null && $order->discount > 0 ? 'font-semibold text-green-600 dark:text-green-400' : 'text-zinc-500' }}">
                            @if($order->discount !== null)
                                {{ $order->discount > 0 ? '+' : '' }}£{{ number_format(abs($order->discount), 4) }}
                            @else —
                            @endif
                        </td>

                        {{-- Notes — inline editable --}}
                        <td class="px-3 py-2.5">
                            <div
                                x-data="{
                                    editing: false, saving: false,
                                    note:     @js($order->notes ?? ''),
                                    original: @js($order->notes ?? ''),
                                    async save() {
                                        this.saving=true;
                                        await $wire.updateNote({{ $order->id }}, this.note);
                                        this.original=this.note; this.editing=false; this.saving=false;
                                    },
                                    cancel() { this.note=this.original; this.editing=false; }
                                }"
                                class="min-w-[160px] max-w-[220px]"
                            >
                                <div x-show="!editing" @click="editing=true;$nextTick(()=>$refs.noteInput.focus())"
                                    class="group flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/60">
                                    <span class="block flex-1 truncate text-xs" :class="note?'text-zinc-700 dark:text-zinc-300':'italic text-zinc-300 dark:text-zinc-600'" x-text="note||'Add note…'"></span>
                                    <svg class="h-3 w-3 flex-shrink-0 text-zinc-300 opacity-0 transition-opacity group-hover:opacity-100 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414a2 2 0 01.586-1.414z"/>
                                    </svg>
                                </div>
                                <div x-show="editing" x-cloak class="space-y-1.5">
                                    <input x-ref="noteInput" type="text" x-model="note"
                                        @keydown.enter="save()" @keydown.escape="cancel()"
                                        class="w-full rounded-md border border-blue-400 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm outline-none ring-2 ring-blue-200 dark:border-blue-500 dark:bg-zinc-700 dark:text-white dark:ring-blue-900"
                                        placeholder="Enter note…"/>
                                    <div class="flex items-center gap-1">
                                        <button @click="save()" :disabled="saving"
                                            class="flex items-center gap-1 rounded-md bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60">
                                            <svg x-show="saving" class="h-2.5 w-2.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                            <span x-text="saving?'Saving…':'Save'"></span>
                                        </button>
                                        <button @click="cancel()" class="rounded-md px-2 py-0.5 text-[10px] font-medium text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-200">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Order Ref --}}
                        <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $order->parent_id ?? $order->order_number ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="19" class="py-20 text-center">
                            <div class="flex flex-col items-center gap-3 text-zinc-400 dark:text-zinc-500">
                                <svg class="h-12 w-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-sm font-medium">No orders found</p>
                                <p class="text-xs">Try adjusting your search or filter criteria.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
