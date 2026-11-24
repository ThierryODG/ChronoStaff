<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Justification;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EmployeeMonthlyReportController extends Controller
{
    public function getReport(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        $targetDate = $request->has('month')
            ? Carbon::parse($request->input('month') . '-01')
            : now();

        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        $monthKey = $targetDate->format('Y-m');
        $monthLabel = $targetDate->locale('fr')->isoFormat('MMMM YYYY');

        // Récupérer les pointages de l'employé pour le mois
        $logs = TimeLog::where('user_id', $user->id)
            ->whereBetween('punched_at', [$startOfMonth->startOfDay(), $endOfMonth->endOfDay()])
            ->orderBy('punched_at')
            ->get();

        // Grouper par jour et calculer les shifts
        $grouped = $logs->groupBy(fn($l) => $l->punched_at->format('Y-m-d'));
        $dailySummary = [];
        $totalMinutes = 0;
        $totalOvertimeMinutes = 0;
        $totalLatenessMinutes = 0;
        $workStartTime = $company->work_start_time ?? '08:00';
        $workEndTime = $company->work_end_time ?? '17:00';

        foreach ($grouped as $dateStr => $dayLogs) {
            $inLog = $dayLogs->firstWhere('type', 'in');
            $outLog = $dayLogs->where('type', 'out')->sortByDesc('punched_at')->first();

            $dateFormatted = Carbon::parse($dateStr)->format('d/m/Y');
            $inTime = $inLog ? $inLog->punched_at->format('H:i') : '—';
            $outTime = $outLog ? $outLog->punched_at->format('H:i') : '—';
            $isOnSite = $inLog ? $inLog->is_on_site : true;

            $durationMinutes = 0;
            $overtimeMinutes = 0;
            $latenessMinutes = 0;

            if ($inLog) {
                $normalStart = Carbon::parse($dateStr . ' ' . $workStartTime);
                if ($inLog->punched_at->greaterThan($normalStart)) {
                    $latenessMinutes = (int) $inLog->punched_at->diffInMinutes($normalStart);
                    $totalLatenessMinutes += $latenessMinutes;
                }
            }

            if ($inLog && $outLog && $outLog->punched_at->greaterThan($inLog->punched_at)) {
                $rawMinutes = (int) $inLog->punched_at->diffInMinutes($outLog->punched_at);
                // Pause auto
                $thresholdMin = ($company->break_threshold_hours ?? 6) * 60;
                $deductMin = $company->break_deduction_minutes ?? 30;
                if ($rawMinutes >= $thresholdMin) {
                    $rawMinutes = max(0, $rawMinutes - $deductMin);
                }
                $durationMinutes = $rawMinutes;
                $totalMinutes += $durationMinutes;

                // Overtime
                if ($company->overtime_enabled) {
                    $normalEnd = Carbon::parse($dateStr . ' ' . $workEndTime);
                    if ($outLog->punched_at->greaterThan($normalEnd)) {
                        $overtimeMinutes = (int) $outLog->punched_at->diffInMinutes($normalEnd);
                        $totalOvertimeMinutes += $overtimeMinutes;
                    }
                }
            }

            $dailySummary[] = [
                'date' => $dateFormatted,
                'in' => $inTime,
                'out' => $outTime,
                'is_on_site' => $isOnSite,
                'duration' => sprintf('%02dh %02dm', intdiv($durationMinutes, 60), $durationMinutes % 60),
                'overtime' => $overtimeMinutes > 0 ? sprintf('+%02dh %02dm', intdiv($overtimeMinutes, 60), $overtimeMinutes % 60) : '—',
                'lateness' => $latenessMinutes > 0 ? sprintf('%d min', $latenessMinutes) : '—',
            ];
        }

        // Construire le fichier HTML imprimable A4 dans public/reports/monthly
        $reportsDir = public_path('reports/monthly');
        if (!File::isDirectory($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        $fileName = "bilan-mensuel-user-{$user->id}-{$monthKey}.html";
        $filePath = $reportsDir . DIRECTORY_SEPARATOR . $fileName;

        $totalFormatted = sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60);
        $overtimeFormatted = sprintf('%dh %02dm', intdiv($totalOvertimeMinutes, 60), $totalOvertimeMinutes % 60);

        $htmlContent = $this->renderHtmlTemplate(
            $user,
            $company,
            $monthLabel,
            $totalFormatted,
            $overtimeFormatted,
            $totalLatenessMinutes,
            count($grouped),
            $dailySummary
        );

        File::put($filePath, $htmlContent);

        // Nettoyage autonome : fichiers vieux de plus de 15 jours
        $this->cleanupOldFiles($reportsDir);

        // Nettoyage autonome des justifications traitées de plus de 30 jours
        Justification::whereIn('status', ['approved', 'rejected'])
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        return response()->json([
            'success' => true,
            'report_url' => url("reports/monthly/{$fileName}"),
            'month_label' => ucfirst($monthLabel),
            'total_shifts' => count($grouped),
            'total_hours' => $totalFormatted,
            'total_overtime' => $overtimeFormatted,
            'lateness_minutes' => $totalLatenessMinutes,
        ]);
    }

    private function cleanupOldFiles(string $dir): void
    {
        try {
            $files = File::files($dir);
            $expiryTime = time() - (15 * 86400); // 15 jours

            foreach ($files as $file) {
                if ($file->getMTime() < $expiryTime) {
                    File::delete($file->getPathname());
                }
            }
        } catch (\Throwable) {}
    }

    private function renderHtmlTemplate(
        $user,
        $company,
        string $monthLabel,
        string $totalHours,
        string $totalOvertime,
        int $latenessMinutes,
        int $shiftCount,
        array $dailySummary
    ): string {
        $rowsHtml = '';
        foreach ($dailySummary as $d) {
            $siteBadge = $d['is_on_site']
                ? '<span style="color:#059669; font-weight:bold;">Sur site</span>'
                : '<span style="color:#d97706;">À distance</span>';

            $rowsHtml .= "
                <tr>
                    <td>{$d['date']}</td>
                    <td>{$d['in']}</td>
                    <td>{$d['out']}</td>
                    <td>{$siteBadge}</td>
                    <td style='text-align:right; font-weight:bold;'>{$d['duration']}</td>
                    <td style='text-align:right; color:#047857;'>{$d['overtime']}</td>
                    <td style='text-align:right; color:#b91c1c;'>{$d['lateness']}</td>
                </tr>
            ";
        }

        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Bilan Mensuel de Pointage — {$user->name} ({$monthLabel})</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; padding: 30px; font-size: 13px; max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; }
        .subtitle { font-size: 12px; color: #64748b; margin-top: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
        .stat-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #64748b; }
        .stat-val { font-size: 18px; font-weight: 900; color: #0f172a; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
        th { background: #f1f5f9; color: #475569; font-weight: bold; text-align: left; padding: 8px 10px; border: 1px solid #cbd5e1; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 10px; border: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .btn-print { background: #2563eb; color: white; padding: 8px 16px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; float: right; margin-bottom: 15px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class='no-print'>
        <button class='btn-print' onclick='window.print()'>🖨️ Imprimer mon Bilan (PDF)</button>
    </div>
    <div class='header'>
        <div>
            <h1 class='title'>Bilan Mensuel Individuel de Pointage</h1>
            <div class='subtitle'>ChronoStaff &bull; {$company->name} &bull; Période : {$monthLabel}</div>
        </div>
    </div>

    <div class='stats-grid'>
        <div class='stat-card'>
            <div class='stat-label'>Salarié</div>
            <div class='stat-val' style='font-size:14px;'>{$user->name}</div>
        </div>
        <div class='stat-card'>
            <div class='stat-label'>Heures Validées</div>
            <div class='stat-val' style='color:#2563eb;'>{$totalHours}</div>
        </div>
        <div class='stat-card'>
            <div class='stat-label'>Heures Supp.</div>
            <div class='stat-val' style='color:#059669;'>{$totalOvertime}</div>
        </div>
        <div class='stat-card'>
            <div class='stat-label'>Jours Pointés</div>
            <div class='stat-val'>{$shiftCount}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Localisation</th>
                <th style='text-align:right;'>Durée</th>
                <th style='text-align:right;'>H. Supp</th>
                <th style='text-align:right;'>Retard</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
        </tbody>
    </table>

    <div style='margin-top: 35px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 11px; color: #94a3b8; display: flex; justify-content: space-between;'>
        <span>Document généré automatiquement par ChronoStaff le " . date('d/m/Y H:i') . "</span>
        <span>Relevé certifié conforme</span>
    </div>
</body>
</html>";
    }
}
