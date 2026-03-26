<?php $rules = isset($rules) ? $rules : []; ?>
<div class="app-page">
  <section class="app-hero">
    <div>
      <p style="margin:0 0 .5rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.24em;color:#2563eb">Orders Workspace</p>
      <h1 style="font-size:1.875rem;font-weight:600;color:#020617;letter-spacing:-.025em;margin:0">Notification Rules</h1>
      <p style="margin:.75rem 0 0;font-size:.875rem;line-height:1.625;color:#475569">Configure smart rules to send alerts when orders match your criteria.</p>
    </div>
    <a href="/orders/notification-rules/create" class="app-button app-button-primary">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      New Rule
    </a>
  </section>

  <div class="app-card" style="overflow:hidden">
    <div class="app-card-header">
      <h2 style="font-size:1rem;font-weight:600;color:#0f172a;margin:0">Rules (<?php echo count($rules); ?>)</h2>
    </div>
    <?php if (empty($rules)): ?>
      <div style="padding:4rem 2rem;text-align:center">
        <div style="width:48px;height:48px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
          <svg width="22" height="22" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <p style="font-size:1rem;font-weight:600;color:#0f172a;margin:0 0 .5rem">No notification rules yet</p>
        <p style="font-size:.875rem;color:#64748b;margin:0 0 1.5rem">Create your first rule to receive automated alerts.</p>
        <a href="/orders/notification-rules/create" class="app-button app-button-primary">Create First Rule</a>
      </div>
    <?php else: ?>
      <table class="app-table">
        <thead><tr>
          <th>Name</th><th>Schedule</th><th>Recipients</th><th>Status</th><th>Last Result</th><th>Last Run</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php foreach ($rules as $rule): $r = $rule['NotificationRule']; ?>
            <tr>
              <td>
                <div style="font-weight:600"><?php echo h($r['name']); ?></div>
                <?php if (!empty($r['description'])): ?><div style="font-size:.75rem;color:#64748b"><?php echo h($r['description']); ?></div><?php endif; ?>
              </td>
              <td>
                <span class="badge badge-blue"><?php echo ucfirst(h($r['frequency'])); ?></span>
                <span style="font-size:.75rem;color:#64748b;margin-left:.25rem"><?php echo h($r['send_time']); ?></span>
              </td>
              <td>
                <?php $emails = explode("\n", trim($r['emails'])); ?>
                <div style="font-size:.8125rem"><?php echo h($emails[0]); ?></div>
                <?php if (count($emails)>1): ?><div style="font-size:.75rem;color:#94a3b8">+<?php echo count($emails)-1; ?> more</div><?php endif; ?>
              </td>
              <td>
                <?php $active = (bool)$r['is_active'];
                $activeUrl = $active ? '/orders/notification-rules/deactivate/' : '/orders/notification-rules/activate/'; ?>
                <form method="POST" action="<?php echo $activeUrl . $r['id']; ?>" style="display:inline">
                  <button type="submit" class="badge <?php echo $active ? 'badge-green' : 'badge-gray'; ?>" style="cursor:pointer;border:none">
                    <?php echo $active ? 'Active' : 'Inactive'; ?>
                  </button>
                </form>
              </td>
              <td>
                <?php
                $lastResult = $r['last_result'] ?? null;
                if ($lastResult === 'success') echo '<span class="badge badge-green">✓ Sent</span>';
                elseif ($lastResult === 'failed') echo '<span class="badge badge-red">✗ Failed</span>';
                elseif ($lastResult === 'no_matches') echo '<span class="badge badge-gray">No matches</span>';
                else echo '<span style="color:#94a3b8;font-size:.8125rem">–</span>';
                ?>
              </td>
              <td style="font-size:.8125rem;color:#64748b">
                <?php echo !empty($r['last_run_at']) ? date('M j, Y H:i', strtotime($r['last_run_at'])) : '–'; ?>
              </td>
              <td>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                  <a href="/orders/notification-rules/<?php echo $r['id']; ?>/edit" class="app-button app-button-sm">Edit</a>
                  <form method="POST" action="/orders/notification-rules/duplicate/<?php echo $r['id']; ?>" style="display:inline">
                    <button type="submit" class="app-button app-button-sm app-button-soft">Duplicate</button>
                  </form>
                  <form method="POST" action="/orders/notification-rules/delete/<?php echo $r['id']; ?>" style="display:inline" onsubmit="return confirm('Delete this rule?')">
                    <button type="submit" class="app-button app-button-sm app-button-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
