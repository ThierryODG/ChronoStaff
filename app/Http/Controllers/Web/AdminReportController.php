<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\TimeCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::first();

        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        $departments = Department::when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))->get();
        $allEmployees = User::where('role', 'employee')
            ->when($admin->company_id, fn($q) => $q->where('company_id', $admin->company_id))
            ->orderBy('name')
            ->get();

        $reportData = $this->computeReport($startDate, $endDate, $departmentId, $userId, $company);

        return view('admin.reports.index', compact('reportData', 'departments', 'allEmployees', 'startDate', 'endDate', 'departmentId', 'userId', 'company'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::first();

        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        $reportData = $this->computeReport($startDate, $endDate, $departmentId, $userId, $company);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="chronostaff-rapport-' . $startDate . '-au-' . $endDate . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($reportData, $startDate, $endDate, $company) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($handle, ['ChronoStaff - Rapport des heures de travail et présences']);
            fputcsv($handle, ['Entreprise', $company->name]);
            fputcsv($handle, ['Période du', $startDate, 'au', $endDate]);
            fputcsv($handle, ['Horaires normaux', ($company->work_start_time ?? '08:00') . ' - ' . ($company->work_end_time ?? '17:00')]);
            fputcsv($handle, []);

            // Synthèse employés
            fputcsv($handle, [
                'Employé',
                'Département',
                'Shifts sur site',
                'Total travaillé (h)',
                'Heures normales (h)',
                'Heures supplémentaires (h)',
                'Retards cumulés (min)',
                'Pause déduite (min)',
            ]);

            foreach ($reportData['employees'] as $emp) {
                fputcsv($handle, [
                    $emp['name'],
                    $emp['department'],
                    $emp['total_shifts'],
                    $emp['total_adjusted_hours'],
                    $emp['total_regular_hours'],
                    $emp['total_overtime_hours'],
                    $emp['total_lateness_minutes'],
                    $emp['total_break_deducted_minutes'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Détail chronologique des shifts']);
            fputcsv($handle, ['Employé', 'Date', 'Arrivée (IN)', 'Départ (OUT)', 'Lieu', 'Durée brute', 'Durée comptabilisée', 'Heures supp', 'Retard', 'Note']);

            foreach ($reportData['employees'] as $emp) {
                foreach ($emp['shifts'] as $shift) {
                    fputcsv($handle, [
                        $emp['name'],
                        $shift['date'],
                        $shift['in_time'],
                        $shift['out_time'] ?? 'En cours...',
                        $shift['is_on_site'] ? 'Sur site' : 'À distance',
                        $shift['raw_duration'],
                        $shift['formatted_duration'],
                        $shift['overtime_formatted'],
                        $shift['lateness_formatted'],
                        $shift['note'] ?? '',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function exportPdf(Request $request): View
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::first();

        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        $reportData = $this->computeReport($startDate, $endDate, $departmentId, $userId, $company);

        return view('admin.reports.pdf', compact('reportData', 'company', 'startDate', 'endDate'));
    }

    public function exportWord(Request $request): Response
    {
        $admin = Auth::user();
        $company = $admin->company ?? Company::first();

        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        $reportData = $this->computeReport($startDate, $endDate, $departmentId, $userId, $company);

        $logoPath = public_path('images/logo.png');
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';

        $content = view('admin.reports.word', compact('reportData', 'company', 'startDate', 'endDate', 'logoBase64'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="chronostaff-rapport-' . $startDate . '-au-' . $endDate . '.doc"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function computeReport(string $startDate, string $endDate, ?string $departmentId, ?string $userId, Company $company): array
    {
        $query = User::where('role', 'employee')
            ->when($company->id, fn($q) => $q->where('company_id', $company->id))
            ->when($userId, fn($q) => $q->where('id', $userId));

        if ($departmentId) {
            $query->whereHas('departments', fn($q) => $q->where('departments.id', $departmentId));
        }

        $employees = $query->with('departments')->orderBy('name')->get();
        $results = [];

        $workStartTime = $company->work_start_time ?? '08:00';
        $workEndTime = $company->work_end_time ?? '17:00';
        $requireOnSite = $company->require_onsite_for_reports ?? true;
        $overtimeEnabled = $company->overtime_enabled ?? true;

        // Durée théorique d'une journée normale de travail (minutes)
        $standardWorkMinutes = 8 * 60; // 8 heures de base
        try {
            $standardStart = Carbon::createFromTimeString($workStartTime);
            $standardEnd = Carbon::createFromTimeString($workEndTime);
            $diff = $standardStart->diffInMinutes($standardEnd, false);
            if ($diff > 0) {
                // Moins la pause si configurée
                $standardWorkMinutes = max(60, $diff - ($company->break_deduction_minutes ?? 0));
            }
        } catch (\Throwable $e) {}

        foreach ($employees as $employee) {
            $logs = TimeLog::where('user_id', $employee->id)
                ->whereBetween('punched_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ])
                ->orderBy('punched_at')
                ->get();

            $shifts = [];
            $currentIn = null;
            $totalRawMins = 0;
            $totalAdjustedMins = 0;
            $totalRegularMins = 0;
            $totalOvertimeMins = 0;
            $totalLatenessMins = 0;
            $totalBreakMins = 0;

            foreach ($logs as $log) {
                if ($log->type === 'in') {
                    $currentIn = $log;
                } elseif ($log->type === 'out' && $currentIn) {
                    $isOnSite = (bool) ($currentIn->is_on_site || $log->is_on_site);

                    $calc = TimeCalculationService::calculateShift(
                        $currentIn->punched_at,
                        $log->punched_at,
                        $company
                    );

                    $shiftTotalMins = $calc['adjusted_duration_minutes'];

                    // Calcul du retard à l'arrivée
                    $latenessMins = 0;
                    $expectedStart = $currentIn->punched_at->copy()->setTimeFromTimeString($workStartTime);
                    if ($currentIn->punched_at->greaterThan($expectedStart)) {
                        $latenessMins = $expectedStart->diffInMinutes($currentIn->punched_at);
                    }

                    // Calcul des heures supplémentaires
                    $overtimeMins = 0;
                    $regularMins = 0;

                    // Si sur site obligatoire et que le shift est à distance, les heures payées sont nulles
                    if ($requireOnSite && !$isOnSite) {
                        $effectivePaidMins = 0;
                        $formattedDuration = '0h 00m (À distance)';
                    } else {
                        $effectivePaidMins = $shiftTotalMins;
                        $formattedDuration = $calc['formatted'];

                        if ($overtimeEnabled && $effectivePaidMins > $standardWorkMinutes) {
                            $overtimeMins = $effectivePaidMins - $standardWorkMinutes;
                            $regularMins = $standardWorkMinutes;
                        } else {
                            $regularMins = $effectivePaidMins;
                        }
                    }

                    $shifts[] = [
                        'date' => $currentIn->punched_at->format('d/m/Y'),
                        'in_time' => $currentIn->punched_at->format('H:i'),
                        'out_time' => $log->punched_at->format('H:i'),
                        'raw_duration' => sprintf('%dh %02dm', floor($calc['raw_duration_minutes'] / 60), $calc['raw_duration_minutes'] % 60),
                        'formatted_duration' => $formattedDuration,
                        'hours_decimal' => round($effectivePaidMins / 60, 2),
                        'regular_hours_decimal' => round($regularMins / 60, 2),
                        'overtime_hours_decimal' => round($overtimeMins / 60, 2),
                        'overtime_formatted' => $overtimeMins > 0 ? sprintf('+%dh %02dm', floor($overtimeMins / 60), $overtimeMins % 60) : '—',
                        'lateness_formatted' => $latenessMins > 0 ? sprintf('%d min', $latenessMins) : '—',
                        'lateness_minutes' => $latenessMins,
                        'is_on_site' => $isOnSite,
                        'note' => $log->admin_note ?? $currentIn->admin_note,
                    ];

                    $totalRawMins += $calc['raw_duration_minutes'];
                    $totalAdjustedMins += $effectivePaidMins;
                    $totalRegularMins += $regularMins;
                    $totalOvertimeMins += $overtimeMins;
                    $totalLatenessMins += $latenessMins;
                    $totalBreakMins += $calc['break_deducted_minutes'];

                    $currentIn = null;
                }
            }

            if ($currentIn) {
                $shifts[] = [
                    'date' => $currentIn->punched_at->format('d/m/Y'),
                    'in_time' => $currentIn->punched_at->format('H:i'),
                    'out_time' => null,
                    'raw_duration' => 'En cours...',
                    'formatted_duration' => 'En cours...',
                    'hours_decimal' => 0,
                    'regular_hours_decimal' => 0,
                    'overtime_hours_decimal' => 0,
                    'overtime_formatted' => '—',
                    'lateness_formatted' => '—',
                    'lateness_minutes' => 0,
                    'is_on_site' => (bool) $currentIn->is_on_site,
                    'note' => $currentIn->admin_note,
                ];
            }

            $completedShifts = array_filter($shifts, fn($s) => $s['out_time'] !== null);
            $totalShiftsCount = count($completedShifts);

            $results[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'department' => $employee->departments->first()?->name ?? 'Général',
                'total_shifts' => $totalShiftsCount,
                'total_raw_hours' => round($totalRawMins / 60, 2),
                'total_adjusted_hours' => round($totalAdjustedMins / 60, 2),
                'total_regular_hours' => round($totalRegularMins / 60, 2),
                'total_overtime_hours' => round($totalOvertimeMins / 60, 2),
                'total_lateness_minutes' => $totalLatenessMins,
                'total_break_deducted_minutes' => $totalBreakMins,
                'total_formatted' => sprintf('%dh %02dm', floor($totalAdjustedMins / 60), $totalAdjustedMins % 60),
                'total_overtime_formatted' => $totalOvertimeMins > 0 ? sprintf('+%dh %02dm', floor($totalOvertimeMins / 60), $totalOvertimeMins % 60) : '0h 00m',
                'total_lateness_formatted' => $totalLatenessMins > 0 ? sprintf('%dh %02dm', floor($totalLatenessMins / 60), $totalLatenessMins % 60) : '0 min',
                'shifts' => $shifts,
            ];
        }

        return ['employees' => $results];
    }
}
