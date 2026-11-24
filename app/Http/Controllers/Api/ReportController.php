<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\TimeCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Get aggregated time report with shifts, worked hours, and rounding rules applied.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->generateReportData($request);

        return response()->json($data);
    }

    /**
     * Export report data as downloadable CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $data = $this->generateReportData($request);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rapport-heures-' . now()->format('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Summary Header
            fputcsv($handle, ['Rapport d\'heures ChronoStaff', 'Période : ' . $data['period']['start_date'] . ' au ' . $data['period']['end_date']]);
            fputcsv($handle, []);

            // Summary Table
            fputcsv($handle, ['Employé', 'Département', 'Nombre de shifts', 'Heures brutes (h)', 'Pause déduite (min)', 'Heures payées (h)', 'Durée formatée']);
            foreach ($data['employees'] as $emp) {
                fputcsv($handle, [
                    $emp['name'],
                    $emp['department'],
                    $emp['total_shifts'],
                    $emp['total_raw_hours'],
                    $emp['total_break_deducted_minutes'],
                    $emp['total_adjusted_hours'],
                    $emp['total_formatted'],
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Détail des shifts']);
            fputcsv($handle, ['Employé', 'Date', 'Arrivée (IN)', 'Départ (OUT)', 'Lieu', 'Durée brute', 'Durée ajustée', 'Note']);

            foreach ($data['employees'] as $emp) {
                foreach ($emp['shifts'] as $shift) {
                    fputcsv($handle, [
                        $emp['name'],
                        $shift['date'],
                        $shift['in_time'],
                        $shift['out_time'] ?? 'En cours...',
                        $shift['is_on_site'] ? 'Sur site' : 'À distance',
                        $shift['raw_duration'],
                        $shift['formatted_duration'],
                        $shift['note'] ?? '',
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Shared logic to compute shifts and worked hours.
     */
    protected function generateReportData(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfWeek()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfWeek()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $userId = $request->input('user_id');

        $admin = $request->user();
        $company = $admin->company ?? Company::first();

        // Query employees
        $empQuery = User::where('role', 'employee')
            ->when($company, fn($q) => $q->where('company_id', $company->id))
            ->when($userId, fn($q) => $q->where('id', $userId));

        if ($departmentId) {
            $empQuery->whereHas('departments', fn($q) => $q->where('departments.id', $departmentId));
        }

        $employees = $empQuery->with('departments')->get();
        $reportEmployees = [];

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
            $totalAdjustedMinutes = 0;
            $totalRawMinutes = 0;
            $totalBreakDeducted = 0;

            foreach ($logs as $log) {
                if ($log->type === 'in') {
                    $currentIn = $log;
                } elseif ($log->type === 'out' && $currentIn) {
                    $calc = TimeCalculationService::calculateShift(
                        $currentIn->punched_at,
                        $log->punched_at,
                        $company
                    );

                    $shifts[] = [
                        'date' => $currentIn->punched_at->format('d/m/Y'),
                        'in_time' => $currentIn->punched_at->format('H:i'),
                        'out_time' => $log->punched_at->format('H:i'),
                        'raw_duration' => sprintf('%dh %02dm', floor($calc['raw_duration_minutes'] / 60), $calc['raw_duration_minutes'] % 60),
                        'formatted_duration' => $calc['formatted'],
                        'hours_decimal' => $calc['hours_decimal'],
                        'is_on_site' => (bool) $currentIn->is_on_site,
                        'note' => $log->admin_note ?? $currentIn->admin_note,
                    ];

                    $totalRawMinutes += $calc['raw_duration_minutes'];
                    $totalAdjustedMinutes += $calc['adjusted_duration_minutes'];
                    $totalBreakDeducted += $calc['break_deducted_minutes'];

                    $currentIn = null;
                }
            }

            // If there's an unclosed shift currently in progress
            if ($currentIn) {
                $shifts[] = [
                    'date' => $currentIn->punched_at->format('d/m/Y'),
                    'in_time' => $currentIn->punched_at->format('H:i'),
                    'out_time' => null,
                    'raw_duration' => 'En cours...',
                    'formatted_duration' => 'En cours...',
                    'hours_decimal' => 0,
                    'is_on_site' => (bool) $currentIn->is_on_site,
                    'note' => $currentIn->admin_note,
                ];
            }

            $hours = floor($totalAdjustedMinutes / 60);
            $mins = $totalAdjustedMinutes % 60;

            $reportEmployees[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'department' => $employee->departments->first()?->name ?? 'Général',
                'total_shifts' => count(array_filter($shifts, fn($s) => $s['out_time'] !== null)),
                'total_raw_hours' => round($totalRawMinutes / 60, 2),
                'total_adjusted_hours' => round($totalAdjustedMinutes / 60, 2),
                'total_break_deducted_minutes' => $totalBreakDeducted,
                'total_formatted' => sprintf('%dh %02dm', $hours, $mins),
                'shifts' => $shifts,
            ];
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'company_rules' => [
                'time_rounding' => $company->time_rounding ?? 'minute',
                'break_threshold_hours' => $company->break_threshold_hours ?? 6.0,
                'break_deduction_minutes' => $company->break_deduction_minutes ?? 30,
            ],
            'employees' => $reportEmployees,
        ];
    }
}

