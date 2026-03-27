<?php
$ruleId = isset($rule['id']) ? $rule['id'] : null;
$formUrl = $isEdit
    ? '/notification-rules/' . $ruleId . '/edit'
    : '/notification-rules/create';

$savedFilters = json_decode($rule['filters_json'] ?? '{}', true) ?: array();
$groupedFiltersJson = json_encode(!empty($savedFilters['groups']) ? $savedFilters : array('match_type' => 'all', 'groups' => array()));

$times = array();
for ($h = 0; $h < 24; $h++) {
    for ($m = 0; $m < 60; $m += 15) {
        $times[] = sprintf('%02d:%02d', $h, $m);
    }
}

function ruleVal($rule, $key, $default = '') {
    return isset($rule[$key]) ? $rule[$key] : $default;
}
?>

<script>
function notificationRuleFormData() {
    return {
        status: '<?php echo h(ruleVal($rule, 'status', 'draft')); ?>',
        frequency: '<?php echo h(ruleVal($rule, 'frequency', 'daily')); ?>',
        dateScopeType: '<?php echo h(ruleVal($rule, 'date_scope_type', 'last_30_days')); ?>',
        groupedFilters: <?php echo $groupedFiltersJson; ?>,
        previewOrders: [],
        previewCount: 0,
        previewLoading: false,
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
            const dateLabels = {gte:'on or after', lte:'on or before', between:'between'};
            let ops = isStringField(field) ? str : isNumberField(field) ? num : isDateField(field) ? dat : [];
            return ops.map(o => ({
                value: o,
                label: isDateField(field) ? (dateLabels[o] || o) : (labels[o] || o)
            }));
        },
        loadPreview() {
            this.previewLoading = true;
            const form = document.getElementById('rule-form');
            const fd = new FormData(form);
            fd.set('filters_json', JSON.stringify(this.groupedFilters));
            fd.set('match_type', this.groupedFilters.match_type || 'all');
            fd.set('date_scope_type', this.dateScopeType);
            const params = new URLSearchParams(fd);
            fetch('/notification-rules/preview-orders', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body: params.toString()
            }).then(r=>r.json()).then(d=>{
                this.previewOrders = (d.orders || []).map(o => o.Order || o);
                this.previewCount = d.count || 0;
                this.previewLoading = false;
            }).catch(()=>this.previewLoading=false);
        }
    };
}
</script>

<div class="app-page" x-data="notificationRuleFormData()">

    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Automation</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">
                <?php echo $isEdit ? 'Edit Notification Rule' : 'Create Notification Rule'; ?>
            </h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Configure when, how and who gets notified about matching order data.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/notification-rules" class="app-button">← Back to Rules</a>
        </div>
    </section>

    <?php if ($this->Session->read('Message.flash')): ?>
    <div class="flash-error"><?php echo $this->Session->flash('flash', array('element' => false)); ?></div>
    <?php endif; ?>

    <?php if (!empty($validationError)): ?>
    <div class="flash-error flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <span><?php echo h($validationError); ?></span>
    </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <!-- LEFT: Form -->
        <div class="space-y-6">
            <?php echo $this->Form->create(null, array(
                'url' => $formUrl,
                'id' => 'rule-form',
            )); ?>
            <input type="hidden" name="filters_json" id="filters-json-input" value="<?php echo h(json_encode($groupedFiltersJson)); ?>">
            <!-- Section 1: Rule Details -->
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-sm font-semibold text-slate-900">1. Rule Details</h3>
                </div>
                <div class="app-card-body space-y-4">
                    <div>
                        <label class="app-label">Rule Name (optional)</label>
                        <input type="text" name="name" value="<?php echo h(ruleVal($rule, 'name')); ?>" class="app-input" placeholder="Auto-generated if empty">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Channel</label>
                            <select name="channel" class="app-select">
                                <option value="email" <?php echo ruleVal($rule, 'channel', 'email') === 'email' ? 'selected' : ''; ?>>Email</option>
                            </select>
                        </div>
                        <div>
                            <label class="app-label">Status</label>
                            <select name="status" x-model="status" class="app-select">
                                <option value="draft" :selected="status==='draft'">Draft</option>
                                <option value="active" :selected="status==='active'">Active</option>
                                <option value="inactive" :selected="status==='inactive'">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Data Scope -->
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-sm font-semibold text-slate-900">2. Data Scope</h3>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Data Source</label>
                            <select name="data_source" class="app-select">
                                <option value="orders" <?php echo ruleVal($rule, 'data_source', 'orders') === 'orders' ? 'selected' : ''; ?>>Orders</option>
                            </select>
                        </div>
                        <div>
                            <label class="app-label">Date Scope</label>
                            <select name="date_scope_type" x-model="dateScopeType" class="app-select">
                                <option value="last_30_days">Last 30 Days</option>
                                <option value="last_7_days">Last 7 Days</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="custom_rolling">Custom Rolling</option>
                            </select>
                        </div>
                    </div>
                    <div x-show="dateScopeType === 'custom_rolling'" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Value</label>
                            <input type="number" name="date_scope_value" value="<?php echo h(ruleVal($rule, 'date_scope_value', 30)); ?>" min="1" class="app-input">
                        </div>
                        <div>
                            <label class="app-label">Unit</label>
                            <select name="date_scope_unit" class="app-select">
                                <option value="days" <?php echo ruleVal($rule, 'date_scope_unit', 'days') === 'days' ? 'selected' : ''; ?>>Days</option>
                                <option value="weeks" <?php echo ruleVal($rule, 'date_scope_unit') === 'weeks' ? 'selected' : ''; ?>>Weeks</option>
                                <option value="months" <?php echo ruleVal($rule, 'date_scope_unit') === 'months' ? 'selected' : ''; ?>>Months</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="app-label">Match Type</label>
                        <select name="match_type" class="app-select">
                            <option value="all" <?php echo ruleVal($rule, 'match_type', 'all') === 'all' ? 'selected' : ''; ?>>ALL groups must match</option>
                            <option value="any" <?php echo ruleVal($rule, 'match_type') === 'any' ? 'selected' : ''; ?>>ANY group must match</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Filter Builder -->
            <div class="app-card">
                <div class="app-card-header flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">3. Filter Builder</h3>
                    <button type="button" @click="loadPreview()" class="app-button app-button-sm app-button-soft">Preview Orders</button>
                </div>
                <div class="app-card-body space-y-3">
                    <template x-if="!groupedFilters.groups || groupedFilters.groups.length === 0">
                        <div class="text-center text-slate-400 py-6 text-sm">No filters yet. Click "Add Group" to start.</div>
                    </template>
                    <template x-for="(group, gi) in (groupedFilters.groups || [])" :key="gi">
                        <div class="filter-group">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase text-slate-500">Group</span>
                                    <span x-text="gi + 1" class="text-xs font-bold text-slate-900"></span>
                                    <select x-model="group.logic" class="filter-select-sm">
                                        <option value="and">AND</option>
                                        <option value="or">OR</option>
                                    </select>
                                </div>
                                <button type="button" @click="removeGroup(gi)" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                            </div>
                            <template x-for="(filter, fi) in (group.filters || [])" :key="fi">
                                <div class="filter-row">
                                    <select x-model="filter.field" @change="filter.operator=''; filter.value=''" class="filter-select-sm">
                                        <option value="">Field...</option>
                                        <?php foreach ($filterFieldDefinitions as $fKey => $fDef): ?>
                                        <option value="<?php echo h($fKey); ?>"><?php echo h(ucwords(str_replace('_', ' ', $fKey))); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select x-model="filter.operator" class="filter-select-sm">
                                        <option value="">Operator...</option>
                                        <template x-for="op in getOperators(filter.field)" :key="op.value">
                                            <option :value="op.value" :selected="filter.operator === op.value" x-text="op.label"></option>
                                        </template>
                                    </select>
                                    <template x-if="filter.operator !== 'between'">
                                        <input type="text" x-model="filter.value" class="filter-input-sm" placeholder="Value..." style="flex:1;">
                                    </template>
                                    <template x-if="filter.operator === 'between'">
                                        <div class="flex gap-1 flex-1">
                                            <input type="text" :value="Array.isArray(filter.value)?filter.value[0]:''"
                                                   @input="filter.value=[($event.target.value),Array.isArray(filter.value)?filter.value[1]:'']"
                                                   class="filter-input-sm" placeholder="From" style="flex:1;">
                                            <input type="text" :value="Array.isArray(filter.value)?filter.value[1]:''"
                                                   @input="filter.value=[Array.isArray(filter.value)?filter.value[0]:'',($event.target.value)]"
                                                   class="filter-input-sm" placeholder="To" style="flex:1;">
                                        </div>
                                    </template>
                                    <button type="button" @click="removeFilter(gi, fi)" class="text-red-400 hover:text-red-600 p-1 rounded flex-shrink-0">✕</button>
                                </div>
                            </template>
                            <button type="button" @click="addFilter(gi)" class="mt-2 text-xs text-blue-600 hover:text-blue-700">+ Add filter</button>
                        </div>
                    </template>
                    <button type="button" @click="addGroup()" class="app-button app-button-sm">+ Add Group</button>
                </div>
            </div>

            <!-- Section 4: Schedule -->
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-sm font-semibold text-slate-900">4. Schedule</h3>
                </div>
                <div class="app-card-body space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="app-label">Frequency</label>
                            <select name="frequency" x-model="frequency" class="app-select">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="app-label">Send Time</label>
                            <select name="send_time" class="app-select">
                                <?php foreach ($times as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ruleVal($rule, 'send_time', '08:00') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div x-show="frequency === 'weekly'">
                        <label class="app-label">Day of Week</label>
                        <select name="day_of_week" class="app-select">
                            <option value="1" <?php echo ruleVal($rule, 'day_of_week') == 1 ? 'selected' : ''; ?>>Monday</option>
                            <option value="2" <?php echo ruleVal($rule, 'day_of_week') == 2 ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="3" <?php echo ruleVal($rule, 'day_of_week') == 3 ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="4" <?php echo ruleVal($rule, 'day_of_week') == 4 ? 'selected' : ''; ?>>Thursday</option>
                            <option value="5" <?php echo ruleVal($rule, 'day_of_week') == 5 ? 'selected' : ''; ?>>Friday</option>
                            <option value="6" <?php echo ruleVal($rule, 'day_of_week') == 6 ? 'selected' : ''; ?>>Saturday</option>
                            <option value="0" <?php echo ruleVal($rule, 'day_of_week') == 0 ? 'selected' : ''; ?>>Sunday</option>
                        </select>
                    </div>
                    <div x-show="frequency === 'monthly'">
                        <label class="app-label">Day of Month</label>
                        <select name="day_of_month" class="app-select">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?php echo $d; ?>" <?php echo ruleVal($rule, 'day_of_month') == $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 5: Email Output -->
            <div class="app-card">
                <div class="app-card-header">
                    <h3 class="text-sm font-semibold text-slate-900">5. Email Output</h3>
                </div>
                <div class="app-card-body space-y-4">
                    <div>
                        <label class="app-label">Recipient Email</label>
                        <input type="email" name="recipient_email" value="<?php echo h($isEdit ? ruleVal($rule, 'recipient_email') : ($userEmail ?? '')); ?>" class="app-input" placeholder="recipient@example.com" required>
                    </div>
                    <div>
                        <label class="app-label">Row Limit</label>
                        <input type="number" name="email_row_limit" value="<?php echo h(ruleVal($rule, 'email_row_limit', 300)); ?>" min="1" max="1000" class="app-input">
                        <p class="text-xs text-slate-400 mt-1">Maximum rows to include in the email (1–1000)</p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <a href="/notification-rules" class="app-button">Cancel</a>
                <button type="submit" class="app-button app-button-primary"
                        onclick="document.getElementById('filters-json-input').value = JSON.stringify(Alpine.store ? window._alpine_gf() : {})">
                    <?php echo $isEdit ? 'Update Rule' : 'Create Rule'; ?>
                </button>
            </div>

            <?php echo $this->Form->end(); ?>
        </div>

        <!-- RIGHT: Preview Panel -->
        <div class="space-y-4">
            <div class="app-card sticky top-4">
                <div class="app-card-header flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Order Preview</h3>
                    <button type="button" @click="loadPreview()" class="app-button app-button-sm app-button-soft">
                        Refresh
                    </button>
                </div>
                <div class="app-card-body">
                    <div x-show="previewLoading" class="text-center py-8 text-slate-400 text-sm">Loading...</div>
                    <div x-show="!previewLoading && previewOrders.length === 0 && previewCount === 0" class="text-center py-8 text-slate-300 text-sm">
                        Click "Preview Orders" to see matching results
                    </div>
                    <div x-show="!previewLoading && (previewOrders.length > 0 || previewCount >= 0)">
                        <p class="text-xs text-slate-500 mb-3">
                            Showing <span x-text="previewOrders.length" class="font-semibold text-slate-900"></span> 
                            of <span x-text="previewCount" class="font-semibold text-slate-900"></span> matching orders
                        </p>
                        <div class="space-y-2 max-h-[500px] overflow-y-auto">
                            <template x-for="o in previewOrders" :key="o.id">
                                <div class="flex items-start justify-between gap-2 p-2.5 rounded-xl border border-slate-100 bg-slate-50 text-xs">
                                    <div class="min-w-0">
                                        <p class="font-mono font-medium text-slate-900" x-text="o.order_number || '—'"></p>
                                        <p class="text-slate-500 truncate" x-text="o.product_description || '—'"></p>
                                        <p class="text-slate-400" x-text="o.supplier_id"></p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="font-medium text-slate-900" x-text="o.orderdate ? o.orderdate.substring(0,10) : '—'"></p>
                                        <p class="text-slate-500" x-text="o.response || '—'"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const fieldDefs = <?php echo json_encode($filterFieldDefinitions); ?>;
function isStringField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'string'; }
function isNumberField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'number'; }
function isDateField(f) { return f && fieldDefs[f] && fieldDefs[f].type === 'datetime'; }

// Before submit, serialize grouped filters
document.getElementById('rule-form').addEventListener('submit', function(e) {
    // Get Alpine data
    const el = document.querySelector('[x-data]');
    if (el && el._x_dataStack) {
        const data = el._x_dataStack[0];
        if (data && data.groupedFilters) {
            document.getElementById('filters-json-input').value = JSON.stringify(data.groupedFilters);
        }
    }
});
</script>
