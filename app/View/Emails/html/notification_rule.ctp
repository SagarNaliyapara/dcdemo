<!DOCTYPE html>
<html><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<style>
body{margin:0;padding:0;background:#f8fafc;font-family:Inter,ui-sans-serif,system-ui,sans-serif;color:#020617}
.wrap{max-width:620px;margin:0 auto;padding:2rem 1rem}
.card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden}
.card-header{padding:1.5rem 2rem;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#eff6ff,#fff)}
.logo-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.logo{width:36px;height:36px;background:#1e40af;border-radius:8px;display:flex;align-items:center;justify-content:center}
.card-body{padding:1.75rem 2rem}
table{width:100%;border-collapse:collapse;margin:1.25rem 0}
th{padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;border-bottom:1px solid #e2e8f0}
td{padding:.625rem .75rem;font-size:.8125rem;border-bottom:1px solid #f8fafc;color:#374151}
.badge{display:inline-flex;padding:.2rem .5rem;border-radius:9999px;font-size:.6875rem;font-weight:600}
.badge-green{background:#dcfce7;color:#16a34a}
.badge-red{background:#fee2e2;color:#dc2626}
.badge-blue{background:#dbeafe;color:#2563eb}
.badge-yellow{background:#fef9c3;color:#ca8a04}
.badge-gray{background:#f1f5f9;color:#475569}
.alert-box{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.5rem}
.footer{padding:1rem 2rem;font-size:.75rem;color:#94a3b8;text-align:center;border-top:1px solid #f1f5f9}
.btn{display:inline-block;padding:.625rem 1.25rem;background:#2563eb;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600;text-decoration:none;margin-top:1rem}
</style></head>
<body>
<div class="wrap">
<div class="card">
  <div class="card-header">
    <div class="logo-row">
      <div class="logo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      </div>
      <div>
        <p style="font-size:1rem;font-weight:700;color:#020617;margin:0">Notification Alert</p>
        <p style="margin:.1rem 0 0;font-size:.75rem;color:#64748b">DC Orders</p>
      </div>
    </div>
    <h1 style="font-size:1.25rem;font-weight:700;color:#020617;margin:0"><?php echo h($rule['name']); ?></h1>
    <?php if (!empty($rule['description'])): ?>
      <p style="font-size:.875rem;color:#64748b;margin:.5rem 0 0"><?php echo h($rule['description']); ?></p>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div class="alert-box">
      <p style="font-size:.875rem;font-weight:700;color:#ea580c;margin:0 0 .25rem">
        🔔 <?php echo number_format($matchCount); ?> order<?php echo $matchCount !== 1 ? 's' : ''; ?> matched this rule
      </p>
      <p style="font-size:.8125rem;color:#9a3412;margin:0">
        Rule: <strong><?php echo h($rule['name']); ?></strong> · <?php echo date('F j, Y \a\t g:i A'); ?>
      </p>
    </div>

    <?php if (!empty($orders)): ?>
    <table>
      <thead><tr>
        <th>Order #</th><th>Date</th><th>Customer</th><th>Status</th><th>Amount</th>
      </tr></thead>
      <tbody>
        <?php foreach (array_slice($orders, 0, 30) as $order):
          $o = isset($order['Order']) ? $order['Order'] : $order;
          $statusMap = ['completed'=>'badge-green','pending'=>'badge-yellow','processing'=>'badge-blue','cancelled'=>'badge-red'];
          $cls = isset($statusMap[$o['status']]) ? $statusMap[$o['status']] : 'badge-gray';
        ?>
        <tr>
          <td style="font-weight:600"><?php echo h($o['order_number']); ?></td>
          <td><?php echo h(date('M j, Y', strtotime($o['order_date']))); ?></td>
          <td><?php echo h($o['customer_name']); ?></td>
          <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst(h($o['status'])); ?></span></td>
          <td style="font-weight:600">$<?php echo number_format($o['total_amount'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($matchCount > 30): ?>
      <p style="font-size:.8125rem;color:#64748b">Showing 30 of <?php echo number_format($matchCount); ?> matches.</p>
    <?php endif; ?>
    <?php endif; ?>

    <a href="<?php echo Configure::read('App.fullBaseUrl'); ?>/orders/history" class="btn">View All Orders →</a>
  </div>
  <div class="footer">
    DC Orders · Notification Rule: <?php echo h($rule['name']); ?> · <?php echo ucfirst($rule['frequency']); ?>
  </div>
</div>
</div>
</body>
</html>
