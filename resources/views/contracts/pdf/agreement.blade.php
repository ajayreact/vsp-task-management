<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 6px; color: #312e81; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #4338ca; border-bottom: 1px solid #c7d2fe; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 12px 0 6px; }
        .header { border-bottom: 2px solid #4338ca; padding-bottom: 12px; margin-bottom: 18px; }
        .muted { color: #6b7280; }
        .grid-2 { width: 100%; }
        .grid-2 td { vertical-align: top; width: 50%; padding: 4px 8px 4px 0; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        table.data th { background: #eef2ff; }
        ul { margin: 6px 0 6px 18px; padding: 0; }
        .signature-box { margin-top: 24px; width: 100%; }
        .signature-box td { width: 50%; vertical-align: top; padding-top: 12px; }
        .line { border-bottom: 1px solid #111827; min-height: 28px; margin: 8px 0; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 9px; color: #9ca3af; }
        .page-number:after { content: counter(page); }
        .signature-image { max-height: 60px; max-width: 220px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $contract->title }}</h1>
        <div class="muted">Contract No: {{ $contract->contract_number }} · Effective Date: {{ $contract->effective_date->format('F j, Y') }}</div>
    </div>

    <h2>Agreement Details</h2>
    <table class="grid-2">
        <tr>
            <td><strong>Contract Type:</strong><br>{{ $contract->contract_type->label() }}</td>
            <td><strong>Country / Version:</strong><br>{{ $contract->country->label() }}</td>
        </tr>
        <tr>
            <td><strong>Start Date:</strong><br>{{ $contract->start_date?->format('F j, Y') ?? '—' }}</td>
            <td><strong>End Date:</strong><br>{{ $contract->end_date?->format('F j, Y') ?? '—' }}</td>
        </tr>
    </table>

    <h2>Service Provider</h2>
    <table class="grid-2">
        <tr>
            <td><strong>Name:</strong><br>{{ $snapshot['provider']['name'] ?? '' }}</td>
            <td><strong>Authorized Person:</strong><br>{{ $snapshot['provider']['authorized_person'] ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Phone:</strong><br>{{ $snapshot['provider']['phone'] ?? '' }}</td>
            <td><strong>Email:</strong><br>{{ $snapshot['provider']['email'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Website:</strong><br>{{ $snapshot['provider']['website'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Address:</strong><br>{{ $snapshot['provider']['address'] ?? '' }}</td>
        </tr>
    </table>

    <h2>Client Details</h2>
    <table class="grid-2">
        <tr>
            <td><strong>Client / Company:</strong><br>{{ $snapshot['client']['name'] ?? $contract->company->name }}</td>
            <td><strong>Authorized Person:</strong><br>{{ $snapshot['client']['authorized_person'] ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Phone:</strong><br>{{ $snapshot['client']['phone'] ?? '' }}</td>
            <td><strong>Email:</strong><br>{{ $snapshot['client']['email'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Website:</strong><br>{{ $snapshot['client']['website'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Address:</strong><br>{{ $snapshot['client']['address'] ?? '' }}</td>
        </tr>
    </table>

    <h2>Service Plan</h2>
    <table class="grid-2">
        <tr>
            <td><strong>Monthly Service Fee:</strong><br>{{ $contract->currency }} {{ number_format((float) ($snapshot['service_plan']['monthly_fee'] ?? 0), 2) }}</td>
            <td><strong>Billing Frequency:</strong><br>{{ ucfirst(str_replace('_', ' ', (string) ($snapshot['service_plan']['billing_frequency'] ?? 'monthly'))) }}</td>
        </tr>
    </table>

    @if (! empty($snapshot['deliverables']))
        <h2>Monthly Deliverables</h2>
        <table class="data">
            <thead><tr><th>Qty</th><th>Service</th><th>Description</th></tr></thead>
            <tbody>
            @foreach ($snapshot['deliverables'] as $item)
                <tr>
                    <td>{{ $item['quantity'] ?? '' }}</td>
                    <td>{{ $item['name'] ?? '' }}</td>
                    <td>{{ $item['description'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($snapshot['extra_work']))
        <h2>Extra Work / Additional Services</h2>
        <table class="data">
            <thead><tr><th>Description</th><th>Additional Fee</th><th>Affects Monthly Fee</th></tr></thead>
            <tbody>
            @foreach ($snapshot['extra_work'] as $item)
                <tr>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td>{{ ($item['currency'] ?? $contract->currency) }} {{ number_format((float) ($item['fee'] ?? 0), 2) }}</td>
                    <td>{{ ! empty($item['affects_monthly_fee']) ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($snapshot['requirements']))
        <h2>Client Information & Requirements</h2>
        <ul>
            @foreach ($snapshot['requirements'] as $item)
                <li>{{ $item['label'] ?? '' }}@if(!empty($item['value'])): {{ $item['value'] }}@endif</li>
            @endforeach
        </ul>
    @endif

    <h2>Campaign Objective</h2>
    <p>{{ ucfirst(str_replace('_', ' ', (string) ($snapshot['campaign_objective']['type'] ?? ''))) }}@if(!empty($snapshot['campaign_objective']['custom'])) — {{ $snapshot['campaign_objective']['custom'] }}@endif</p>

    @if (! empty($snapshot['lead_pricing']))
        <h2>Lead Pricing</h2>
        <table class="data">
            <thead><tr><th>Lead Type</th><th>Cost Per Lead</th><th>Description</th></tr></thead>
            <tbody>
            @foreach ($snapshot['lead_pricing'] as $row)
                <tr>
                    <td>{{ $row['lead_type'] ?? '' }}</td>
                    <td>{{ ($row['currency'] ?? $contract->currency) }} {{ number_format((float) ($row['cpl'] ?? 0), 2) }}</td>
                    <td>{{ $row['description'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @php
        $exampleQty = $snapshot['lead_example']['quantity'] ?? null;
        $exampleCpl = $snapshot['lead_example']['cpl'] ?? null;
    @endphp
    @if ($exampleQty && $exampleCpl)
        <h3>Example / Estimated Lead Invoice</h3>
        <p>{{ $exampleQty }} × {{ $contract->currency }} {{ number_format((float) $exampleCpl, 2) }} = {{ $contract->currency }} {{ number_format((float) $exampleQty * (float) $exampleCpl, 2) }}</p>
    @endif

    <h2>Payment Terms</h2>
    <ul>
        @foreach (($snapshot['payment_terms'] ?? []) as $term)
            @if (is_string($term) && trim($term) !== '')
                <li>{{ $term }}</li>
            @endif
        @endforeach
    </ul>

    @if (! empty($snapshot['responsibilities']))
        <h2>Client Responsibilities</h2>
        <ul>
            @foreach ($snapshot['responsibilities'] as $item)
                <li>{{ is_array($item) ? ($item['text'] ?? '') : $item }}</li>
            @endforeach
        </ul>
    @endif

    @if (! empty($snapshot['custom_terms']))
        <h2>Additional Terms & Conditions</h2>
        <div>{!! nl2br(e($snapshot['custom_terms'])) !!}</div>
    @endif

    <table class="signature-box">
        <tr>
            <td>
                <h3>CLIENT</h3>
                <div><strong>{{ $snapshot['client']['name'] ?? $contract->company->name }}</strong></div>
                <div>Authorized Person: {{ $snapshot['client']['authorized_person'] ?? '' }}</div>
                @if ($clientSignature)
                    <div class="line">
                        @if (($clientSignature['signature_type'] ?? '') === 'typed')
                            {{ $clientSignature['signature_data'] }}
                        @elseif (str_starts_with((string) ($clientSignature['signature_data'] ?? ''), 'data:image'))
                            <img src="{{ $clientSignature['signature_data'] }}" class="signature-image" alt="Signature">
                        @else
                            Signed electronically
                        @endif
                    </div>
                    <div>Date: {{ isset($clientSignature['signed_at']) ? \Illuminate\Support\Carbon::parse($clientSignature['signed_at'])->format('F j, Y') : now()->format('F j, Y') }}</div>
                @elseif ($includeSignaturePlaceholders)
                    <div class="line">Signature: ______________________</div>
                    <div>Date: ___________________________</div>
                @endif
            </td>
            <td>
                <h3>SERVICE PROVIDER</h3>
                <div><strong>{{ $snapshot['provider']['name'] ?? '' }}</strong></div>
                <div>Authorized Person: {{ $snapshot['provider']['authorized_person'] ?? '' }}</div>
                <div class="line">Signature: ______________________</div>
                <div>Date: ___________________________</div>
            </td>
        </tr>
    </table>

    <div class="footer">Generated by {{ config('app.name') }} · Page <span class="page-number"></span></div>
</body>
</html>
