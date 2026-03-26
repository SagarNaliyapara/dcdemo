<!DOCTYPE html>
<html><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<style>
body{margin:0;padding:0;background:#f8fafc;font-family:Inter,ui-sans-serif,system-ui,sans-serif;color:#020617}
.wrap{max-width:620px;margin:0 auto;padding:2rem 1rem}
.card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden}
.card-header{padding:1.5rem 2rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.75rem}
.logo{width:36px;height:36px;background:#1e40af;border-radius:8px;display:flex;align-items:center;justify-content:center}
.logo-text{font-size:1rem;font-weight:700;color:#020617}
.card-body{padding:1.75rem 2rem}
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin:1.5rem 0}
.stat-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1rem}
.stat-label{font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#64748b;margin:0}
.stat-value{font-size:1.5rem;font-weight:700;color:#020617;margin:.375rem 0 0}
table{width:100%;border-collapse:collapse;margin:1.25rem 0}
th{padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;border-bottom:1px solid #e2e8f0}
td{padding:.625rem .75rem;font-size:.8125rem;border-bottom:1px solid #f8fafc;color:#374151}
.badge{display:inline-flex;padding:.2rem .5rem;border-radius:9999px;font-size:.6875rem;font-weight:600}
.badge-green{background:#dcfce7;color:#16a34a}
.badge-red{background:#fee2e2;color:#dc2626}
.badge-blue{background:#dbeafe;color:#2563eb}
.badge-yellow{background:#fef9c3;color:#ca8a04}
.badge-gray{background:#f1f5f9;color:#475569}
.footer{padding:1rem 2rem;font-size:.75rem;color:#94a3b8;text-align:center;border-top:1px solid #f1f5f9}
.btn{display:inline-block;padding:.625rem 1.25rem;background:#2563eb;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600;text-decoration:none;margin-top:1rem}
</style></head>
<body>
<div class="wrap">
<div class="card">
  <div class="card-header">
    <div class="logo">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    </div>
    <div>
      <p class="logo-text">DC Orders Report</p>
      <p style="margin:.15rem 0 0;font-size:.75rem;color:#64748b"><?php echo h($report['name']); ?></p>
    </div>
  </div>
  <div class="card-body">
    <h1 style="font-size:1.25rem;font-weight:700;color:#020617;margin:0 0 .5rem"><?php echo h($report['name']); ?></h1>
    <p style="font-size:.875rem;color:#64748b;margin:0">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>

    <div class="stat-grid">
      <div class="stat-box">
        <p class="stat-label">Total Orders</p>
        <p class="stat-value"><?php echo number_format($totalCount); ?></p>
      </div>
      <div class="stat-box">
        <p class="stat-label">Total Amount</p>
        <p class="stat-value">$<?php echo number_format($totalAmount, 2); ?></p>
      </div>
    </div>

    <?php if (!empty($orders)): ?>
    <table>
      <thead><tr>
        <th>Order #</th><th>Date</th><th>Customer</th><th>Status</th><th>Amount</th>
      </tr></thead>
      <tbody>
        <?php foreach (array_slice($orders, 0, 50) as $order):
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
    <?php if ($totalCount > 50): ?>
      <p style="font-size:.8125rem;color:#64748b">Showing 50 of <?php echo number_format($totalCount); ?> orders.</p>
    <?php endif; ?>
    <?php endif; ?>

    <a href="<?php echo Configure::read('App.fullBaseUrl'); ?>/orders/history" class="btn">View Full Report →</a>
  </div>
  <div class="footer">
    DC Orders · Automated report — <?php echo ucfirst($report['frequency']); ?> at <?php echo $report['send_time']; ?>
  </div>
</div>
</div>
</body>
</html>
