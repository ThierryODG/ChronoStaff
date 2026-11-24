<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $admin = Auth::user();
        $companyId = $admin->company_id;

        $employees = User::where('role', 'employee')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->with(['departments', 'company'])
            ->orderBy('name')
            ->get();

        $present = [];
        $absent = [];

        foreach ($employees as $emp) {
            $latest = TimeLog::where('user_id', $emp->id)
                ->orderByDesc('punched_at')
                ->first();

            if ($latest && $latest->type === 'in') {
                $durationMinutes = $latest->punched_at->diffInMinutes(now());
                $hours = floor($durationMinutes / 60);
                $mins = $durationMinutes % 60;

                $present[] = [
                    'employee' => $emp,
                    'department' => $latest->department?->name ?? $emp->departments->first()?->name ?? 'Général',
                    'time' => $latest->punched_at->format('H:i'),
                    'date' => $latest->punched_at->format('d/m/Y'),
                    'elapsed' => sprintf('%dh %02dm', $hours, $mins),
                    'is_on_site' => (bool) $latest->is_on_site,
                    'distance' => $latest->distance_meters,
                    'is_active' => $emp->is_active,
                ];
            } else {
                $absent[] = [
                    'employee' => $emp,
                    'department' => $emp->departments->first()?->name ?? 'Général',
                    'last_seen' => $latest ? $latest->punched_at->format('d/m/Y H:i') : 'Jamais',
                    'is_active' => $emp->is_active,
                ];
            }
        }

        // Recent punch activity (last 10 punches)
        $recentPunches = TimeLog::with(['user', 'department'])
            ->whereHas('user', fn($q) => $companyId ? $q->where('company_id', $companyId) : $q)
            ->orderByDesc('punched_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard.index', compact('present', 'absent', 'recentPunches', 'employees'));
    }
}

