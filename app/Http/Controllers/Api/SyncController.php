<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Bulk synchronize punches recorded while mobile was offline.
     *
     * Expected JSON payload:
     * {
     *   "punches": [
     *     {
     *       "offline_id": "c6a23ef2-243e-42c2-90ab-1a8dfbb69695", // Required UUID
     *       "user_id": 2, // Optional if auth Bearer provided
     *       "qr_token": "...", // Optional
     *       "pin_code": "...", // Optional
     *       "department_id": 1,
     *       "type": "in", // 'in' or 'out'
     *       "punched_at": "2026-11-23T08:15:00Z",
     *       "latitude": 48.8698,
     *       "longitude": 2.3314
     *     }
     *   ]
     * }
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'punches' => 'required|array',
            'punches.*.offline_id' => 'required|string|max:64',
            'punches.*.user_id' => 'nullable|integer',
            'punches.*.qr_token' => 'nullable|string',
            'punches.*.pin_code' => 'nullable|string',
            'punches.*.department_id' => 'nullable|integer',
            'punches.*.type' => 'nullable|in:in,out',
            'punches.*.punched_at' => 'required|date',
            'punches.*.latitude' => 'nullable|numeric',
            'punches.*.longitude' => 'nullable|numeric',
        ]);

        $authUser = $request->user();
        $synced = [];
        $duplicates = [];
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($validated['punches'] as $item) {
                $offlineId = $item['offline_id'];

                // 1. DEDUPLICATION: Check if this offline_id has already been recorded
                $existing = TimeLog::where('offline_id', $offlineId)->first();
                if ($existing) {
                    $duplicates[] = [
                        'offline_id' => $offlineId,
                        'server_id' => $existing->id,
                        'status' => 'already_synced',
                    ];
                    continue; // Skip without throwing error
                }

                // 2. Identify the employee
                $user = null;
                if (!empty($item['qr_token'])) {
                    $user = User::where('qr_token', $item['qr_token'])->first();
                } elseif (!empty($item['pin_code'])) {
                    $user = User::where('pin_code', $item['pin_code'])->first();
                } elseif (!empty($item['user_id'])) {
                    $user = User::find($item['user_id']);
                } elseif ($authUser) {
                    $user = $authUser;
                }

                if (!$user || !$user->is_active) {
                    $failed[] = [
                        'offline_id' => $offlineId,
                        'reason' => 'Utilisateur introuvable ou inactif.',
                    ];
                    continue;
                }

                // 3. Determine punch type if omitted
                $type = $item['type'] ?? null;
                if (!$type) {
                    // Check user's state right before this offline punch timestamp
                    $prev = TimeLog::where('user_id', $user->id)
                        ->where('punched_at', '<', Carbon::parse($item['punched_at']))
                        ->orderByDesc('punched_at')
                        ->first();
                    $type = ($prev && $prev->type === 'in') ? 'out' : 'in';
                }

                // 4. GPS Distance
                $company = $user->company;
                $lat = $item['latitude'] ?? null;
                $lng = $item['longitude'] ?? null;
                $distance = null;
                $isOnSite = false;

                if ($company && $lat !== null && $lng !== null) {
                    $distance = $company->distanceTo((float) $lat, (float) $lng);
                    $isOnSite = ($distance !== null && $distance <= 300);
                }

                // 5. Create TimeLog
                $timeLog = TimeLog::create([
                    'user_id' => $user->id,
                    'department_id' => $item['department_id'] ?? $user->departments()->first()?->id,
                    'type' => $type,
                    'punched_at' => Carbon::parse($item['punched_at']),
                    'offline_id' => $offlineId,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'distance_meters' => $distance,
                    'is_on_site' => $isOnSite,
                    'is_manual' => false,
                    'synced_at' => now(),
                ]);

                $synced[] = [
                    'offline_id' => $offlineId,
                    'server_id' => $timeLog->id,
                    'type' => $timeLog->type,
                    'punched_at' => $timeLog->punched_at->toIso8601String(),
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation : ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total_received' => count($validated['punches']),
                'synced_count' => count($synced),
                'duplicates_count' => count($duplicates),
                'failed_count' => count($failed),
            ],
            'synced' => $synced,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ]);
    }
}

