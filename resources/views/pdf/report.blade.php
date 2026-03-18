<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
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
        .metrics {
            display: table;
            width: 100%;
            margin-bottom: 28px;
        }
        .metric-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 16px 10px;
            border: 1px solid #e8e5e0;
        }
        .metric-box:first-child { border-right: none; }
        .metric-box:last-child { border-left: none; }
        .metric-box .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7A7F82;
            font-weight: 600;
        }
        .metric-box .value {
            font-size: 18px;
            font-weight: 700;
            margin-top: 4px;
            color: #1a1a1a;
        }
        .metric-box.highlight .value { color: #BFA071; }
        .metric-box.deposit .value { color: #00807f; }
        .metric-box.withdrawal .value { color: #8b3a2f; }
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
        .badge-allocation {
            background: #f0ede8;
            color: #7A7F82;
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
        <div class="header-subtitle">Relatorio Mensal de Investimentos</div>
        <div class="report-title">
            Periodo: <strong>{{ $monthName }} / {{ $year }}</strong>
        </div>
    </div>

    <div class="content">
        <div class="client-info">
            <div class="client-name">{{ $client->full_name }}</div>
            <div class="client-doc">{{ $client->document }}</div>
        </div>

        <div class="metrics">
            <div class="metric-box deposit">
                <div class="label">Total Depositos</div>
                <div class="value">R$ {{ number_format($report->total_deposits, 2, ',', '.') }}</div>
            </div>
            <div class="metric-box withdrawal">
                <div class="label">Total Saques</div>
                <div class="value">R$ {{ number_format($report->total_withdrawals, 2, ',', '.') }}</div>
            </div>
            <div class="metric-box highlight">
                <div class="label">Rentabilidade</div>
                <div class="value">{{ number_format($report->profitability_percent, 2, ',', '.') }}%</div>
            </div>
        </div>

        <div class="section-title">Historico de Movimentacoes</div>

        @if($transactions->isEmpty())
            <p style="color: #7A7F82; font-style: italic;">Nenhuma movimentacao neste periodo.</p>
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
                        <tr>
                            <td>
                                @if($t->type === 'deposit')
                                    <span class="badge-deposit">Aporte</span>
                                @elseif($t->type === 'withdrawal')
                                    <span class="badge-withdrawal">Saque</span>
                                @else
                                    <span class="badge-allocation">Alocacao</span>
                                @endif
                            </td>
                            <td class="{{ $t->type === 'deposit' ? 'amount-deposit' : ($t->type === 'withdrawal' ? 'amount-withdrawal' : '') }}">
                                R$ {{ number_format($t->amount, 2, ',', '.') }}
                            </td>
                            <td>{{ $t->cashFlowTransaction?->currency?->code ?? '-' }}</td>
                            <td>{{ $t->cashFlowTransaction?->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        <span class="gold">TRADEON</span> &mdash; Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}
        <br>Este relatorio e confidencial e destinado exclusivamente ao cliente identificado acima.
    </div>
</body>
</html>
