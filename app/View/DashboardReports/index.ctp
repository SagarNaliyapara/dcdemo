<div class="app-page">
    <section class="app-hero">
        <div class="max-w-3xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.24em] text-blue-600">Pharmacy Platform</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Dashboard</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Monitor your pharmacy order management system. Track orders, manage scheduled reports and notification rules.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'history')); ?>" class="app-button app-button-primary">
                View Order History
            </a>
            <a href="<?php echo Router::url(array('controller' => 'notification_rules_reports', 'action' => 'index')); ?>" class="app-button">
                Notification Rules
            </a>
        </div>
    </section>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Orders</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950"><?php echo number_format($totalOrders); ?></p>
            <p class="mt-1 text-sm text-slate-500">All time orders in system</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Total Value</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">£<?php echo number_format($totalValue, 2); ?></p>
            <p class="mt-1 text-sm text-slate-500">Combined order value</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">In Stock</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-700"><?php echo number_format($inStockCount); ?></p>
            <p class="mt-1 text-sm text-slate-500">Items currently in stock</p>
        </div>
        <div class="app-stat-card">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Out of Stock</p>
            <p class="mt-3 text-3xl font-semibold text-red-600"><?php echo number_format($outOfStockCount); ?></p>
            <p class="mt-1 text-sm text-slate-500">Items currently unavailable</p>
        </div>
    </div>

    <!-- Recent Orders -->
    <section class="app-card">
        <div class="app-card-header flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Recent Orders</h2>
            <a href="<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'history')); ?>" class="text-sm text-blue-600 font-medium hover:underline">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Description</th>
                        <th>Supplier</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Response</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="7" class="text-center text-slate-400 py-8">No orders found</td></tr>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order): $o = $order['Order']; ?>
                    <tr>
                        <td>
                            <span class="font-mono text-sm font-medium text-slate-900"><?php echo h($o['order_number'] ?? '-'); ?></span>
                        </td>
                        <td><?php echo h($o['product_description'] ?? '-'); ?></td>
                        <td><span class="text-slate-500 text-xs"><?php echo h($o['supplier_id'] ?? '-'); ?></span></td>
                        <td><?php echo number_format((float)($o['quantity'] ?? 0), 0); ?></td>
                        <td>£<?php echo number_format((float)($o['price'] ?? 0), 4); ?></td>
                        <td>
                            <?php
                            $resp = $o['response'] ?? '';
                            $badges = array(
                                'IN STOCK' => 'badge-green', 'OUT OF STOCK' => 'badge-red',
                                'AWAITING DELIVERY' => 'badge-yellow', 'ORDERED' => 'badge-blue',
                                'NOT ORDERED' => 'badge-gray', 'EXCESS STOCK' => 'badge-purple',
                                'CONFIRMED' => 'badge-emerald',
                            );
                            $badgeClass = isset($badges[$resp]) ? $badges[$resp] : 'badge-gray';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo h($resp ?: 'Unknown'); ?></span>
                        </td>
                        <td class="text-slate-500 text-xs whitespace-nowrap"><?php echo $o['orderdate'] ? date('d M Y', strtotime($o['orderdate'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Quick Links -->
    <div class="grid gap-4 md:grid-cols-3">
        <a href="<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'history')); ?>" class="app-card p-6 hover:shadow-lg transition-shadow group">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-4 group-hover:bg-blue-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h3 class="font-semibold text-slate-900 mb-1">Order History</h3>
            <p class="text-sm text-slate-500">Search, filter and manage all pharmacy orders</p>
        </a>
        <a href="<?php echo Router::url(array('controller' => 'orders_reports', 'action' => 'scheduledReports')); ?>" class="app-card p-6 hover:shadow-lg transition-shadow group">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-4 group-hover:bg-emerald-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-slate-900 mb-1">Scheduled Reports</h3>
            <p class="text-sm text-slate-500">Set up automated email reports on a schedule</p>
        </a>
        <a href="<?php echo Router::url(array('controller' => 'notification_rules_reports', 'action' => 'index')); ?>" class="app-card p-6 hover:shadow-lg transition-shadow group">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-4 group-hover:bg-purple-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h3 class="font-semibold text-slate-900 mb-1">Notification Rules</h3>
            <p class="text-sm text-slate-500">Build smart alerts with grouped filter logic</p>
        </a>
    </div>
</div>
