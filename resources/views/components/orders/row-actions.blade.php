@props(['selectedCount' => 0])

<div class="app-card">
    <div class="app-card-body flex flex-wrap items-center gap-2">
        <span class="mr-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
            Bulk Actions
        @if($selectedCount > 0)
            <span class="ml-1 rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $selectedCount }}</span>
        @endif
        </span>

        @php($disabled = $selectedCount === 0)

        <button wire:click="reOrder" @disabled($disabled) class="app-button app-button-primary disabled:cursor-not-allowed disabled:opacity-40">Re Order</button>
        <button wire:click="addToExcessStock" @disabled($disabled) class="app-button disabled:cursor-not-allowed disabled:opacity-40">Add To Excess Stock</button>
        <button wire:click="addToBulkOrder" @disabled($disabled) class="app-button disabled:cursor-not-allowed disabled:opacity-40">Add To Bulk Order</button>
        <button wire:click="addToAvailabilityTracker" @disabled($disabled) class="app-button disabled:cursor-not-allowed disabled:opacity-40">Add To Availability Tracker</button>
        <button wire:click="addToReturnsManagement" @disabled($disabled) class="app-button disabled:cursor-not-allowed disabled:opacity-40">Add To Returns Management</button>
    </div>
</div>
