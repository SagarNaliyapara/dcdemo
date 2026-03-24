@props([
    'groups' => [],
    'fieldDefinitions' => [],
    'matchTypeBinding',
    'groupsBinding',
    'addGroupMethod',
    'removeGroupMethod',
    'addFilterMethod',
    'removeFilterMethod',
    'title' => 'Filters',
    'caption' => 'Refine the results with grouped filters.',
])

@php
    $fieldMap = collect($fieldDefinitions)->keyBy('key');
    $operatorLabels = [
        'equals' => 'Equals',
        'not_equals' => 'Does Not Equal',
        'contains' => 'Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
        'in' => 'Is Any Of',
        'not_in' => 'Is None Of',
        'gt' => 'Greater Than',
        'gte' => 'Greater Than Or Equal To',
        'lt' => 'Less Than',
        'lte' => 'Less Than Or Equal To',
        'between' => 'Between',
    ];
@endphp

<div class="app-card">
    <div class="app-card-header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $caption }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">+ add filter</span>
                <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">- remove filter</span>
            </div>
        </div>
    </div>

    <div class="app-card-body space-y-5">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,280px)_1fr] lg:items-end">
            <div>
                <label class="app-label">How Should Groups Match</label>
                <select wire:model.live="{{ $matchTypeBinding }}" class="app-select">
                    <option value="all">Match all groups</option>
                    <option value="any">Match any group</option>
                </select>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Group logic controls how filters combine inside a group. The match mode controls how groups combine with each other.
            </div>
        </div>

        <div class="space-y-4">
            @foreach($groups as $groupIndex => $group)
                @php
                    $groupBinding = $groupsBinding.'.'.$groupIndex;
                @endphp
                <section wire:key="group-builder-{{ $group['id'] }}" class="rounded-[22px] border border-slate-200 bg-slate-50/70 p-4">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="rounded-2xl bg-slate-900 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-white">
                                Group {{ $loop->iteration }}
                            </div>
                            <div class="w-44">
                                <label class="app-label">Logic Inside Group</label>
                                <select wire:model.live="{{ $groupBinding }}.logic" class="app-select">
                                    <option value="and">And</option>
                                    <option value="or">Or</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="{{ $addFilterMethod }}('{{ $group['id'] }}')" class="app-button app-button-soft px-3 py-2">
                                <span class="text-lg leading-none">+</span>
                                Add Filter
                            </button>
                            <button
                                type="button"
                                wire:click="{{ $removeGroupMethod }}('{{ $group['id'] }}')"
                                @disabled(count($groups) === 1)
                                class="app-button app-button-danger px-3 py-2 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Remove Group
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($group['filters'] as $filterIndex => $filter)
                            @php
                                $filterBinding = $groupBinding.'.filters.'.$filterIndex;
                                $definition = $fieldMap[$filter['field']] ?? $fieldDefinitions[0] ?? null;
                                $operators = $definition['operators'] ?? [];
                                $type = $definition['type'] ?? 'string';
                            @endphp
                            <div wire:key="group-filter-{{ $filter['id'] }}" class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-700">Filter {{ $filterIndex + 1 }}</div>
                                    <div class="flex gap-2">
                                        <button type="button" wire:click="{{ $addFilterMethod }}('{{ $group['id'] }}')" class="flex h-9 w-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-lg font-semibold text-blue-700">+</button>
                                        <button
                                            type="button"
                                            wire:click="{{ $removeFilterMethod }}('{{ $group['id'] }}', '{{ $filter['id'] }}')"
                                            @disabled(count($group['filters']) === 1)
                                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-lg font-semibold text-rose-700 disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            -
                                        </button>
                                    </div>
                                </div>

                                <div class="grid gap-3 lg:grid-cols-3">
                                    <div>
                                        <label class="app-label">Field</label>
                                        <select wire:model.live="{{ $filterBinding }}.field" class="app-select">
                                            @foreach($fieldDefinitions as $fieldDefinition)
                                                <option value="{{ $fieldDefinition['key'] }}">{{ $fieldDefinition['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="app-label">Operator</label>
                                        <select wire:model.live="{{ $filterBinding }}.operator" class="app-select">
                                            @foreach($operators as $operator)
                                                <option value="{{ $operator }}">{{ $operatorLabels[$operator] ?? \Illuminate\Support\Str::headline($operator) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="app-label">Value</label>
                                        @if(($filter['operator'] ?? '') === 'between')
                                            <div class="grid grid-cols-2 gap-2">
                                                <input
                                                    type="{{ $type === 'number' ? 'number' : ($type === 'datetime' ? 'date' : 'text') }}"
                                                    wire:model.live="{{ $filterBinding }}.value"
                                                    class="app-input"
                                                    @if($type === 'number') step="0.01" @endif
                                                />
                                                <input
                                                    type="{{ $type === 'number' ? 'number' : ($type === 'datetime' ? 'date' : 'text') }}"
                                                    wire:model.live="{{ $filterBinding }}.secondary_value"
                                                    class="app-input"
                                                    @if($type === 'number') step="0.01" @endif
                                                />
                                            </div>
                                        @else
                                            <input
                                                type="{{ $type === 'number' ? 'number' : ($type === 'datetime' ? 'date' : 'text') }}"
                                                wire:model.live="{{ $filterBinding }}.value"
                                                class="app-input"
                                                @if($type === 'number') step="0.01" @endif
                                                placeholder="{{ in_array(($filter['operator'] ?? ''), ['in', 'not_in'], true) ? 'Comma separated values' : 'Enter value' }}"
                                            />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <button type="button" wire:click="{{ $addGroupMethod }}" class="app-button app-button-soft">
            Add Group
        </button>
    </div>
</div>
