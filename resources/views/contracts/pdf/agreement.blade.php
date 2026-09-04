<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->title }}</title>
    <style>
        @page { margin: 28px 32px 36px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.55;
            margin: 0;
        }

        .doc-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 3px solid #312e81;
            padding-bottom: 14px;
        }
        .doc-header td { vertical-align: middle; }
        .logo-cell { width: 120px; padding-right: 16px; }
        .logo-img { max-width: 110px; max-height: 70px; }
        .title-cell { text-align: right; }
        .doc-title {
            font-size: 19px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 0 0 6px;
            line-height: 1.25;
        }
        .doc-meta { font-size: 10px; color: #64748b; }
        .doc-meta strong { color: #334155; }

        h2 {
            font-size: 12.5px;
            font-weight: bold;
            color: #312e81;
            margin: 20px 0 10px;
            padding: 6px 10px;
            background: #eef2ff;
            border-left: 4px solid #4338ca;
        }
        h3 {
            font-size: 11px;
            font-weight: bold;
            color: #312e81;
            margin: 0 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 3px 10px 3px 0;
        }
        .info-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-value { font-size: 10.5px; color: #0f172a; }

        .parties-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 4px;
        }
        .party-box {
            width: 50%;
            vertical-align: top;
            border: 1px solid #c7d2fe;
            background: #fafbff;
            padding: 12px 14px;
        }
        .party-box.client-box {
            border-color: #ddd6fe;
            background: #fdfcff;
        }
        .party-row { margin-bottom: 7px; }
        .party-row:last-child { margin-bottom: 0; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th {
            background: #312e81;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 7px 9px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 7px 9px;
            font-size: 10px;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }

        ul { margin: 4px 0 4px 16px; padding: 0; }
        ul li { margin-bottom: 3px; }

        .highlight-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 8px 12px;
            margin-top: 6px;
            font-size: 10px;
        }

        .terms-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 10px;
        }

        .signature-section {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-top: 28px;
            page-break-inside: avoid;
        }
        .signature-cell {
            width: 50%;
            vertical-align: top;
            border: 1px solid #cbd5e1;
            padding: 14px 16px;
            background: #ffffff;
        }
        .signature-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .signature-role {
            font-size: 9.5px;
            color: #64748b;
            margin-bottom: 14px;
        }
        .signature-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .signature-script {
            font-family: DejaVu Sans, sans-serif;
            font-style: italic;
            font-size: 26px;
            color: #1e3a8a;
            line-height: 1.2;
            min-height: 34px;
            margin-bottom: 10px;
        }
        .signature-image {
            max-height: 52px;
            max-width: 200px;
            margin-bottom: 10px;
        }
        .signature-blank {
            border-bottom: 1px solid #334155;
            min-height: 28px;
            margin-bottom: 12px;
        }
        .signature-date-value {
            font-size: 10.5px;
            color: #0f172a;
            min-height: 16px;
        }

        .footer {
            position: fixed;
            bottom: -8px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $logo = $snapshot['document_logo'] ?? '';
        $providerSignature = $snapshot['provider_signature'] ?? config('contracts.provider_signature', 'Ajay O');
        $providerSignatureDate = $snapshot['provider_signature_date'] ?? $contract->effective_date->toDateString();
        $providerDateFormatted = \Illuminate\Support\Carbon::parse($providerSignatureDate)->format('F j, Y');
    @endphp

    <table class="doc-header">
        <tr>
            <td class="logo-cell">
                @if (! empty($logo) && str_starts_with((string) $logo, 'data:image'))
                    <img src="{{ $logo }}" class="logo-img" alt="Logo">
                @else
                    <div style="font-size: 11px; font-weight: bold; color: #4338ca;">{{ $snapshot['provider']['name'] ?? config('app.name') }}</div>
                @endif
            </td>
            <td class="title-cell">
                <div class="doc-title">{{ $contract->title }}</div>
                <div class="doc-meta">
                    <strong>Contract No:</strong> {{ $contract->contract_number }} &nbsp;|&nbsp;
                    <strong>Effective:</strong> {{ $contract->effective_date->format('F j, Y') }}
                    @if ($contract->start_date)
                        &nbsp;|&nbsp; <strong>Period:</strong> {{ $contract->start_date->format('M j, Y') }} – {{ $contract->end_date?->format('M j, Y') ?? 'Ongoing' }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <h2>Agreement Details</h2>
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-label">Contract Type</div>
                <div class="info-value">{{ $contract->contract_type->label() }}</div>
            </td>
            <td>
                <div class="info-label">Country / Version</div>
                <div class="info-value">{{ $contract->country->label() }} · {{ $contract->currency }}</div>
            </td>
        </tr>
    </table>

    <h2>Parties to this Agreement</h2>
    <table class="parties-table">
        <tr>
            <td class="party-box">
                <h3>Service Provider</h3>
                <div class="party-row">
                    <div class="info-label">Company</div>
                    <div class="info-value"><strong>{{ $snapshot['provider']['name'] ?? '' }}</strong></div>
                </div>
                <div class="party-row">
                    <div class="info-label">Authorized Person</div>
                    <div class="info-value">{{ $snapshot['provider']['authorized_person'] ?? '—' }}</div>
                </div>
                <div class="party-row">
                    <div class="info-label">Phone / Email</div>
                    <div class="info-value">
                        {{ $snapshot['provider']['phone'] ?? '—' }}<br>
                        {{ $snapshot['provider']['email'] ?? '—' }}
                    </div>
                </div>
                <div class="party-row">
                    <div class="info-label">Website</div>
                    <div class="info-value">{{ $snapshot['provider']['website'] ?? '—' }}</div>
                </div>
                @if (! empty($snapshot['provider']['address']))
                    <div class="party-row">
                        <div class="info-label">Address</div>
                        <div class="info-value">{{ $snapshot['provider']['address'] }}</div>
                    </div>
                @endif
            </td>
            <td class="party-box client-box">
                <h3>Client</h3>
                <div class="party-row">
                    <div class="info-label">Company</div>
                    <div class="info-value"><strong>{{ $snapshot['client']['name'] ?? $contract->company->name }}</strong></div>
                </div>
                <div class="party-row">
                    <div class="info-label">Authorized Person</div>
                    <div class="info-value">{{ $snapshot['client']['authorized_person'] ?? '—' }}</div>
                </div>
                <div class="party-row">
                    <div class="info-label">Phone / Email</div>
                    <div class="info-value">
                        {{ $snapshot['client']['phone'] ?? '—' }}<br>
                        {{ $snapshot['client']['email'] ?? '—' }}
                    </div>
                </div>
                <div class="party-row">
                    <div class="info-label">Website</div>
                    <div class="info-value">{{ $snapshot['client']['website'] ?? '—' }}</div>
                </div>
                @if (! empty($snapshot['client']['address']))
                    <div class="party-row">
                        <div class="info-label">Address</div>
                        <div class="info-value">{{ $snapshot['client']['address'] }}</div>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <h2>Service Plan</h2>
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-label">Monthly Service Fee</div>
                <div class="info-value"><strong>{{ $contract->currency }} {{ number_format((float) ($snapshot['service_plan']['monthly_fee'] ?? 0), 2) }}</strong></div>
            </td>
            <td>
                <div class="info-label">Billing Frequency</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', (string) ($snapshot['service_plan']['billing_frequency'] ?? 'monthly'))) }}</div>
            </td>
        </tr>
    </table>

    @if (! empty($snapshot['deliverables']))
        <h2>Monthly Deliverables</h2>
        <table class="data">
            <thead><tr><th style="width: 8%;">Qty</th><th style="width: 28%;">Service</th><th>Description</th></tr></thead>
            <tbody>
            @foreach ($snapshot['deliverables'] as $item)
                <tr>
                    <td>{{ $item['quantity'] ?? '—' }}</td>
                    <td><strong>{{ $item['name'] ?? '' }}</strong></td>
                    <td>{{ $item['description'] ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($snapshot['extra_work']))
        <h2>Extra Work / Additional Services</h2>
        <table class="data">
            <thead><tr><th>Description</th><th style="width: 22%;">Additional Fee</th><th style="width: 18%;">Affects Monthly Fee</th></tr></thead>
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
        <h2>Client Information &amp; Requirements</h2>
        <ul>
            @foreach ($snapshot['requirements'] as $item)
                <li>{{ $item['label'] ?? '' }}@if(!empty($item['value'])): <strong>{{ $item['value'] }}</strong>@endif</li>
            @endforeach
        </ul>
    @endif

    <h2>Campaign Objective</h2>
    <p>{{ ucfirst(str_replace('_', ' ', (string) ($snapshot['campaign_objective']['type'] ?? ''))) }}@if(!empty($snapshot['campaign_objective']['custom'])) — {{ $snapshot['campaign_objective']['custom'] }}@endif</p>

    @if (! empty($snapshot['lead_pricing']))
        <h2>Lead Pricing</h2>
        <table class="data">
            <thead><tr><th>Lead Type</th><th style="width: 20%;">Cost Per Lead</th><th>Description</th></tr></thead>
            <tbody>
            @foreach ($snapshot['lead_pricing'] as $row)
                <tr>
                    <td><strong>{{ $row['lead_type'] ?? '' }}</strong></td>
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
        <div class="highlight-box">
            <strong>Example / Estimated Lead Invoice:</strong>
            {{ $exampleQty }} × {{ $contract->currency }} {{ number_format((float) $exampleCpl, 2) }}
            = <strong>{{ $contract->currency }} {{ number_format((float) $exampleQty * (float) $exampleCpl, 2) }}</strong>
            <em style="color:#64748b;"> (illustrative only — not an actual invoice)</em>
        </div>
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
        <h2>Additional Terms &amp; Conditions</h2>
        <div class="terms-block">{!! nl2br(e($snapshot['custom_terms'])) !!}</div>
    @endif

    <table class="signature-section">
        <tr>
            <td class="signature-cell">
                <h3>Client</h3>
                <div class="signature-name">{{ $snapshot['client']['name'] ?? $contract->company->name }}</div>
                <div class="signature-role">Authorized: {{ $snapshot['client']['authorized_person'] ?? '—' }}</div>

                <div class="signature-label">Signature</div>
                @if ($clientSignature)
                    @if (($clientSignature['signature_type'] ?? '') === 'typed')
                        <div class="signature-script">{{ $clientSignature['signature_data'] }}</div>
                    @elseif (str_starts_with((string) ($clientSignature['signature_data'] ?? ''), 'data:image'))
                        <img src="{{ $clientSignature['signature_data'] }}" class="signature-image" alt="Client signature">
                    @else
                        <div class="signature-script">Signed electronically</div>
                    @endif
                    <div class="signature-label">Date</div>
                    <div class="signature-date-value">{{ isset($clientSignature['signed_at']) ? \Illuminate\Support\Carbon::parse($clientSignature['signed_at'])->format('F j, Y') : now()->format('F j, Y') }}</div>
                @elseif ($includeSignaturePlaceholders)
                    <div class="signature-blank"></div>
                    <div class="signature-label">Date</div>
                    <div class="signature-blank"></div>
                @endif
            </td>
            <td class="signature-cell">
                <h3>Service Provider</h3>
                <div class="signature-name">{{ $snapshot['provider']['name'] ?? '' }}</div>
                <div class="signature-role">Authorized: {{ $snapshot['provider']['authorized_person'] ?? '—' }}</div>

                <div class="signature-label">Signature</div>
                <div class="signature-script">{{ $providerSignature }}</div>
                <div class="signature-label">Date</div>
                <div class="signature-date-value">{{ $providerDateFormatted }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">{{ $snapshot['provider']['name'] ?? config('app.name') }} · Generated by VSP CRM · Page <span class="page-number"></span></div>
</body>
</html>
