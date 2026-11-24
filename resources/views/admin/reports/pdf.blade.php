<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport ChronoStaff - {{ $company->name }} ({{ $startDate }} au {{ $endDate }})</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 20px; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; }
        .logo { height: 45px; }
        .title { font-size: 18px; font-weight: bold; color: #1e293b; margin: 0; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 4px; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
        .meta-item { font-size: 11px; }
        .meta-label { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 9px; display: block; margin-bottom: 2px; }
        .meta-val { font-weight: bold; color: #0f172a; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        th { background: #f1f5f9; color: #475569; font-weight: bold; text-align: left; padding: 8px; border: 1px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        td { padding: 7px 8px; border: 1px solid #e2e8f0; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-onsite { background: #dcfce7; color: #166534; }
        .badge-remote { background: #fef3c7; color: #92400e; }
        .signature-box { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 20px; }
        .signature-line { width: 200px; border-top: 1px solid #94a3b8; text-align: center; padding-top: 5px; font-size: 10px; color: #64748b; }
        .print-btn { background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 15px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; font-size: 10px; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button class="print-btn" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">Rapport d'Heures & Présences</h1>
            <div class="subtitle">{{ $company->name }} — Période du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="ChronoStaff" class="logo">
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <span class="meta-label">Entreprise</span>
            <span class="meta-val">{{ $company->name }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Horaires de référence</span>
            <span class="meta-val">{{ $company->work_start_time ?? '08:00' }} - {{ $company->work_end_time ?? '17:00' }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Total Salariés</span>
            <span class="meta-val">{{ count($reportData['employees']) }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Date d'édition</span>
            <span class="meta-val">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <h2 style="font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #334155;">1. Synthèse par Salarié</h2>
    <table>
        <thead>
            <tr>
                <th>Salarié</th>
                <th>Département</th>
                <th class="text-center">Shifts</th>
                <th class="text-right">Heures Normales</th>
                <th class="text-right">Heures Supp.</th>
                <th class="text-right">Total Payé</th>
                <th class="text-right">Retards</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['employees'] as $emp)
                <tr>
                    <td style="font-weight: 600;">{{ $emp['name'] }}</td>
                    <td>{{ $emp['department'] }}</td>
                    <td class="text-center">{{ $emp['total_shifts'] }}</td>
                    <td class="text-right">{{ $emp['total_regular_hours'] }} h</td>
                    <td class="text-right" style="color: #047857; font-weight: 600;">{{ $emp['total_overtime_hours'] > 0 ? '+'.$emp['total_overtime_hours'].' h' : '—' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $emp['total_formatted'] }}</td>
                    <td class="text-right" style="color: {{ $emp['total_lateness_minutes'] > 0 ? '#b91c1c' : '#64748b' }};">{{ $emp['total_lateness_formatted'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 style="font-size: 13px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; color: #334155;">2. Détail Chronologique des Pointages</h2>
    <table>
        <thead>
            <tr>
                <th>Salarié</th>
                <th>Date</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Lieu</th>
                <th class="text-right">Durée Validée</th>
                <th class="text-right">Heures Supp.</th>
                <th class="text-right">Retard</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportData['employees'] as $emp)
                @foreach($emp['shifts'] as $shift)
                    <tr>
                        <td>{{ $emp['name'] }}</td>
                        <td>{{ $shift['date'] }}</td>
                        <td>{{ $shift['in_time'] }}</td>
                        <td>{{ $shift['out_time'] ?? 'En cours' }}</td>
                        <td>
                            <span class="badge {{ $shift['is_on_site'] ? 'badge-onsite' : 'badge-remote' }}">
                                {{ $shift['is_on_site'] ? 'Sur site' : 'À distance' }}
                            </span>
                        </td>
                        <td class="text-right" style="font-weight: 600;">{{ $shift['formatted_duration'] }}</td>
                        <td class="text-right" style="color: #047857;">{{ $shift['overtime_formatted'] }}</td>
                        <td class="text-right" style="color: {{ $shift['lateness_minutes'] > 0 ? '#b91c1c' : '#64748b' }};">{{ $shift['lateness_formatted'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="signature-box">
        <div class="signature-line">Visa de la Direction</div>
        <div class="signature-line">Signature de l'Employé</div>
    </div>

</body>
</html>
