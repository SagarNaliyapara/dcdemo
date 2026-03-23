@props(['orders', 'totalAmount', 'selectedCount' => 0, 'perPage' => 25])

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Products</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($orders->total()) }}</p>
        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">matching current filters</p>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Order Amount</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">£{{ number_format($totalAmount, 2) }}</p>
        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">across all filtered orders</p>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Selected</p>
        <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $selectedCount }}</p>
        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">orders selected for action</p>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current Page</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</p>
        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ $perPage }} per page</p>
    </div>
</div>
