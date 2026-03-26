<div class="app-page">
    <?php if ($this->Session->read('Message.flash')): ?>
    <div class="flash-success"><?php echo $this->Session->flash('flash', array('element' => false)); ?></div>
    <?php endif; ?>

    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Automation</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Notification Rules</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Create smart alert rules with grouped filter logic. Rules run on a schedule and email matching orders.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?php echo Router::url(array('controller' => 'notification_rules', 'action' => 'create')); ?>" class="app-button app-button-primary">
                + Create Rule
            </a>
            <a href="<?php echo Router::url(array('controller' => 'orders', 'action' => 'history')); ?>" class="app-button">
                Order History
            </a>
        </div>
    </section>

    <!-- Filters -->
    <section class="app-card">
        <div class="app-card-body">
            <form method="get" action="<?php echo Router::url(array('action' => 'index')); ?>" class="grid gap-3 md:grid-cols-4">
                <div class="relative md:col-span-2">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input type="text" name="search" value="<?php echo h($search); ?>"
                           placeholder="Search by name or email..." class="app-input" style="padding-left:2.75rem;">
                </div>
                <select name="status" class="app-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="error" <?php echo $statusFilter === 'error' ? 'selected' : ''; ?>>Error</option>
                </select>
                <select name="frequency" class="app-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $freqFilter === 'all' ? 'selected' : ''; ?>>All frequencies</option>
                    <option value="daily" <?php echo $freqFilter === 'daily' ? 'selected' : ''; ?>>Daily</option>
                    <option value="weekly" <?php echo $freqFilter === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="monthly" <?php echo $freqFilter === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                </select>
            </form>
        </div>
    </section>

    <!-- Rules Table -->
    <section class="app-card">
        <?php if (empty($rules)): ?>
        <div class="app-card-body text-center py-16">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-slate-400 font-medium">No notification rules found</p>
            <a href="<?php echo Router::url(array('controller' => 'notification_rules', 'action' => 'create')); ?>" class="app-button app-button-primary mt-6 inline-flex">
                Create your first rule
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Frequency</th>
                        <th>Recipient</th>
                        <th>Next Run</th>
                        <th>Last Result</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $ruleRow): $r = $ruleRow['NotificationRule']; ?>
                    <tr>
                        <td>
                            <a href="<?php echo Router::url(array('action' => 'edit', $r['id'])); ?>" class="font-medium text-slate-900 hover:text-blue-600">
                                <?php echo h($r['name'] ?? 'Unnamed Rule'); ?>
                            </a>
                            <p class="text-xs text-slate-400"><?php echo h(ucfirst($r['data_source'] ?? 'orders')); ?> · <?php echo h(str_replace('_', ' ', $r['date_scope_type'] ?? 'last_30_days')); ?></p>
                            <?php if (!empty($r['last_error_message'])): ?>
                            <p class="text-xs text-red-500 mt-0.5 truncate max-w-[200px]" title="<?php echo h($r['last_error_message']); ?>">⚠ <?php echo h($r['last_error_message']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusBadge = array(
                                'active' => 'badge-green', 'inactive' => 'badge-gray',
                                'draft' => 'badge-blue', 'error' => 'badge-red',
                            );
                            $sc = isset($statusBadge[$r['status']]) ? $statusBadge[$r['status']] : 'badge-gray';
                            ?>
                            <span class="badge <?php echo $sc; ?>"><?php echo h(ucfirst($r['status'])); ?></span>
                        </td>
                        <td><span class="badge badge-blue"><?php echo h(ucfirst($r['frequency'])); ?></span></td>
                        <td class="text-sm text-slate-600"><?php echo h($r['recipient_email']); ?></td>
                        <td class="text-sm text-slate-500 whitespace-nowrap">
                            <?php echo $r['next_run_at'] ? date('d M H:i', strtotime($r['next_run_at'])) : '—'; ?>
                        </td>
                        <td class="text-sm text-slate-500">
                            <?php echo $r['last_result_count'] !== null ? number_format($r['last_result_count']) . ' rows' : '—'; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <a href="<?php echo Router::url(array('action' => 'edit', $r['id'])); ?>" class="app-button app-button-sm">Edit</a>

                                <?php echo $this->Form->create(null, array('url' => array('action' => 'toggleStatus', $r['id']), 'style' => 'display:inline')); ?>
                                <?php echo $this->Form->submit($r['status'] === 'active' ? 'Deactivate' : 'Activate', array(
                                    'class' => 'app-button app-button-sm ' . ($r['status'] === 'active' ? 'app-button-danger' : 'app-button-soft'),
                                    'div' => false,
                                )); ?>
                                <?php echo $this->Form->end(); ?>

                                <?php echo $this->Form->create(null, array('url' => array('action' => 'duplicate', $r['id']), 'style' => 'display:inline')); ?>
                                <?php echo $this->Form->submit('Copy', array('class' => 'app-button app-button-sm', 'div' => false)); ?>
                                <?php echo $this->Form->end(); ?>

                                <?php echo $this->Form->create(null, array('url' => array('action' => 'delete', $r['id']), 'style' => 'display:inline')); ?>
                                <?php echo $this->Form->submit('Delete', array(
                                    'class' => 'app-button app-button-sm app-button-danger',
                                    'div' => false,
                                    'onclick' => "return confirm('Delete this notification rule?')",
                                )); ?>
                                <?php echo $this->Form->end(); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
