@php
    $packingSlip = $order->packingSlip;
    $shipping = $packingSlip->shipping_address ?? [];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing Slip {{ $packingSlip->packing_slip_number }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; margin: 32px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 24px; }
        .button { background: #0d6efd; border: 0; border-radius: 4px; color: #fff; cursor: pointer; padding: 8px 12px; text-decoration: none; }
        .muted { color: #6b7280; }
        .header { align-items: flex-start; display: flex; justify-content: space-between; margin-bottom: 28px; }
        .address { border: 1px solid #e5e7eb; margin-bottom: 24px; padding: 16px; }
        h1, h2, h3 { margin: 0 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
        th { background: #f9fafb; font-size: 12px; text-transform: uppercase; }
        .text-end { text-align: right; }
        @media print {
            body { margin: 18mm; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="button" href="{{ route('admin.orders.show', ['id' => $order->id]) }}">Back</a>
        <button class="button" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>Packing Slip</h1>
            <div class="muted">{{ $packingSlip->packing_slip_number }}</div>
        </div>
        <div class="text-end">
            <div><strong>Order:</strong> {{ $order->order_number }}</div>
            <div><strong>Status:</strong> {{ $packingSlip->status }}</div>
            <div><strong>Generated:</strong> {{ $packingSlip->generated_at }}</div>
        </div>
    </div>

    <section class="address">
        <h3>Ship To</h3>
        <div>{{ $shipping['recipient_name'] ?? $order->customer?->name }}</div>
        <div>{{ $shipping['phone'] ?? $order->customer?->phone }}</div>
        <div>{{ $shipping['address_line_1'] ?? '' }}</div>
        <div>{{ collect([$shipping['city'] ?? null, $shipping['state'] ?? null, $shipping['postal_code'] ?? null, $shipping['country'] ?? null])->filter()->join(', ') }}</div>
    </section>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th class="text-end">Qty</th>
                <th>Picked</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($packingSlip->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item['product_name'] ?? '' }}</strong>
                        <div class="muted">{{ $item['variant_name'] ?? '' }}</div>
                    </td>
                    <td>{{ $item['sku'] ?? '' }}</td>
                    <td class="text-end">{{ $item['quantity'] ?? '' }}</td>
                    <td>[ &nbsp; ]</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
