<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            color: #1a1a1a;
            font-size: 11px;
            line-height: 1.5;
            background: #fff;
        }
        .header {
            background: #0a0a0a;
            color: #fff;
            padding: 30px 40px;
            position: relative;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #003333, #00807f, #BFA071);
        }
        .brand {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #00807f;
        }
        .header-subtitle {
            color: #7A7F82;
            font-size: 11px;
            margin-top: 4px;
        }
        .report-title {
            font-size: 14px;
            color: #fff;
            margin-top: 16px;
            font-weight: 400;
        }
        .report-title strong {
            color: #00807f;
        }
        .content { padding: 30px 40px; }
        .client-info {
            background: #f8f8f6;
            border: 1px solid #e8e5e0;
            border-left: 3px solid #00807f;
            padding: 16px 20px;
            margin-bottom: 28px;
            display: table;
            width: 100%;
        }
        .client-info-left {
            display: table-cell;
            vertical-align: middle;
        }
        .client-info-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .client-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .client-doc {
            color: #7A7F82;
            font-size: 10px;
            margin-top: 2px;
        }
        .client-profit-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7A7F82;
            font-weight: 600;
        }
        .client-profit-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.1;
        }

        /* Metrics Grid */
        .metrics {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .metrics-row {
            display: table-row;
        }
        .metric-box {
            display: table-cell;
            text-align: center;
            padding: 14px 8px;
            border: 1px solid #e8e5e0;
            vertical-align: top;
        }
        .metric-box .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7A7F82;
            font-weight: 600;
        }
        .metric-box .value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
            color: #1a1a1a;
        }
        .color-teal { color: #00807f !important; }
        .color-gold { color: #BFA071 !important; }
        .color-red { color: #8b3a2f !important; }
        .color-gray { color: #7A7F82 !important; }

        /* Summary row */
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .summary-box {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border: 1px solid #e8e5e0;
            background: #f8f8f6;
            vertical-align: top;
        }
        .summary-box .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7A7F82;
            font-weight: 600;
        }
        .summary-box .value {
            font-size: 14px;
            font-weight: 700;
            margin-top: 4px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #00807f;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e8e5e0;
        }
        table.transactions {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.transactions th {
            background: #003333;
            color: #fff;
            padding: 8px 12px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        table.transactions td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0ede8;
            color: #333;
        }
        table.transactions tr:nth-child(even) td {
            background: #faf9f7;
        }
        .badge-deposit {
            background: #e6f2f2;
            color: #003333;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-withdrawal {
            background: #fde8e4;
            color: #8b3a2f;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-updated {
            background: #fdf5e6;
            color: #8a6d3b;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        .footer {
            margin-top: 40px;
            padding: 20px 40px;
            border-top: 1px solid #e8e5e0;
            color: #7A7F82;
            font-size: 9px;
            text-align: center;
        }
        .footer .gold { color: #00807f; }
        .amount-deposit { color: #00807f; font-weight: 600; }
        .amount-withdrawal { color: #8b3a2f; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">TRADEON</div>
        <div class="header-subtitle">Relatório Mensal de Investimentos</div>
        <div class="report-title">
            Período: <strong>{{ $monthName }} / {{ $year }}</strong>
        </div>
    </div>

    <div class="content">
        <div class="client-info">
            <div class="client-info-left">
                <div class="client-name">{{ $client->full_name }}</div>
                <div class="client-doc">{{ $client->document }}</div>
            </div>
            <div class="client-info-right">
                <div class="client-profit-label">Lucro</div>
                <div class="client-profit-value {{ $report->profit_value > 0 ? 'color-teal' : 'color-gray' }}">
                    R$ {{ number_format($report->profit_value, 2, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Linha 1: Valor Inicial / Valor Atualizado / Ganho Real / Ganho % --}}
        <div class="metrics">
            <div class="metric-box">
                <div class="label">Valor Inicial</div>
                <div class="value color-teal">R$ {{ number_format($report->initial_value, 2, ',', '.') }}</div>
            </div>
            <div class="metric-box">
                <div class="label">Valor Atualizado</div>
                <div class="value">R$ {{ number_format($report->updated_value, 2, ',', '.') }}</div>
            </div>
            <div class="metric-box">
                <div class="label">Ganho Real</div>
                <div class="value {{ $report->real_gain >= 0 ? 'color-teal' : 'color-red' }}">
                    R$ {{ number_format($report->real_gain, 2, ',', '.') }}
                </div>
            </div>
            <div class="metric-box">
                <div class="label">Ganho %</div>
                <div class="value {{ $report->gain_percentage >= 0 ? 'color-gold' : 'color-red' }}">
                    {{ number_format($report->gain_percentage, 2, ',', '.') }}%
                </div>
            </div>
        </div>

        {{-- Linha 2: Comissão / Lucro / Valor Inicial Mês Subsequente --}}
        <div class="summary-row">
            <div class="summary-box">
                <div class="label">Comissão ({{ number_format($report->commission_rate, 1, ',', '.') }}%)</div>
                <div class="value color-gold">R$ {{ number_format($report->commission_value, 2, ',', '.') }}</div>
            </div>
            <div class="summary-box">
                <div class="label">Valor Inicial {{ $nextPeriod }}</div>
                <div class="value color-teal">R$ {{ number_format($report->next_month_initial, 2, ',', '.') }}</div>
            </div>
        </div>

        <div class="section-title">Histórico de Movimentações</div>

        @if($transactions->isEmpty())
            <p style="color: #7A7F82; font-style: italic;">Nenhuma movimentação neste período.</p>
        @else
            <table class="transactions">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Moeda</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                        @php
                            $quotation = $t->cashFlowTransaction?->quotation_at_transaction ?? 1;
                            $valueBRL = $t->amount * $quotation;
                        @endphp
                        <tr>
                            <td>
                                @if($t->type === 'deposit')
                                    <span class="badge-deposit">Valor Inicial</span>
                                @elseif($t->type === 'contribution')
                                    <span class="badge-deposit">Aporte</span>
                                @elseif($t->type === 'withdrawal')
                                    <span class="badge-withdrawal">Saque</span>
                                @elseif($t->type === 'updated_value')
                                    <span class="badge-updated">Valor Atualizado</span>
                                @endif
                            </td>
                            <td class="{{ in_array($t->type, ['deposit', 'updated_value', 'contribution']) ? 'amount-deposit' : ($t->type === 'withdrawal' ? 'amount-withdrawal' : '') }}">
                                R$ {{ number_format($valueBRL, 2, ',', '.') }}
                            </td>
                            <td>BRL</td>
                            <td>{{ $t->cashFlowTransaction?->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        <span class="gold">TRADEON</span> &mdash; Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}
        <br>Este relatório é confidencial e destinado exclusivamente ao cliente identificado acima.
    </div>
</body>
</html>
