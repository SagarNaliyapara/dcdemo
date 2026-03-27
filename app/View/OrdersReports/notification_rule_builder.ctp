<?php
$rule = isset($rule) ? $rule['NotificationRule'] : [];
$isEdit = !empty($rule['id']);
$filtersJson = !empty($rule['filters_json']) ? json_decode($rule['filters_json'], true) : [];
$conditions = !empty($filtersJson['conditions']) ? $filtersJson['conditions'] : [['field'=>'status','operator'=>'equals','value'=>'']];
$conditionLogic = !empty($filtersJson['condition_logic']) ? $filtersJson['condition_logic'] : 'AND';

$fields = [
  'status' => 'Status', 'flag' => 'Flag', 'total_amount' => 'Total Amount',
  'customer_name' => 'Customer Name', 'customer_email' => 'Customer Email',
  'order_number' => 'Order Number',
];
$operators = [
  'equals' => 'equals', 'not_equals' => 'not equals', 'contains' => 'contains',
  'not_contains' => 'not contains', 'starts_with' => 'starts with', 'ends_with' => 'ends with',
  'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<=', 'between' => 'between',
  'in' => 'in list', 'is_empty' => 'is empty', 'is_not_empty' => 'is not empty',
];
?>
<div class="app-page">
  <section class="app-hero">
    <div>
      <p style="margin:0 0 .5rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.24em;color:#2563eb">Orders Workspace</p>
      <h1 style="font-size:1.875rem;font-weight:600;color:#020617;letter-spacing:-.025em;margin:0">
        <?php echo $isEdit ? 'Edit Rule' : 'Create Notification Rule'; ?>
      </h1>
      <p style="margin:.75rem 0 0;font-size:.875rem;line-height:1.625;color:#475569">Define conditions and schedule for your alert.</p>
    </div>
    <a href="/orders/notification-rules" class="app-button">← Back to Rules</a>
  </section>

  <div style="display:grid;grid-template-columns:1fr 380px;gap:1.25rem;align-items:start">
    <!-- FORM -->
    <form method="POST" action="<?php echo $isEdit ? '/orders/notification-rules/' . $rule['id'] . '/update' : '/orders/notification-rules/store'; ?>" id="ruleForm">
      <?php if ($isEdit): ?>
        <input type="hidden" name="data[NotificationRule][id]" value="<?php echo $rule['id']; ?>"/>
      <?php endif; ?>
      <input type="hidden" name="data[NotificationRule][filters_json]" id="filtersJsonOutput"/>

      <div class="app-card" style="overflow:hidden;margin-bottom:1.25rem">
        <div class="app-card-header"><h2 style="font-size:1rem;font-weight:600;margin:0">Basic Information</h2></div>
        <div class="app-card-body" style="display:flex;flex-direction:column;gap:1rem">
          <div>
            <label class="app-label">Rule name</label>
            <input type="text" name="data[NotificationRule][name]" class="app-input" placeholder="High value orders alert" required value="<?php echo h($rule['name'] ?? ''); ?>"/>
          </div>
          <div>
            <label class="app-label">Description (optional)</label>
            <textarea name="data[NotificationRule][description]" class="app-textarea" rows="2" placeholder="Brief description…"><?php echo h($rule['description'] ?? ''); ?></textarea>
          </div>
          <div>
            <label class="app-label">Recipient emails (one per line)</label>
            <textarea name="data[NotificationRule][emails]" class="app-textarea" rows="3" placeholder="email@example.com&#10;another@example.com" required><?php echo h($rule['emails'] ?? ''); ?></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div>
              <label class="app-label">Frequency</label>
              <select name="data[NotificationRule][frequency]" class="app-select">
                <?php foreach (['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly'] as $fv=>$fl): ?>
                  <option value="<?php echo $fv; ?>" <?php echo (($rule['frequency']??'daily')===$fv)?'selected':''; ?>><?php echo $fl; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="app-label">Send time</label>
              <select name="data[NotificationRule][send_time]" class="app-select">
                <?php for ($h=0;$h<24;$h++) for ($m=0;$m<60;$m+=15) {
                  $t = sprintf('%02d:%02d', $h, $m);
                  $selected = (($rule['send_time']??'08:00')===($t.':00')||($rule['send_time']??'08:00')===$t)?'selected':'';
                  echo '<option value="' . $t . '" ' . $selected . '>' . date('g:i A', mktime($h,$m,0)) . '</option>';
                } ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="app-card" style="overflow:hidden;margin-bottom:1.25rem">
        <div class="app-card-header" style="display:flex;align-items:center;justify-content:space-between">
          <h2 style="font-size:1rem;font-weight:600;margin:0">Conditions</h2>
          <div style="display:flex;align-items:center;gap:.75rem">
            <label class="app-label" style="margin:0;white-space:nowrap">Match</label>
            <select id="conditionLogic" class="app-select" style="width:auto">
              <option value="AND" <?php echo ($conditionLogic==='AND')?'selected':''; ?>>ALL (AND)</option>
              <option value="OR" <?php echo ($conditionLogic==='OR')?'selected':''; ?>>ANY (OR)</option>
            </select>
            <label class="app-label" style="margin:0;white-space:nowrap">conditions</label>
          </div>
        </div>
        <div class="app-card-body">
          <div id="conditionsContainer" style="display:flex;flex-direction:column;gap:.75rem">
            <?php foreach ($conditions as $i => $cond): ?>
              <div class="rule-row" data-index="<?php echo $i; ?>">
                <select class="app-select cond-field" style="flex:1;min-width:140px">
                  <?php foreach ($fields as $fv=>$fl): ?>
                    <option value="<?php echo $fv; ?>" <?php echo (($cond['field']??'')===$fv)?'selected':''; ?>><?php echo $fl; ?></option>
                  <?php endforeach; ?>
                </select>
                <select class="app-select cond-op" style="flex:1;min-width:120px">
                  <?php foreach ($operators as $ov=>$ol): ?>
                    <option value="<?php echo $ov; ?>" <?php echo (($cond['operator']??'')===$ov)?'selected':''; ?>><?php echo $ol; ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" class="app-input cond-val" style="flex:1;min-width:120px" placeholder="Value" value="<?php echo h($cond['value']??''); ?>"/>
                <button type="button" onclick="removeCondition(this)" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.25rem;flex-shrink:0;display:flex;align-items:center">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" onclick="addCondition()" class="app-button app-button-soft" style="margin-top:1rem">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Condition
          </button>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:.75rem">
        <a href="/orders/notification-rules" class="app-button">Cancel</a>
        <button type="button" onclick="previewRule()" class="app-button app-button-soft">Preview Matches</button>
        <button type="submit" class="app-button app-button-primary"><?php echo $isEdit ? 'Update Rule' : 'Create Rule'; ?></button>
      </div>
    </form>

    <!-- PREVIEW PANEL -->
    <div>
      <div class="app-card" style="overflow:hidden;position:sticky;top:1.5rem">
        <div class="app-card-header">
          <h2 style="font-size:1rem;font-weight:600;margin:0">Preview</h2>
        </div>
        <div class="app-card-body" id="previewPanel" style="min-height:120px">
          <p style="font-size:.875rem;color:#94a3b8;text-align:center">Click "Preview Matches" to see matching orders.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var fieldOptions = <?php echo json_encode(array_keys($fields)); ?>;
var operatorOptions = <?php echo json_encode($operators); ?>;

function buildConditionRow(field, op, val) {
  var div = document.createElement('div');
  div.className = 'rule-row';
  var fSel = '<select class="app-select cond-field" style="flex:1;min-width:140px">';
  <?php foreach ($fields as $fv=>$fl): ?>
  fSel += '<option value="<?php echo $fv; ?>"' + (field==='<?php echo $fv; ?>'?' selected':'') + '><?php echo $fl; ?></option>';
  <?php endforeach; ?>
  fSel += '</select>';
  var oSel = '<select class="app-select cond-op" style="flex:1;min-width:120px">';
  <?php foreach ($operators as $ov=>$ol): ?>
  oSel += '<option value="<?php echo $ov; ?>"' + (op==='<?php echo $ov; ?>'?' selected':'') + '><?php echo $ol; ?></option>';
  <?php endforeach; ?>
  oSel += '</select>';
  div.innerHTML = fSel + oSel +
    '<input type="text" class="app-input cond-val" style="flex:1;min-width:120px" placeholder="Value" value="' + (val||'') + '"/>' +
    '<button type="button" onclick="removeCondition(this)" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:.25rem;flex-shrink:0;display:flex;align-items:center"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
  return div;
}
function addCondition() {
  document.getElementById('conditionsContainer').appendChild(buildConditionRow('status','equals',''));
}
function removeCondition(btn) {
  var container = document.getElementById('conditionsContainer');
  if (container.children.length > 1) btn.closest('.rule-row').remove();
}
function gatherFilters() {
  var conditions = [];
  document.querySelectorAll('.rule-row').forEach(function(row) {
    conditions.push({
      field: row.querySelector('.cond-field').value,
      operator: row.querySelector('.cond-op').value,
      value: row.querySelector('.cond-val').value
    });
  });
  return {
    condition_logic: document.getElementById('conditionLogic').value,
    conditions: conditions
  };
}
document.getElementById('ruleForm').addEventListener('submit', function() {
  document.getElementById('filtersJsonOutput').value = JSON.stringify(gatherFilters());
});
function previewRule() {
  var filters = gatherFilters();
  var panel = document.getElementById('previewPanel');
  panel.innerHTML = '<p style="text-align:center;color:#64748b;font-size:.875rem">Loading…</p>';
  fetch('/orders/notification-rules/preview', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
    body: 'filters_json=' + encodeURIComponent(JSON.stringify(filters))
  })
  .then(function(r){return r.json();})
  .then(function(data) {
    if (data.count === 0) {
      panel.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:.875rem">No orders match these conditions.</p>';
    } else {
      var html = '<p style="font-size:.875rem;color:#0f172a;font-weight:600;margin:0 0 .75rem"><strong>' + data.count + '</strong> orders match</p>';
      if (data.orders && data.orders.length) {
        html += '<table class="app-table" style="font-size:.75rem"><thead><tr><th>Order #</th><th>Customer</th><th>Amount</th></tr></thead><tbody>';
        data.orders.forEach(function(o) {
          html += '<tr><td>' + o.order_number + '</td><td>' + o.customer_name + '</td><td>$' + parseFloat(o.total_amount).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
        if (data.count > data.orders.length) html += '<p style="font-size:.75rem;color:#94a3b8;margin-top:.5rem">+ ' + (data.count - data.orders.length) + ' more</p>';
      }
      panel.innerHTML = html;
    }
  })
  .catch(function(){ panel.innerHTML = '<p style="color:#ef4444;font-size:.875rem">Error loading preview.</p>'; });
}
</script>
