<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', 'Helvetica', sans-serif; color: #1a1a1a; font-size: 11px; line-height: 1.5; background: #fff; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: avoid; }
        .header { background: #0a0a0a; color: #fff; padding: 30px 40px; position: relative; }
        .header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: linear-gradient(to right, #003333, #00807f, #BFA071); }
        .brand { font-size: 22px; font-weight: 700; letter-spacing: 3px; color: #00807f; }
        .header-subtitle { color: #7A7F82; font-size: 11px; margin-top: 4px; }
        .report-title { font-size: 14px; color: #fff; margin-top: 16px; font-weight: 400; }
        .report-title strong { color: #00807f; }
        .content { padding: 30px 40px; }
        .collab-info { background: #f8f8f6; border: 1px solid #e8e5e0; border-left: 3px solid #00807f; padding: 16px 20px; margin-bottom: 28px; display: table; width: 100%; }
        .collab-info-left { display: table-cell; vertical-align: middle; }
        .collab-info-right { display: table-cell; vertical-align: middle; text-align: right; }
        .collab-name { font-size: 14px; font-weight: 600; color: #1a1a1a; }
        .collab-cpf { color: #7A7F82; font-size: 10px; margin-top: 2px; }
        .profit-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #7A7F82; font-weight: 600; }
        .profit-value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .metrics { display: table; width: 100%; margin-bottom: 10px; }
        .metric-box { display: table-cell; text-align: center; padding: 14px 8px; border: 1px solid #e8e5e0; vertical-align: top; }
        .metric-box .label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #7A7F82; font-weight: 600; }
        .metric-box .value { font-size: 15px; font-weight: 700; margin-top: 4px; color: #1a1a1a; }
        .color-teal { color: #00807f !important; }
        .color-gold { color: #BFA071 !important; }
        .color-red { color: #8b3a2f !important; }
        .color-warning { color: #e0a830 !important; }
        .color-gray { color: #7A7F82 !important; }
        .summary-row { display: table; width: 100%; margin-bottom: 28px; }
        .summary-box { display: table-cell; text-align: center; padding: 12px 8px; border: 1px solid #e8e5e0; background: #f8f8f6; vertical-align: top; }
        .summary-box .label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #7A7F82; font-weight: 600; }
        .summary-box .value { font-size: 14px; font-weight: 700; margin-top: 4px; }
        .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #00807f; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e8e5e0; }
        table.transactions { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.transactions th { background: #003333; color: #fff; padding: 8px 12px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        table.transactions td { padding: 8px 12px; border-bottom: 1px solid #f0ede8; color: #333; }
        table.transactions tr:nth-child(even) td { background: #faf9f7; }
        .badge-deposit { background: #e6f2f2; color: #003333; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-withdrawal { background: #fde8e4; color: #8b3a2f; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-updated { background: #fdf5e6; color: #8a6d3b; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .badge-commission { background: #f5f0e6; color: #8a6d3b; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: 600; }
        .warning-badge { background: #fdf5e6; color: #8a6d3b; padding: 3px 10px; border-radius: 4px; font-size: 9px; font-weight: 600; display: inline-block; margin-top: 4px; }
        .footer { margin-top: 40px; padding: 20px 40px; border-top: 1px solid #e8e5e0; color: #7A7F82; font-size: 9px; text-align: center; }
        .footer .gold { color: #00807f; }
        .amount-positive { color: #00807f; font-weight: 600; }
        .amount-negative { color: #8b3a2f; font-weight: 600; }
        .totals-box { background: #0a0a0a; color: #fff; padding: 20px 40px; margin-bottom: 0; }
        .totals-row { display: table; width: 100%; }
        .totals-cell { display: table-cell; text-align: center; padding: 10px; vertical-align: top; }
        .totals-cell .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #7A7F82; }
        .totals-cell .value { font-size: 20px; font-weight: 700; margin-top: 4px; }
    </style>
</head>
<body>
    {{-- Página de Resumo --}}
    <div class="page">
        <div class="header">
            <div class="brand">TRADEON</div>
            <div class="header-subtitle">Relatório Consolidado — Colaboradores</div>
            <div class="report-title"><strong>{{ count($pages) }} relatório(s) selecionado(s)</strong></div>
        </div>
        <div class="totals-box">
            <div class="totals-row">
                <div class="totals-cell">
                    <div class="label">Total Valor Atualizado</div>
                    <div class="value color-teal">US$ {{ number_format($totalUpdatedValue, 2, ',', '.') }}</div>
                </div>
                <div class="totals-cell">
                    <div class="label">Média Lucro %</div>
                    <div class="value {{ $avgProfitPct < 0 ? 'color-red' : ($avgProfitPct < 5 ? 'color-warning' : 'color-gold') }}">{{ number_format($avgProfitPct, 2, ',', '.') }}%</div>
                </div>
                <div class="totals-cell">
                    <div class="label">Meta</div>
                    <div class="value color-gold">5,00%</div>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="section-title">Colaboradores</div>
            <table class="transactions">
                <thead>
                    <tr><th>Colaborador</th><th>Período</th><th>Valor Atualizado</th><th>Lucro %</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($pages as $p)
                        @php $pct = (float) $p['report']->profit_percentage; @endphp
                        <tr>
                            <td style="font-weight: 600;">{{ $p['collaborator']->name }}</td>
                            <td>{{ $p['monthName'] }} / {{ $p['year'] }}</td>
                            <td class="amount-positive">US$ {{ number_format($p['report']->updated_value, 2, ',', '.') }}</td>
                            <td style="color: {{ $pct < 0 ? '#8b3a2f' : ($pct < 5 ? '#e0a830' : '#00807f') }}; font-weight: 600;">{{ number_format($pct, 2, ',', '.') }}%</td>
                            <td>
                                @if($pct < 0)
                                    <span style="color: #8b3a2f; font-weight: 600;">Negativo</span>
                                @elseif($pct < 5)
                                    <span style="color: #e0a830; font-weight: 600;">Abaixo da meta</span>
                                @else
                                    <span style="color: #00807f; font-weight: 600;">Na meta</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="footer">
            <span class="gold">TRADEON</span> &mdash; Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Páginas individuais --}}
    @foreach($pages as $page)
        @php
            $report = $page['report'];
            $collaborator = $page['collaborator'];
            $transactions = $page['transactions'];
            $monthName = $page['monthName'];
            $year = $page['year'];
            $nextPeriod = $page['nextPeriod'];
            $pct = (float) $report->profit_percentage;
        @endphp
        <div class="page">
            <div class="header">
                <div class="brand">TRADEON</div>
                <div class="header-subtitle">Relatório Interno — Colaborador</div>
                <div class="report-title">Período: <strong>{{ $monthName }} / {{ $year }}</strong></div>
            </div>
            <div class="content">
                <div class="collab-info">
                    <div class="collab-info-left">
                        <div class="collab-name">{{ $collaborator->name }}</div>
                        <div class="collab-cpf">{{ $collaborator->cpf }}</div>
                    </div>
                    <div class="collab-info-right">
                        <div class="profit-label">Lucro %</div>
                        <div class="profit-value {{ $pct < 0 ? 'color-red' : ($pct < 5 ? 'color-warning' : 'color-teal') }}">
                            {{ number_format($pct, 2, ',', '.') }}%
                        </div>
                        @if($pct >= 0 && $pct < 5)
                            <div class="warning-badge">Abaixo da meta de 5%</div>
                        @endif
                    </div>
                </div>

                <div class="metrics">
                    <div class="metric-box">
                        <div class="label">Valor Inicial</div>
                        <div class="value color-teal">US$ {{ number_format($report->initial_value, 2, ',', '.') }}</div>
                    </div>
                    <div class="metric-box">
                        <div class="label">Valor Atualizado</div>
                        <div class="value">US$ {{ number_format($report->updated_value, 2, ',', '.') }}</div>
                    </div>
                    <div class="metric-box">
                        <div class="label">Lucro</div>
                        <div class="value {{ $report->profit >= 0 ? 'color-teal' : 'color-red' }}">
                            US$ {{ number_format($report->profit, 2, ',', '.') }}
                        </div>
                    </div>
                    <div class="metric-box">
                        <div class="label">Lucro %</div>
                        <div class="value {{ $pct < 0 ? 'color-red' : ($pct < 5 ? 'color-warning' : 'color-gold') }}">
                            {{ number_format($pct, 2, ',', '.') }}%
                        </div>
                    </div>
                </div>

                <div class="summary-row">
                    <div class="summary-box">
                        <div class="label">Comissão ({{ number_format($report->commission_rate, 1, ',', '.') }}%)</div>
                        <div class="value color-gold">US$ {{ number_format($report->commission_value, 2, ',', '.') }}</div>
                    </div>
                    <div class="summary-box">
                        <div class="label">Valor Inicial {{ $nextPeriod }}</div>
                        <div class="value color-teal">US$ {{ number_format($report->next_month_initial, 2, ',', '.') }}</div>
                    </div>
                </div>

                <div class="section-title">Movimentações do Período</div>
                @if($transactions->isEmpty())
                    <p style="color: #7A7F82; font-style: italic;">Nenhuma movimentação neste período.</p>
                @else
                    <table class="transactions">
                        <thead><tr><th>Tipo</th><th>Valor</th><th>Data</th></tr></thead>
                        <tbody>
                            @foreach($transactions as $t)
                                <tr>
                                    <td>
                                        @if($t->type === 'initial_value')
                                            <span class="badge-deposit">Valor Inicial</span>
                                        @elseif($t->type === 'deposit')
                                            <span class="badge-deposit">Aporte</span>
                                        @elseif($t->type === 'updated_value')
                                            <span class="badge-updated">Valor Atualizado</span>
                                        @elseif($t->type === 'withdrawal')
                                            <span class="badge-withdrawal">Retirada</span>
                                        @elseif($t->type === 'commission_withdrawal')
                                            <span class="badge-commission">Saque Comissão</span>
                                        @elseif($t->type === 'client_withdrawal')
                                            <span class="badge-withdrawal">Saque Cliente</span>
                                        @endif
                                    </td>
                                    <td class="{{ in_array($t->type, ['initial_value', 'deposit', 'updated_value']) ? 'amount-positive' : 'amount-negative' }}">
                                        US$ {{ number_format($t->amount, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $t->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="footer">
                <span class="gold">TRADEON</span> &mdash; Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}
                <br>Este relatório é confidencial e de uso interno.
            </div>
        </div>
    @endforeach
</body>
</html>
