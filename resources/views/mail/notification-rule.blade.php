<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; padding: 24px; background: #f4f7fb; color: #122033; }
        .wrap { max-width: 760px; margin: 0 auto; background: #ffffff; border: 1px solid #dbe5f0; border-radius: 18px; overflow: hidden; }
        .head { padding: 24px 28px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); border-bottom: 1px solid #dbe5f0; }
        .head h1 { margin: 0 0 8px; font-size: 22px; }
        .head p { margin: 0; font-size: 13px; color: #6b7a90; }
        .body { padding: 24px 28px; }
        .stats { display: flex; gap: 12px; margin-bottom: 22px; }
        .stat { flex: 1; border: 1px solid #dbe5f0; border-radius: 14px; padding: 14px; background: #fbfdff; }
        .stat-label { font-size: 11px; color: #6b7a90; text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em; }
        .stat-value { margin-top: 8px; font-size: 24px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #dbe5f0; vertical-align: top; }
        th { font-size: 11px; color: #6b7a90; text-transform: uppercase; letter-spacing: 0.04em; background: #fbfdff; }
        .note { margin-top: 16px; font-size: 12px; color: #6b7a90; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <h1>{{ $rule->name ?: 'Order notification' }}</h1>
            <p>Generated {{ now()->format('d M Y H:i') }} for {{ $rule->recipient_email }}</p>
        </div>

        <div class="body">
            <div class="stats">
                <div class="stat">
                    <div class="stat-label">Matches</div>
                    <div class="stat-value">{{ number_format($totalMatches) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Shown In Email</div>
                    <div class="stat-value">{{ number_format($orders->count()) }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Frequency</div>
                    <div class="stat-value">{{ ucfirst($rule->frequency) }}</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Description</th>
                        <th>Supplier</th>
                        <th>Approved Qty</th>
                        <th>Subtotal</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td>{{ $order->order_number ?: '—' }}</td>
                            <td>{{ $order->product_description ?: '—' }}</td>
                            <td>{{ $order->supplier_id ?: '—' }}</td>
                            <td>{{ number_format((float) $order->approved_qty, 2) }}</td>
                            <td>£{{ number_format((float) $order->sub_total, 2) }}</td>
                            <td>{{ $order->orderdate?->format('d M Y H:i') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="note">
                @if($totalMatches > $orders->count())
                    This email shows the first {{ number_format($orders->count()) }} rows. The full result set is attached as CSV.
                @else
                    The full result set is attached as CSV.
                @endif
            </p>
        </div>
    </div>
</body>
</html>
