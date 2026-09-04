<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $contract->title }}</title>
    <style>
        @page { margin: 24px 28px 32px; background-color: {{ config('contracts.pdf.background', '#FFFDF5') }}; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #111827;
            line-height: 1.55;
            margin: 0;
            background-color: {{ config('contracts.pdf.background', '#FFFDF5') }};
        }

        .banner-wrap {
            width: 100%;
            border: 1px solid #111827;
            margin-bottom: 18px;
            background: {{ config('contracts.pdf.background', '#FFFDF5') }};
        }
        .banner-table { width: 100%; border-collapse: collapse; }
        .banner-table td { vertical-align: middle; padding: 10px 12px; }
        .banner-logo { width: 28%; }
        .logo-img { max-width: 120px; max-height: 72px; }
        .banner-title { width: 44%; }
        .doc-title {
            font-size: 17px;
            font-weight: bold;
            color: {{ config('contracts.pdf.heading', '#1A3A5F') }};
            margin: 0;
            line-height: 1.25;
        }
        .doc-meta {
            margin-top: 6px;
            font-size: 9px;
            color: #475569;
        }
        .doc-meta strong { color: {{ config('contracts.pdf.heading', '#1A3A5F') }}; }
        .banner-accent { width: 28%; padding: 0 !important; }
        .accent-table { width: 100%; height: 78px; border-collapse: collapse; }
        .accent-blue {
            background: {{ config('contracts.pdf.accent_blue', '#1A3A5F') }};
            width: 58%;
        }
        .accent-red {
            background: {{ config('contracts.pdf.accent_red', '#E31E24') }};
            width: 42%;
        }

        h2 {
            font-size: 12px;
            font-weight: bold;
            color: {{ config('contracts.pdf.heading', '#1A3A5F') }};
            margin: 18px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        h2.section-number {
            font-size: 13px;
            margin-top: 22px;
        }
        h3 {
            font-size: 10.5px;
            font-weight: bold;
            color: {{ config('contracts.pdf.heading', '#1A3A5F') }};
            margin: 0 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.35px;
        }

        .intro-text { margin: 0 0 8px; }

        .parties-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 6px;
        }
        .party-box {
            width: 50%;
            vertical-align: top;
            border: 1px solid #d4d4d8;
            background: #ffffff;
            padding: 12px 14px;
        }
        .party-row { margin-bottom: 7px; }
        .party-row:last-child { margin-bottom: 0; }
        .info-label {
            font-size: 8.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-value { font-size: 10.5px; color: #0f172a; }

        .fee-intro { margin: 8px 0 4px; }
        .fee-amount {
            font-size: 22px;
            font-weight: bold;
            color: {{ config('contracts.pdf.price_green', '#00A651') }};
            margin: 4px 0 14px;
            line-height: 1.2;
        }
        .fee-period {
            font-size: 14px;
            font-weight: bold;
            color: {{ config('contracts.pdf.heading', '#1A3A5F') }};
        }

        .deliverable-item { margin-bottom: 12px; page-break-inside: avoid; }
        .deliverable-title {
            font-size: 11px;
            font-weight: bold;
            color: {{ config('contracts.pdf.heading', '#1A3A5F') }};
            margin-bottom: 2px;
        }
        .deliverable-desc {
            font-size: 10px;
            color: #334155;
            margin-left: 14px;
        }
        .deliverable-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: {{ config('contracts.pdf.accent_blue', '#1A3A5F') }};
            margin-right: 6px;
        }

        ul.scope-list { margin: 6px 0 10px 18px; padding: 0; }
        ul.scope-list li { margin-bottom: 4px; }

        .revised-fee-label { margin: 10px 0 4px; }
        .revised-fee-amount {
            font-size: 20px;
            font-weight: bold;
            color: {{ config('contracts.pdf.price_green', '#00A651') }};
            margin: 2px 0 10px;
        }
        .scope-footer { margin-top: 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table.data th {
            background: {{ config('contracts.pdf.heading', '#1A3A5F') }};
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 7px 9px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 7px 9px;
            font-size: 10px;
            background: #ffffff;
        }

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
            background: #ffffff;
            border: 1px solid #e5e7eb;
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
            color: #1A3A5F;
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
        if (($logo === '' || $logo === null) && config('contracts.default_logo')) {
            $defaultLogoPath = public_path(config('contracts.default_logo'));
            if (is_file($defaultLogoPath)) {
                $extension = strtolower(pathinfo($defaultLogoPath, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
                $logo = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($defaultLogoPath));
            }
        }

        $providerSignature = $snapshot['provider_signature'] ?? config('contracts.provider_signature', 'Ajay O');
        $providerSignatureDate = $snapshot['provider_signature_date'] ?? $contract->effective_date->toDateString();
        $providerDateFormatted = \Illuminate\Support\Carbon::parse($providerSignatureDate)->format('F j, Y');

        $currencySymbol = match ($contract->currency) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $contract->currency.' ',
        };

        $monthlyFee = (float) ($snapshot['service_plan']['monthly_fee'] ?? 0);
        $monthlyFeeFormatted = fmod($monthlyFee, 1.0) === 0.0
            ? number_format($monthlyFee, 0)
            : number_format($monthlyFee, 2);

        $extraWorkScope = $snapshot['extra_work_scope'] ?? [];
        $revisedFee = $extraWorkScope['revised_fee'] ?? '';
        $revisedFeeFormatted = $revisedFee !== '' && $revisedFee !== null
            ? (fmod((float) $revisedFee, 1.0) === 0.0
                ? number_format((float) $revisedFee, 0)
                : number_format((float) $revisedFee, 2))
            : null;
    @endphp

    <div class="banner-wrap">
        <table class="banner-table">
            <tr>
                <td class="banner-logo">
                    @if (! empty($logo) && str_starts_with((string) $logo, 'data:image'))
                        <img src="{{ $logo }}" class="logo-img" alt="Logo">
                    @else
                        <div style="font-size: 12px; font-weight: bold; color: {{ config('contracts.pdf.heading', '#1A3A5F') }};">
                            {{ $snapshot['provider']['name'] ?? config('contracts.provider.name') }}
                        </div>
                    @endif
                </td>
                <td class="banner-title">
                    <div class="doc-title">{{ $contract->title }}</div>
                    <div class="doc-meta">
                        <strong>Contract No:</strong> {{ $contract->contract_number }} &nbsp;|&nbsp;
                        <strong>Effective:</strong> {{ $contract->effective_date->format('F j, Y') }}
                    </div>
                </td>
                <td class="banner-accent">
                    <table class="accent-table">
                        <tr>
                            <td class="accent-blue"></td>
                            <td class="accent-red"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <h2>Agreement Details</h2>
    <p class="intro-text">
        This {{ $contract->contract_type->label() }} (&ldquo;Agreement&rdquo;) is entered into on
        <strong>{{ $contract->effective_date->format('F j, Y') }}</strong>
        between the Service Provider and Client listed below.
    </p>

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
            <td class="party-box">
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

    <p class="fee-intro">The Client will receive the following services for a monthly service fee of:</p>
    <div class="fee-amount">
        {{ $currencySymbol }}{{ $monthlyFeeFormatted }}
        <span class="fee-period"> / MONTH</span>
    </div>

    @if (! empty($snapshot['deliverables']))
        <h2>Monthly Deliverables</h2>
        @foreach ($snapshot['deliverables'] as $item)
            @php
                $qty = trim((string) ($item['quantity'] ?? ''));
                $name = trim((string) ($item['name'] ?? ''));
                $title = trim(($qty !== '' ? $qty.' ' : '').$name);
            @endphp
            <div class="deliverable-item">
                <div class="deliverable-title">
                    <span class="deliverable-dot"></span>{{ $title }}
                </div>
                @if (! empty($item['description']))
                    <div class="deliverable-desc">{{ $item['description'] }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if (! empty($extraWorkScope))
        <h2 class="section-number">2. Extra Work</h2>
        @if (! empty($extraWorkScope['intro']))
            <p class="intro-text">{{ $extraWorkScope['intro'] }}</p>
        @endif
        @if (! empty($extraWorkScope['items']))
            <ul class="scope-list">
                @foreach ($extraWorkScope['items'] as $scopeItem)
                    @if (trim((string) $scopeItem) !== '')
                        <li>{{ $scopeItem }}</li>
                    @endif
                @endforeach
            </ul>
        @endif
        @if (! empty($extraWorkScope['revised_fee_label']))
            <p class="revised-fee-label">{{ $extraWorkScope['revised_fee_label'] }}</p>
        @endif
        @if ($revisedFeeFormatted !== null)
            <div class="revised-fee-amount">{{ $currencySymbol }}{{ $revisedFeeFormatted }} <span class="fee-period">/ MONTH</span></div>
        @endif
        @if (! empty($extraWorkScope['footer']))
            <p class="scope-footer">{{ $extraWorkScope['footer'] }}</p>
        @endif
    @endif

    @if (! empty($snapshot['extra_work']))
        <h2>Extra Work Pricing</h2>
        <table class="data">
            <thead><tr><th>Description</th><th style="width: 22%;">Additional Fee</th><th style="width: 18%;">Affects Monthly Fee</th></tr></thead>
            <tbody>
            @foreach ($snapshot['extra_work'] as $item)
                <tr>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td>{{ $currencySymbol }}{{ number_format((float) ($item['fee'] ?? 0), fmod((float) ($item['fee'] ?? 0), 1.0) === 0.0 ? 0 : 2) }}</td>
                    <td>{{ ! empty($item['affects_monthly_fee']) ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($snapshot['requirements']))
        <h2 class="section-number">3. Client Information &amp; Requirements</h2>
        <p class="intro-text">Before starting the campaign, the Client will provide the required information and materials, including:</p>
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
                    <td>{{ $currencySymbol }}{{ number_format((float) ($row['cpl'] ?? 0), 2) }}</td>
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
            {{ $exampleQty }} × {{ $currencySymbol }}{{ number_format((float) $exampleCpl, 2) }}
            = <strong>{{ $currencySymbol }}{{ number_format((float) $exampleQty * (float) $exampleCpl, 2) }}</strong>
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

    <div class="footer">{{ $snapshot['provider']['name'] ?? config('contracts.provider.name') }} · Generated by VSP CRM · Page <span class="page-number"></span></div>
</body>
</html>
