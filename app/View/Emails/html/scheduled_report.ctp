<!DOCTYPE html>
<html><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<style>
body{margin:0;padding:0;background:#f8fafc;font-family:Inter,ui-sans-serif,system-ui,sans-serif;color:#020617}
.wrap{max-width:680px;margin:0 auto;padding:2rem 1rem}
.card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden}
.card-header{padding:1.5rem 2rem;border-bottom:1px solid #f1f5f9;background:linear-gradient(135deg,#f0fdf4,#fff)}
.logo-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.logo{width:36px;height:36px;background:#16a34a;border-radius:8px;display:flex;align-items:center;justify-content:center}
.card-body{padding:1.75rem 2rem}
.meta{font-size:.8125rem;color:#64748b;margin:.5rem 0 1.25rem}
.stats-row{display:flex;gap:1rem;margin-bottom:1.5rem}
.stat-box{flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem 1rem;text-align:center}
.stat-label{font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin:0 0 .25rem}
.stat-value{font-size:1.25rem;font-weight:700;color:#020617;margin:0}
table{width:100%;border-collapse:collapse;margin:1rem 0}
th{padding:.5rem .75rem;text-align:left;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;border-bottom:2px solid #e2e8f0;background:#f8fafc}
td{padding:.625rem .75rem;font-size:.8125rem;border-bottom:1px solid #f1f5f9;color:#374151}
tr:last-child td{border-bottom:none}
.badge{display:inline-flex;padding:.2rem .5rem;border-radius:9999px;font-size:.6875rem;font-weight:600}
.badge-green{background:#dcfce7;color:#16a34a}
.badge-red{background:#fee2e2;color:#dc2626}
.badge-blue{background:#dbeafe;color:#2563eb}
.badge-yellow{background:#fef9c3;color:#ca8a04}
.badge-gray{background:#f1f5f9;color:#475569}
.csv-note{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.875rem 1.125rem;margin-top:1rem;font-size:.8125rem;color:#15803d}
.footer{padding:1rem 2rem;font-size:.75rem;color:#94a3b8;text-align:center;border-top:1px solid #f1f5f9}
.btn{display:inline-block;padding:.625rem 1.25rem;background:#16a34a;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600;text-decoration:none;margin-top:1.25rem}
</style></head>
<body>
<div class="wrap">
<div class="card">

  <div class="card-header">
    <div class="logo-row">
      <div class="logo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      </div>
      <div>
        <p style="font-size:1rem;font-weight:700;color:#020617;margin:0">Scheduled Report</p>
        <p style="margin:.1rem 0 0;font-size:.75rem;color:#64748b">DC Orders</p>
      </div>
    </div>
    <h1 style="font-size:1.25rem;font-weight:700;color:#020617;margin:0"><?php echo h($report['name']); ?></h1>
  </div>

  <div class="card-body">
    <p class="meta">
      Generated <?php echo date('j M Y H:i'); ?> for <strong><?php echo h($recipientEmail); ?></strong>
    </p>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-box">
        <p class="stat-label">Total Orders</p>
        <p class="stat-value"><?php echo number_format($totalCount); ?></p>
      </div>
      <div class="stat-box">
        <p class="stat-label">Total Amount</p>
        <p class="stat-value" style="font-size:1rem">£<?php echo number_format($totalAmount, 2); ?></p>
      </div>
      <div class="stat-box">
        <p class="stat-label">Frequency</p>
        <p class="stat-value" style="font-size:.9rem"><?php echo ucfirst(h($report['frequency'])); ?></p>
      </div>
    </div>

    <!-- Orders Table -->
    <?php if (!empty($orders)):
      $shown = array_slice($orders, 0, 10);
    ?>
    <table>
      <thead><tr>
        <th>Order</th>
        <th>Description</th>
        <th>Supplier</th>
        <th style="text-align:right">Approved Qty</th>
        <th style="text-align:right">Subtotal</th>
        <th>Status</th>
        <th>Date</th>
      </tr></thead>
      <tbody>
        <?php foreach ($shown as $o):
          $approvedQty = (float)(isset($o['approved_qty']) ? $o['approved_qty'] : (isset($o['quantity']) ? $o['quantity'] : 0));
          $price       = (float)(isset($o['price']) ? $o['price'] : 0);
          $subtotal    = $approvedQty * $price;
          $response    = isset($o['response']) ? $o['response'] : '';
          $dateRaw     = isset($o['orderdate']) ? $o['orderdate'] : '';
          $statusBadge = array(
            'IN STOCK'        => 'badge-green',
            'CONFIRMED'       => 'badge-green',
            'ORDERED'         => 'badge-blue',
            'AWAITING DELIVERY' => 'badge-blue',
            'OUT OF STOCK'    => 'badge-red',
            'NOT ORDERED'     => 'badge-red',
            'EXCESS STOCK'    => 'badge-yellow',
            'EXCESS'          => 'badge-yellow',
          );
          $cls = isset($statusBadge[$response]) ? $statusBadge[$response] : 'badge-gray';
        ?>
        <tr>
          <td style="font-weight:600;white-space:nowrap"><?php echo h(isset($o['order_number']) ? $o['order_number'] : '-'); ?></td>
          <td style="max-width:160px"><?php echo h(isset($o['product_description']) ? $o['product_description'] : '-'); ?></td>
          <td><?php echo h(isset($o['supplier_id']) ? $o['supplier_id'] : '-'); ?></td>
          <td style="text-align:right"><?php echo number_format($approvedQty, 2); ?></td>
          <td style="text-align:right;font-weight:600">£<?php echo number_format($subtotal, 2); ?></td>
          <td><span class="badge <?php echo $cls; ?>"><?php echo h($response ?: '-'); ?></span></td>
          <td style="white-space:nowrap;color:#64748b"><?php echo $dateRaw ? date('j M Y H:i', strtotime($dateRaw)) : '-'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalCount > 10): ?>
    <div class="csv-note">
      📎 Showing 10 of <?php echo number_format($totalCount); ?> orders. The full result set is attached as CSV.
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <a href="<?php echo Configure::read('App.fullBaseUrl'); ?>/orders/history" class="btn">View All Orders →</a>
  </div>

  <div class="footer">
    DC Orders · <?php echo h($report['name']); ?> · <?php echo ucfirst(h($report['frequency'])); ?> at <?php echo h($report['send_time']); ?>
  </div>
</div>
</div>
</body>
</html>
