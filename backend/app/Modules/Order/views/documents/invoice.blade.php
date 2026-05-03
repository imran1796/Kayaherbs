@php
    $invoice = $order->invoice;
    $billing = $invoice->billing_address ?? [];
    $shipping = $invoice->shipping_address ?? [];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; margin: 32px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 24px; }
        .button { background: #0d6efd; border: 0; border-radius: 4px; color: #fff; cursor: pointer; padding: 8px 12px; text-decoration: none; }
        .muted { color: #6b7280; }
        .header { align-items: flex-start; display: flex; justify-content: space-between; margin-bottom: 28px; }
        .grid { display: grid; gap: 24px; grid-template-columns: 1fr 1fr; margin-bottom: 24px; }
        h1, h2, h3 { margin: 0 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; }
        th { background: #f9fafb; font-size: 12px; text-transform: uppercase; }
        .text-end { text-align: right; }
        .totals { margin-left: auto; margin-top: 18px; width: 320px; }
        .totals td { border: 0; padding: 6px 8px; }
        .total-row td { border-top: 1px solid #111827; font-weight: 700; }
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
            <h1>Invoice</h1>
            <div class="muted">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="text-end">
            <div><strong>Order:</strong> {{ $order->order_number }}</div>
            <div><strong>Status:</strong> {{ $invoice->status }}</div>
            <div><strong>Issued:</strong> {{ $invoice->issued_at }}</div>
        </div>
    </div>

    <div class="grid">
        <section>
            <h3>Bill To</h3>
            <div>{{ $billing['recipient_name'] ?? $order->customer?->name }}</div>
            <div>{{ $billing['phone'] ?? $order->customer?->phone }}</div>
            <div>{{ $billing['address_line_1'] ?? '' }}</div>
            <div>{{ collect([$billing['city'] ?? null, $billing['state'] ?? null, $billing['postal_code'] ?? null, $billing['country'] ?? null])->filter()->join(', ') }}</div>
        </section>
        <section>
            <h3>Ship To</h3>
            <div>{{ $shipping['recipient_name'] ?? $order->customer?->name }}</div>
            <div>{{ $shipping['phone'] ?? $order->customer?->phone }}</div>
            <div>{{ $shipping['address_line_1'] ?? '' }}</div>
            <div>{{ collect([$shipping['city'] ?? null, $shipping['state'] ?? null, $shipping['postal_code'] ?? null, $shipping['country'] ?? null])->filter()->join(', ') }}</div>
        </section>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        <div class="muted">{{ $item->variant_name }}</div>
                    </td>
                    <td>{{ $item->sku }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">{{ $item->unit_price }}</td>
                    <td class="text-end">{{ $item->line_total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-end">{{ $invoice->subtotal }} {{ $invoice->currency }}</td>
        </tr>
        <tr>
            <td>Shipping</td>
            <td class="text-end">{{ $invoice->shipping_total }} {{ $invoice->currency }}</td>
        </tr>
        <tr class="total-row">
            <td>Grand total</td>
            <td class="text-end">{{ $invoice->grand_total }} {{ $invoice->currency }}</td>
        </tr>
    </table>
</body>
</html>
