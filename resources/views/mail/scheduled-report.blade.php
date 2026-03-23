<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <style>
        body  { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #111827; margin: 0; padding: 0; background: #f9fafb; }
        .wrap { max-width: 680px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.08); }
        .hdr  { background: #1d4ed8; padding: 28px 32px; color: #fff; }
        .hdr h1 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
        .hdr p  { margin: 0; font-size: 13px; opacity: .8; }
        .body { padding: 28px 32px; }
        .stats { display: flex; gap: 16px; margin-bottom: 28px; }
        .stat  { flex: 1; background: #f3f4f6; border-radius: 8px; padding: 16px; text-align: center; }
        .stat-num { font-size: 28px; font-weight: 700; color: #1d4ed8; }
        .stat-lbl { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f3f4f6; padding: 8px 10px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .ftr { background: #f9fafb; padding: 20px 32px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .pill { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 6px; font-size: 12px; margin: 2px 4px 2px 0; }
    </style>
</head>
<body>
<div class="wrap">

    {{-- Header --}}
    <div class="hdr">
        <h1>{{ $report->name ?: 'Order History Report' }}</h1>
        <p>Generated {{ \Carbon\Carbon::now()->format('l, d F Y \a\t H:i') }}</p>
    </div>

    <div class="body">

        {{-- Stats --}}
        <div class="stats">
            <div class="stat">
                <div class="stat-num">{{ number_format($orders->count()) }}</div>
                <div class="stat-lbl">Total Orders</div>
            </div>
            <div class="stat">
                <div class="stat-num">£{{ number_format($orders->sum(fn($o) => (float)$o->price * (float)$o->quantity), 2) }}</div>
                <div class="stat-lbl">Total Amount</div>
            </div>
            <div class="stat">
                <div class="stat-num">{{ ucfirst($report->frequency) }}</div>
                <div class="stat-lbl">Report Frequency</div>
            </div>
        </div>

        {{-- Applied filters --}}
        @php
            $filters = $report->filters_json;
            $dateLabels = ['today'=>'Today','yesterday'=>'Yesterday','last3days'=>'Last 3 Days','last7days'=>'Last 7 Days','thismonth'=>'This Month','lastmonth'=>'Last Month'];
        @endphp
        @if(!empty($filters))
            <p class="section-title" style="margin-bottom:8px;">Applied Filters</p>
            <div style="margin-bottom:24px;">
                @if(($filters['dateFilter'] ?? 'all') !== 'all')
                    <span class="pill">{{ $dateLabels[$filters['dateFilter']] ?? $filters['dateFilter'] }}</span>
                @endif
                @if(!empty($filters['search']))
                    <span class="pill">Search: {{ $filters['search'] }}</span>
                @endif
                @foreach($filters['supplierFilter'] ?? [] as $s)
                    <span class="pill">{{ $s }}</span>
                @endforeach
                @foreach($filters['categoryFilter'] ?? [] as $c)
                    <span class="pill">{{ $c }}</span>
                @endforeach
                @foreach($filters['stockFilter'] ?? [] as $st)
                    <span class="pill">{{ $st }}</span>
                @endforeach
            </div>
        @endif

        {{-- Order preview table (first 15 rows) --}}
        @if($orders->isNotEmpty())
            <p class="section-title">Order Preview
                @if($orders->count() > 15)
                    <span style="font-weight:400;text-transform:none;color:#9ca3af;">(showing first 15 of {{ number_format($orders->count()) }} — full list in attached CSV)</span>
                @endif
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>PIP Code</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Sub Total</th>
                        <th>Supplier</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders->take(15) as $order)
                        <tr>
                            <td style="font-family:monospace;">{{ $order->order_number ?? $order->ordernumber ?? '—' }}</td>
                            <td style="font-family:monospace;font-weight:600;">{{ $order->pipcode ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($order->product_description ?? '', 40) }}</td>
                            <td>{{ number_format((float)$order->quantity) }}</td>
                            <td>£{{ number_format((float)$order->price, 4) }}</td>
                            <td>£{{ number_format((float)$order->price * (float)$order->quantity, 2) }}</td>
                            <td>{{ $order->supplier_id ?? '—' }}</td>
                            <td>
                                @if($order->response)
                                    <span class="badge" style="background:{{ str_contains(strtolower($order->response),'stock')?'#d1fae5':'#fef3c7' }};color:{{ str_contains(strtolower($order->response),'stock')?'#065f46':'#92400e' }};">
                                        {{ \Illuminate\Support\Str::limit($order->response, 25) }}
                                    </span>
                                @else —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#6b7280;font-size:14px;text-align:center;padding:32px 0;">No new orders matched your filters since the last report.</p>
        @endif

        <p style="font-size:13px;color:#6b7280;margin-top:24px;">
            📎 The full order list is attached as a CSV file.
        </p>
    </div>

    <div class="ftr">
        This report was automatically generated by your Order Management System.<br>
        Scheduled: <strong>{{ ucfirst($report->frequency) }}</strong> at <strong>{{ $report->send_time }}</strong>.
        To manage your scheduled reports, log in to the system.
    </div>
</div>
</body>
</html>
