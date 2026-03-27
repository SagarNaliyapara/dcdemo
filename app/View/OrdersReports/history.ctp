<?php
// Helper function for response badges
function responseBadge($resp) {
    $map = array(
        'IN STOCK' => array('badge-green', 'In Stock'),
        'OUT OF STOCK' => array('badge-red', 'Out of Stock'),
        'AWAITING DELIVERY' => array('badge-yellow', 'Awaiting'),
        'ORDERED' => array('badge-blue', 'Ordered'),
        'NOT ORDERED' => array('badge-gray', 'Not Ordered'),
        'EXCESS STOCK' => array('badge-purple', 'Excess Stock'),
        'CONFIRMED' => array('badge-emerald', 'Confirmed'),
        'EXCESS' => array('badge-purple', 'Excess'),
    );
    $info = isset($map[$resp]) ? $map[$resp] : array('badge-gray', h($resp ?: 'Unknown'));
    return '<span class="badge ' . $info[0] . '">' . h($info[1]) . '</span>';
}

function flagDot($flag) {
    $map = array(
        'red' => 'flag-dot-red', 'green' => 'flag-dot-green',
        'black' => 'flag-dot-black', 'blue' => 'flag-dot-blue',
    );
    $cls = isset($map[$flag]) && $flag ? $map[$flag] : 'flag-dot-none';
    return '<span class="flag-dot ' . $cls . '" title="' . h($flag ?: 'No flag') . '"></span>';
}

$baseUrl = Router::url(array('action' => 'history'));
$sortLink = function($col, $label) use ($filters, $baseUrl) {
    $dir = ($filters['sortField'] === $col && $filters['sortDir'] === 'asc') ? 'desc' : 'asc';
    $arrow = '';
    if ($filters['sortField'] === $col) {
        $arrow = $filters['sortDir'] === 'asc' ? ' ▲' : ' ▼';
    }
    $params = array_merge($filters, array('sortField' => $col, 'sortDir' => $dir));
    unset($params['groupedFilters']); // handled separately
    $qStr = http_build_query($params);
    if (!empty($filters['groupedFilters'])) {
        $qStr .= '&groupedFilters=' . urlencode(json_encode($filters['groupedFilters']));
    }
    return '<a href="' . $baseUrl . '?' . $qStr . '">' . h($label) . $arrow . '</a>';
};

$filtersJsonForModal = json_encode(array(
    'search'      => $filters['search'],
    'dateFilter'  => $filters['dateFilter'],
    'startDate'   => $filters['startDate'],
    'endDate'     => $filters['endDate'],
));
$groupedFiltersJson = json_encode($filters['groupedFilters'] ?: array('match_type' => 'all', 'groups' => array()));

$activeFilterCount = 0;
if (!empty($filters['groupedFilters']['groups'])) {
    foreach ($filters['groupedFilters']['groups'] as $g) {
        $activeFilterCount += count($g['filters'] ?? array());
    }
}
?>

<script>
function orderHistoryData() {
    return {
        // UI state
        showAdvancedFilter: false,
        showScheduleModal: false,
        // Filter state
        groupedFilters: <?php echo $groupedFiltersJson; ?>,
        // Schedule modal state (merged here to avoid nested x-data $root issues)
        schedName: '',
        schedFreq: 'daily',
        schedTime: '08:00',
        schedDow: '1',
        schedDom: '1',
        schedEmail: '<?php echo h($this->Session->read('Auth.User.email')); ?>',
        schedSaving: false,
        schedSaved: false,
        schedTimes: <?php
            $times = array();
            for ($h = 0; $h < 24; $h++) {
                for ($m = 0; $m < 60; $m += 15) {
                    $times[] = sprintf('%02d:%02d', $h, $m);
                }
            }
            echo json_encode($times);
        ?>,
        // Filter helpers
        get activeFilterCount() {
            let count = 0;
            (this.groupedFilters.groups || []).forEach(g => count += (g.filters || []).length);
            return count;
        },
        get hasActiveFilters() { return this.activeFilterCount > 0; },
        get currentFiltersJson() {
            return JSON.stringify({
                search: <?php echo json_encode($filters['search']); ?>,
                dateFilter: <?php echo json_encode($filters['dateFilter']); ?>,
                startDate: <?php echo json_encode($filters['startDate']); ?>,
                endDate: <?php echo json_encode($filters['endDate']); ?>,
                groupedFilters: this.groupedFilters
            });
        },
        // Methods
        applyFilters() {
            if (!this.groupedFilters || !this.groupedFilters.groups) {
                this.groupedFilters = {match_type: 'all', groups: []};
            }
            document.getElementById('grouped-filters-input').value = JSON.stringify(this.groupedFilters);
            document.getElementById('filter-form').submit();
        },
        addGroup() {
            if (!this.groupedFilters.groups) this.groupedFilters.groups = [];
            this.groupedFilters.groups.push({logic: 'and', filters: [{field: '', operator: '', value: ''}]});
        },
        removeGroup(gi) { this.groupedFilters.groups.splice(gi, 1); },
        addFilter(gi) {
            if (!this.groupedFilters.groups[gi].filters) this.groupedFilters.groups[gi].filters = [];
            this.groupedFilters.groups[gi].filters.push({field: '', operator: '', value: ''});
        },
        removeFilter(gi, fi) { this.groupedFilters.groups[gi].filters.splice(fi, 1); },
        getOperators(field) {
            const str = ['equals','not_equals','contains','starts_with','ends_with','in','not_in'];
            const num = ['equals','not_equals','gt','gte','lt','lte','between'];
            const dat = ['gte','lte','between'];
            const labels = {
                equals:'equals', not_equals:'not equals', contains:'contains',
                starts_with:'starts with', ends_with:'ends with', in:'in', not_in:'not in',
                gt:'>', gte:'>=', lt:'<', lte:'<=', between:'between',
            };
            // date gte/lte have special labels
            const dateLabels = {gte:'on or after', lte:'on or before', between:'between'};
            let ops = isStringField(field) ? str : isNumberField(field) ? num : isDateField(field) ? dat : [];
            return ops.map(o => ({
                value: o,
                label: isDateField(field) ? (dateLabels[o] || o) : (labels[o] || o)
            }));
        },
        saveSchedule() {
            if (!this.schedEmail) { alert('Please enter a recipient email address'); return; }
            this.schedSaving = true;
            fetch('<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'saveScheduledReport')); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body: new URLSearchParams({
                    name: this.schedName,
                    frequency: this.schedFreq,
                    send_time: this.schedTime,
                    day_of_week: this.schedDow,
                    day_of_month: this.schedDom,
                    email: this.schedEmail,
                    filters_json: this.currentFiltersJson
                })
            }).then(r => r.json()).then(d => {
                this.schedSaving = false;
                this.schedSaved = true;
                setTimeout(() => { this.schedSaved = false; this.showScheduleModal = false; }, 2000);
            }).catch(() => { this.schedSaving = false; });
        }
    };
}
</script>

<div x-data="orderHistoryData()" class="app-page">

    <?php if ($this->Session->read('Message.flash')): ?>
    <div class="flash-success" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3500)">
        <?php echo $this->Session->flash('flash', array('element' => false)); ?>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Orders Workspace</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Order History</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Search processed orders, build grouped filters, and turn any result set into a scheduled email workflow.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?php echo Router::url(array('controller' => 'notification_rules_reports', 'action' => 'index')); ?>" class="app-button">
                Notification Rules
            </a>
            <a href="<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'scheduledReports')); ?>" class="app-button app-button-soft">
                Scheduled Reports
            </a>
        </div>
    </section>

    <!-- Stat Cards -->
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Filtered Orders</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950"><?php echo number_format($totalCount); ?></p>
            <p class="mt-1 text-sm text-slate-500">Across current search and filters</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Order Value</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">£<?php echo number_format($totalAmount, 2); ?></p>
            <p class="mt-1 text-sm text-slate-500">Total value across result set</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Selected Rows</p>
            <p class="mt-3 text-3xl font-semibold text-blue-700" id="selected-count">0</p>
            <p class="mt-1 text-sm text-slate-500">Ready for bulk actions</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Page Size</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950"><?php echo (int)$filters['perPage']; ?></p>
            <p class="mt-1 text-sm text-slate-500"><?php echo $hasActiveFilters ? 'Grouped filters active' : 'No advanced filters active'; ?></p>
        </div>
    </div>

    <!-- Filters Bar -->
    <section class="app-card">
        <div class="app-card-body space-y-4">
            <form method="get" action="<?php echo Router::url(array('action' => 'history')); ?>" id="filter-form">
                <input type="hidden" name="sortField" value="<?php echo h($filters['sortField']); ?>">
                <input type="hidden" name="sortDir" value="<?php echo h($filters['sortDir']); ?>">
                <input type="hidden" name="groupedFilters" id="grouped-filters-input" value="<?php echo h(json_encode($filters['groupedFilters'])); ?>">

                <div class="grid gap-3" style="grid-template-columns: minmax(0,1.4fr) 200px 200px 170px;">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                        <input type="text" name="search" value="<?php echo h($filters['search']); ?>"
                               placeholder="Search orders, descriptions, suppliers..."
                               class="app-input" style="padding-left:2.75rem;"
                               oninput="debounce(()=>document.getElementById('filter-form').submit(), 400)()">
                    </div>
                    <select name="dateFilter" class="app-select" onchange="this.form.submit()">
                        <?php
                        $dateOptions = array(
                            'all' => 'All time', 'today' => 'Today', 'yesterday' => 'Yesterday',
                            'last3days' => 'Last 3 days', 'last7days' => 'Last 7 days',
                            'thismonth' => 'This month', 'lastmonth' => 'Last month', 'custom' => 'Custom range'
                        );
                        foreach ($dateOptions as $val => $lbl):
                        ?>
                        <option value="<?php echo $val; ?>" <?php echo $filters['dateFilter'] === $val ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" @click="showAdvancedFilter = true"
                            :class="hasActiveFilters ? 'app-button justify-between border-blue-200 bg-blue-50 text-blue-700' : 'app-button justify-between'">
                        <span>Grouped Filters</span>
                        <span class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold text-white" x-text="activeFilterCount"></span>
                    </button>
                    <select name="perPage" class="app-select" onchange="this.form.submit()">
                        <?php foreach (array(10, 25, 50, 100) as $pp): ?>
                        <option value="<?php echo $pp; ?>" <?php echo $filters['perPage'] == $pp ? 'selected' : ''; ?>><?php echo $pp; ?> rows</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($filters['dateFilter'] === 'custom'): ?>
                <div class="grid gap-3 md:grid-cols-2">
                    <input type="date" name="startDate" value="<?php echo h($filters['startDate']); ?>" class="app-input" onchange="this.form.submit()">
                    <input type="date" name="endDate" value="<?php echo h($filters['endDate']); ?>" class="app-input" onchange="this.form.submit()">
                </div>
                <?php endif; ?>

                <!-- Active Scope Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[20px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Active Scope</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                            <?php
                            $dateLabels2 = array(
                                'all' => 'All time', 'today' => 'Today', 'yesterday' => 'Yesterday',
                                'last3days' => 'Last 3 Days', 'last7days' => 'Last 7 Days',
                                'thismonth' => 'This Month', 'lastmonth' => 'Last Month', 'custom' => 'Custom Range'
                            );
                            echo $dateLabels2[$filters['dateFilter']] ?? 'All time';
                            ?>
                        </span>
                        <?php if (!empty($filters['search'])): ?>
                        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Search: <?php echo h($filters['search']); ?></span>
                        <?php endif; ?>
                        <?php if ($hasActiveFilters): ?>
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Grouped filters applied</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php if ($filters['dateFilter'] !== 'custom' && (!empty($filters['search']) || $hasActiveFilters || $filters['dateFilter'] !== 'all')): ?>
                        <button type="button" @click="showScheduleModal = true" class="app-button app-button-soft app-button-sm">
                            Schedule Report
                        </button>
                        <?php endif; ?>
                        <a href="<?php echo Router::url(array('controller' => 'notification_rules_reports', 'action' => 'create')); ?>" class="app-button app-button-primary app-button-sm">
                            Create Notification Rule
                        </a>
                        <?php if (!empty($filters['search']) || $filters['dateFilter'] !== 'all' || $hasActiveFilters): ?>
                        <a href="<?php echo Router::url(array('action' => 'history')); ?>" class="app-button app-button-sm" style="border-color:#fca5a5;color:#dc2626;background:#fef2f2;">
                            × Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Orders Table -->
    <section class="app-card">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all" onchange="toggleAll(this)"></th>
                        <th>Flag</th>
                        <th><?php echo $sortLink('order_number', 'Order #'); ?></th>
                        <th><?php echo $sortLink('product_description', 'Description'); ?></th>
                        <th><?php echo $sortLink('supplier_id', 'Supplier'); ?></th>
                        <th><?php echo $sortLink('pipcode', 'PIP Code'); ?></th>
                        <th><?php echo $sortLink('category', 'Category'); ?></th>
                        <th><?php echo $sortLink('quantity', 'Qty / Appr'); ?></th>
                        <th><?php echo $sortLink('price', 'Price / DT'); ?></th>
                        <th>Subtotal</th>
                        <th><?php echo $sortLink('response', 'Response'); ?></th>
                        <th><?php echo $sortLink('orderdate', 'Order Date'); ?></th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="13" class="text-center text-slate-400 py-12">
                        <div class="flex flex-col items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="font-medium text-slate-400">No orders found</p>
                            <p class="text-sm text-slate-300">Try adjusting your search or filters</p>
                        </div>
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): $o = $order['Order']; ?>
                    <tr data-id="<?php echo $o['id']; ?>">
                        <td><input type="checkbox" class="row-checkbox" value="<?php echo $o['id']; ?>" onchange="updateSelectedCount()"></td>
                        <td>
                            <div class="flex items-center gap-1" x-data="{open:false, flag:'<?php echo h($o['flag'] ?? ''); ?>'}">
                                <button type="button" @click="open=!open" class="flex items-center p-1 rounded-lg hover:bg-slate-100">
                                    <span class="flag-dot" :class="{
                                        'flag-dot-red': flag==='red', 'flag-dot-green': flag==='green',
                                        'flag-dot-black': flag==='black', 'flag-dot-blue': flag==='blue',
                                        'flag-dot-none': !flag
                                    }"></span>
                                </button>
                                <div x-show="open" @click.outside="open=false" class="absolute z-20 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-2" style="min-width:130px;">
                                    <?php foreach (array('red' => 'Red', 'green' => 'Green', 'black' => 'Black', 'blue' => 'Blue', '' => 'None') as $fc => $fl): ?>
                                    <button type="button" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-lg"
                                            @click="flag='<?php echo $fc; ?>'; setFlag(<?php echo $o['id']; ?>, '<?php echo $fc; ?>'); open=false">
                                        <span class="flag-dot <?php echo $fc ? 'flag-dot-' . $fc : 'flag-dot-none'; ?>"></span>
                                        <?php echo $fl; ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="font-mono text-sm font-medium text-slate-900"><?php echo h($o['order_number'] ?? '-'); ?></span></td>
                        <td class="max-w-[200px]"><span class="block truncate" title="<?php echo h($o['product_description'] ?? ''); ?>"><?php echo h($o['product_description'] ?? '-'); ?></span></td>
                        <td><span class="text-xs text-slate-500"><?php echo h($o['supplier_id'] ?? '-'); ?></span></td>
                        <td><span class="font-mono text-xs"><?php echo h($o['pipcode'] ?? '-'); ?></span></td>
                        <td><span class="text-xs text-slate-500"><?php echo h($o['category'] ?? '-'); ?></span></td>
                        <td class="text-right whitespace-nowrap">
                            <span class="font-medium"><?php echo number_format((float)($o['quantity'] ?? 0), 0); ?></span>
                            <?php if (isset($o['approved_qty']) && $o['approved_qty'] !== null): ?>
                            <span class="text-slate-400 text-xs"> / <?php echo number_format((float)$o['approved_qty'], 0); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <span class="font-medium">£<?php echo number_format((float)($o['price'] ?? 0), 4); ?></span>
                            <?php if (isset($o['dt_price']) && $o['dt_price'] !== null): ?>
                            <span class="text-slate-400 text-xs block">DT: £<?php echo number_format((float)$o['dt_price'], 4); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right whitespace-nowrap font-medium">
                            £<?php echo number_format((float)($o['quantity'] ?? 0) * (float)($o['price'] ?? 0), 2); ?>
                        </td>
                        <td><?php echo responseBadge($o['response'] ?? ''); ?></td>
                        <td class="whitespace-nowrap text-xs text-slate-500"><?php echo $o['orderdate'] ? date('d M Y', strtotime($o['orderdate'])) : '-'; ?></td>
                        <td class="max-w-[160px]" x-data="{editing:false, note:<?php echo htmlspecialchars(json_encode($o['notes'] ?? ''), ENT_QUOTES); ?>}">
                            <div x-show="!editing" @click="editing=true" class="text-xs text-slate-500 truncate cursor-pointer hover:text-slate-900 min-h-[1.5rem] py-1 px-1 rounded hover:bg-slate-100" title="Click to edit note">
                                <span x-text="note || '—'"></span>
                            </div>
                            <div x-show="editing" class="flex gap-1 items-start">
                                <textarea x-model="note" class="text-xs border border-slate-200 rounded-lg p-1 w-full resize-none" rows="2" @keydown.esc="editing=false"></textarea>
                                <div class="flex flex-col gap-1">
                                    <button type="button" @click="saveNote(<?php echo $o['id']; ?>, note); editing=false"
                                            class="text-xs bg-emerald-500 text-white rounded px-2 py-1 hover:bg-emerald-600">✓</button>
                                    <button type="button" @click="editing=false"
                                            class="text-xs bg-slate-200 text-slate-600 rounded px-2 py-1 hover:bg-slate-300">✕</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Pagination -->
    <?php
    $paging = isset($this->request->params['paging']['Order']) ? $this->request->params['paging']['Order'] : array();
    $totalPages = isset($paging['pageCount']) ? $paging['pageCount'] : 1;
    $currentPage = isset($paging['page']) ? $paging['page'] : 1;
    $totalRecords = isset($paging['count']) ? $paging['count'] : $totalCount;
    $perPageCurrent = isset($paging['limit']) ? $paging['limit'] : $filters['perPage'];
    $firstItem = ($currentPage - 1) * $perPageCurrent + 1;
    $lastItem = min($currentPage * $perPageCurrent, $totalRecords);
    ?>
    <?php if ($totalPages > 1): ?>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            Showing <span class="font-semibold text-slate-900"><?php echo number_format($firstItem); ?></span>
            to <span class="font-semibold text-slate-900"><?php echo number_format($lastItem); ?></span>
            of <span class="font-semibold text-slate-900"><?php echo number_format($totalRecords); ?></span> orders
        </p>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
            <a href="<?php echo Router::url(array('action' => 'history', '?' => array_merge($filters, array('page' => $currentPage - 1, 'groupedFilters' => json_encode($filters['groupedFilters']))))); ?>">← Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $currentPage - 2);
            $end   = min($totalPages, $currentPage + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
            <?php if ($p == $currentPage): ?>
            <span class="current"><?php echo $p; ?></span>
            <?php else: ?>
            <a href="<?php echo Router::url(array('action' => 'history', '?' => array_merge($filters, array('page' => $p, 'groupedFilters' => json_encode($filters['groupedFilters']))))); ?>"><?php echo $p; ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo Router::url(array('action' => 'history', '?' => array_merge($filters, array('page' => $currentPage + 1, 'groupedFilters' => json_encode($filters['groupedFilters']))))); ?>">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================
         ADVANCED FILTER MODAL
         ======================================== -->
    <div x-show="showAdvancedFilter" x-cloak class="modal-overlay" @click="showAdvancedFilter=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="filter-builder">
                <div class="app-card-header flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Grouped Filters</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Build rule-style filters on order history</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="app-label mb-0">Match type:</label>
                        <select x-model="groupedFilters.match_type" class="filter-select-sm">
                            <option value="all">ALL groups match</option>
                            <option value="any">ANY group matches</option>
                        </select>
                    </div>
                </div>
                <div class="app-card-body space-y-3">
                    <template x-if="!groupedFilters.groups || groupedFilters.groups.length === 0">
                        <div class="text-center text-slate-400 py-8 text-sm">
                            No filter groups yet. Click "Add Group" to start building filters.
                        </div>
                    </template>
                    <template x-for="(group, gi) in (groupedFilters.groups || [])" :key="gi">
                        <div class="filter-group">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Group</span>
                                    <span class="text-xs font-bold text-slate-900" x-text="gi + 1"></span>
                                    <select x-model="group.logic" class="filter-select-sm text-xs">
                                        <option value="and">AND (all must match)</option>
                                        <option value="or">OR (any must match)</option>
                                    </select>
                                </div>
                                <button type="button" @click="removeGroup(gi)" class="text-red-400 hover:text-red-600 text-xs font-medium">Remove group</button>
                            </div>
                            <template x-for="(filter, fi) in (group.filters || [])" :key="fi">
                                <div class="filter-row">
                                    <select x-model="filter.field" @change="filter.operator=''; filter.value=''" class="filter-select-sm" style="min-width:150px;">
                                        <option value="">Select field...</option>
                                        <?php foreach ($filterFieldDefinitions as $fKey => $fDef): ?>
                                        <option value="<?php echo h($fKey); ?>"><?php echo h(ucwords(str_replace('_', ' ', $fKey))); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select x-model="filter.operator" class="filter-select-sm" style="min-width:130px;">
                                        <option value="">Operator...</option>
                                        <template x-for="op in getOperators(filter.field)" :key="op.value">
                                            <option :value="op.value" :selected="filter.operator === op.value" x-text="op.label"></option>
                                        </template>
                                    </select>
                                    <template x-if="filter.operator !== 'between'">
                                        <input type="text" x-model="filter.value" class="filter-input-sm" :placeholder="getValuePlaceholder(filter)" style="flex:1;">
                                    </template>
                                    <template x-if="filter.operator === 'between'">
                                        <div class="flex gap-1 flex-1">
                                            <input type="text" :value="Array.isArray(filter.value) ? filter.value[0] : ''"
                                                   @input="filter.value = [($event.target.value), Array.isArray(filter.value) ? filter.value[1] : '']"
                                                   class="filter-input-sm" placeholder="From" style="flex:1;">
                                            <input type="text" :value="Array.isArray(filter.value) ? filter.value[1] : ''"
                                                   @input="filter.value = [Array.isArray(filter.value) ? filter.value[0] : '', ($event.target.value)]"
                                                   class="filter-input-sm" placeholder="To" style="flex:1;">
                                        </div>
                                    </template>
                                    <button type="button" @click="removeFilter(gi, fi)" class="text-red-400 hover:text-red-600 p-1 rounded-lg hover:bg-red-50 flex-shrink-0" title="Remove filter">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="addFilter(gi)" class="mt-2 text-xs text-blue-600 font-medium hover:text-blue-700 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add filter
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addGroup()" class="app-button app-button-sm">+ Add Group</button>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between rounded-[22px] border border-slate-200 bg-white px-5 py-4 shadow-lg">
                <button type="button" @click="groupedFilters={match_type:'all',groups:[]}; applyFilters()" class="app-button app-button-danger">Reset Filters</button>
                <div class="flex gap-3">
                    <button type="button" @click="showAdvancedFilter=false" class="app-button">Cancel</button>
                    <button type="button" @click="applyFilters(); showAdvancedFilter=false" class="app-button app-button-primary">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================
         SCHEDULE REPORT MODAL
         ======================================== -->
    <div x-show="showScheduleModal" x-cloak class="modal-overlay" @click="showScheduleModal=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-box" @click.stop>
            <div class="modal-header">
                <h3 class="text-lg font-semibold text-slate-900">Schedule Report</h3>
                <p class="text-sm text-slate-500 mt-1">Set up automated email delivery of the current filtered orders</p>
            </div>
            <div class="modal-body space-y-4">
                <div>
                    <label class="app-label">Report Name (optional)</label>
                    <input type="text" x-model="schedName" class="app-input" placeholder="Auto-generated if empty">
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="app-label">Frequency</label>
                        <select x-model="schedFreq" class="app-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="app-label">Send Time</label>
                        <select x-model="schedTime" class="app-select">
                            <template x-for="t in schedTimes" :key="t">
                                <option :value="t" x-text="t"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div x-show="schedFreq === 'weekly'">
                    <label class="app-label">Day of Week</label>
                    <select x-model="schedDow" class="app-select">
                        <option value="1">Monday</option><option value="2">Tuesday</option>
                        <option value="3">Wednesday</option><option value="4">Thursday</option>
                        <option value="5">Friday</option><option value="6">Saturday</option>
                        <option value="0">Sunday</option>
                    </select>
                </div>
                <div x-show="schedFreq === 'monthly'">
                    <label class="app-label">Day of Month</label>
                    <select x-model="schedDom" class="app-select">
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                        <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="app-label">Recipient Email</label>
                    <input type="email" x-model="schedEmail" class="app-input" placeholder="recipient@example.com">
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                    <strong class="text-slate-700">Current filters will be saved:</strong>
                    <?php echo !empty($filters['search']) ? 'Search: "' . h($filters['search']) . '" · ' : ''; ?>
                    Date: <?php echo $dateLabels2[$filters['dateFilter']] ?? 'All time'; ?>
                    <?php echo $hasActiveFilters ? ' · Grouped filters active' : ''; ?>
                </div>
                <div class="flex items-center gap-2 text-xs text-emerald-600" x-show="schedSaved">
                    ✓ Report scheduled successfully!
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="showScheduleModal=false" class="app-button">Cancel</button>
                    <button type="button" @click="saveSchedule()" :disabled="schedSaving" class="app-button app-button-primary">
                        <span x-text="schedSaving ? 'Saving...' : 'Schedule Report'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Debounce utility
let _debTimer;
function debounce(fn, delay) {
    return function() {
        clearTimeout(_debTimer);
        _debTimer = setTimeout(fn, delay);
    };
}

// Update selected row count
function updateSelectedCount() {
    const count = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
}

function toggleAll(cb) {
    document.querySelectorAll('.row-checkbox').forEach(box => { box.checked = cb.checked; });
    updateSelectedCount();
}

// Set flag via AJAX
function setFlag(orderId, flag) {
    fetch('<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'updateFlag')); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: new URLSearchParams({id: orderId, flag: flag})
    });
}

// Save note via AJAX
function saveNote(orderId, note) {
    fetch('<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'updateNote')); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: new URLSearchParams({id: orderId, note: note})
    });
}

// Filter field type helpers (used by Alpine)
const fieldDefs = <?php echo json_encode($filterFieldDefinitions); ?>;
function isStringField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'string'; }
function isNumberField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'number'; }
function isDateField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'datetime'; }
function getValuePlaceholder(filter) {
    if (!filter.field) return 'Value...';
    if (filter.operator === 'in' || filter.operator === 'not_in') return 'Comma-separated values';
    if (isDateField(filter.field)) return 'YYYY-MM-DD';
    return 'Enter value...';
}

// Alpine functions exposed globally (they're used in x-data scopes)
document.addEventListener('alpine:init', () => {
    Alpine.store('filterDefs', fieldDefs);
});
</script>


