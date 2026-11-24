<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset="utf-8">
    <title>Rapport ChronoStaff - {{ $company->name }}</title>
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        h1 { color: #1e3a8a; font-size: 18pt; margin-bottom: 4pt; }
        h2 { color: #334155; font-size: 14pt; margin-top: 16pt; margin-bottom: 6pt; }
        p.meta { color: #64748b; font-size: 10pt; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 8pt; margin-bottom: 12pt; }
        th { background-color: #f1f5f9; border: 1pt solid #cbd5e1; padding: 6pt; font-size: 10pt; font-weight: bold; text-align: left; }
        td { border: 1pt solid #cbd5e1; padding: 5pt; font-size: 10pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .overtime { color: #047857; font-weight: bold; }
        .lateness { color: #b91c1c; }
    </style>
</head>
<body>
    <table style="width: 100%; border: none; margin-bottom: 12pt;">
        <tr style="border: none;">
            <td style="border: none; vertical-align: middle;">
                <h1 style="margin: 0; padding: 0;">ChronoStaff — Rapport d'Heures & Présences</h1>
                <p class="meta" style="margin-top: 4pt; margin-bottom: 0;">Entreprise : <strong>{{ $company->name }}</strong> | Période : du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p class="meta" style="margin: 0;">Horaires de référence : {{ $company->work_start_time ?? '08:00' }} - {{ $company->work_end_time ?? '17:00' }}</p>
            </td>
            @if(!empty($logoBase64))
                <td style="border: none; text-align: right; vertical-align: middle;">
                    <img src="data:image/png;base64,{{ $logoBase64 }}" width="140" style="width: 140px; height: auto;" alt="ChronoStaff">
                </td>
            @endif
        </tr>
    </table>

    <h2>1. Synthèse des Salariés</h2>
    <table>
        <thead>
            <tr>
                <th>Employé</th>
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
                    <td class="bold">{{ $emp['name'] }}</td>
                    <td>{{ $emp['department'] }}</td>
                    <td class="text-center">{{ $emp['total_shifts'] }}</td>
                    <td class="text-right">{{ $emp['total_regular_hours'] }} h</td>
                    <td class="text-right overtime">{{ $emp['total_overtime_hours'] > 0 ? '+'.$emp['total_overtime_hours'].' h' : '—' }}</td>
                    <td class="text-right bold">{{ $emp['total_formatted'] }}</td>
                    <td class="text-right lateness">{{ $emp['total_lateness_formatted'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>2. Détail Chronologique</h2>
    <table>
        <thead>
            <tr>
                <th>Employé</th>
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
                        <td>{{ $shift['is_on_site'] ? 'Sur site' : 'À distance' }}</td>
                        <td class="text-right bold">{{ $shift['formatted_duration'] }}</td>
                        <td class="text-right overtime">{{ $shift['overtime_formatted'] }}</td>
                        <td class="text-right lateness">{{ $shift['lateness_formatted'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
