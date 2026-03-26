<div class="app-page">
    <?php if ($this->Session->read('Message.flash')): ?>
    <div class="flash-success"><?php echo $this->Session->flash('flash', array('element' => false)); ?></div>
    <?php endif; ?>

    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Orders Workspace</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Scheduled Reports</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Manage automated email reports. Reports are triggered based on saved order filters.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?php echo Router::url(array('controller' => 'orders', 'action' => 'history')); ?>" class="app-button">
                ← Order History
            </a>
        </div>
    </section>

    <section class="app-card">
        <div class="app-card-header flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Your Scheduled Reports</h2>
            <span class="text-sm text-slate-500"><?php echo count($reports); ?> report<?php echo count($reports) !== 1 ? 's' : ''; ?></span>
        </div>
        <?php if (empty($reports)): ?>
        <div class="app-card-body text-center py-16">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-slate-400 font-medium">No scheduled reports yet</p>
            <p class="text-slate-300 text-sm mt-1">Go to Order History and click "Schedule Report" to create one</p>
            <a href="<?php echo Router::url(array('controller' => 'orders', 'action' => 'history')); ?>" class="app-button app-button-primary mt-6 inline-flex">
                Go to Order History
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Frequency</th>
                        <th>Send Time</th>
                        <th>Next Run</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): $r = $report['ScheduledReport']; ?>
                    <tr>
                        <td>
                            <span class="font-medium text-slate-900"><?php echo h($r['name'] ?? 'Unnamed'); ?></span>
                            <?php
                            $filt = json_decode($r['filters_json'] ?? '{}', true) ?: array();
                            if (!empty($filt['search'])): ?>
                            <p class="text-xs text-slate-400">Search: <?php echo h($filt['search']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td><span class="text-sm text-slate-600"><?php echo h($r['email']); ?></span></td>
                        <td><span class="badge badge-blue"><?php echo h(ucfirst($r['frequency'])); ?></span></td>
                        <td><?php echo h($r['send_time']); ?>
                            <?php if ($r['frequency'] === 'weekly' && $r['day_of_week'] !== null):
                                $days = array(0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat');
                                echo '<span class="text-xs text-slate-400"> · ' . ($days[$r['day_of_week']] ?? '') . '</span>';
                            endif; ?>
                            <?php if ($r['frequency'] === 'monthly' && $r['day_of_month'] !== null): ?>
                            <span class="text-xs text-slate-400"> · Day <?php echo $r['day_of_month']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-slate-500">
                            <?php echo $r['next_run_at'] ? date('d M Y H:i', strtotime($r['next_run_at'])) : '—'; ?>
                        </td>
                        <td>
                            <?php if ($r['is_active']): ?>
                            <span class="badge badge-green">Active</span>
                            <?php else: ?>
                            <span class="badge badge-gray">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <?php echo $this->Form->create(null, array(
                                    'url' => array('action' => 'toggleScheduledReport', $r['id']),
                                    'style' => 'display:inline',
                                )); ?>
                                <?php echo $this->Form->submit($r['is_active'] ? 'Deactivate' : 'Activate', array(
                                    'class' => 'app-button app-button-sm ' . ($r['is_active'] ? 'app-button-danger' : 'app-button-soft'),
                                    'div' => false,
                                )); ?>
                                <?php echo $this->Form->end(); ?>

                                <?php echo $this->Form->create(null, array(
                                    'url' => array('action' => 'deleteScheduledReport', $r['id']),
                                    'style' => 'display:inline',
                                )); ?>
                                <?php echo $this->Form->submit('Delete', array(
                                    'class' => 'app-button app-button-sm app-button-danger',
                                    'div' => false,
                                    'onclick' => "return confirm('Delete this scheduled report?')",
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
