<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClockController extends Controller
{
    /**
     * Single punch endpoint: handles QR Code scan or 4-digit PIN fallback.
     * Can be invoked by the authenticated user, or via credentials in body.
     */
    public function punch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'nullable|string',
            'pin_code' => 'nullable|string|size:4',
            'type' => 'nullable|in:in,out', // Optional explicit punch type
            'department_id' => 'nullable|exists:departments,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'offline_id' => 'nullable|string|max:64',
            'punched_at' => 'nullable|date',
        ]);

        // 1. Resolve User
        $user = null;
        if (!empty($validated['qr_token'])) {
            $user = User::where('qr_token', $validated['qr_token'])->first();
        } elseif (!empty($validated['pin_code'])) {
            $user = User::where('pin_code', $validated['pin_code'])->first();
        } elseif ($request->user()) {
            $user = $request->user();
        }

        if (!$user) {
            $isPin = !empty($validated['pin_code']);
            return response()->json([
                'success' => false,
                'message' => $isPin ? 'Code PIN incorrect. Veuillez réessayer.' : 'Badge QR non reconnu.',
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte employé est inactif.',
            ], 403);
        }

        // 2. Offline idempotence check: if this offline_id already exists, return existing punch
        if (!empty($validated['offline_id'])) {
            $existing = TimeLog::where('offline_id', $validated['offline_id'])->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'is_duplicate' => true,
                    'message' => 'Ce pointage a déjà été enregistré.',
                    'time_log' => $existing,
                ]);
            }
        }

        // 3. Cooldown / Anti-double pointage check (si activé dans les paramètres)
        $company = $user->company;
        $latest = $user->latestTimeLog;
        $punchedAt = !empty($validated['punched_at'])
            ? Carbon::parse($validated['punched_at'])
            : now();

        if ($latest && $company && $company->cooldown_enabled) {
            $diffMinutes = $latest->punched_at->diffInMinutes($punchedAt);
            $minInterval = (int) ($company->min_punch_interval_minutes ?? 60);

            // Si le dernier pointage était une arrivée et qu'on veut pointer le départ avant le délai
            if ($latest->type === 'in' && $diffMinutes < $minInterval) {
                $remaining = $minInterval - $diffMinutes;
                $arrivalTime = $latest->punched_at->format('H:i');
                return response()->json([
                    'success' => false,
                    'cooldown' => true,
                    'message' => "Vous avez déjà pointé votre arrivée à {$arrivalTime}. Un délai de {$minInterval} min est requis avant le départ (reste {$remaining} min).",
                ], 422);
            }
        }

        // 4. Determine punch type ('in' or 'out')
        $type = $validated['type'] ?? null;
        if (!$type) {
            // Toggle state: if last was 'in', next is 'out'; otherwise 'in'
            $type = ($latest && $latest->type === 'in') ? 'out' : 'in';
        }

        // 5. GPS distance calculation & On-site determination
        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;
        $distance = null;
        $isOnSite = false;

        if ($company && $lat !== null && $lng !== null) {
            $distance = $company->distanceTo((float) $lat, (float) $lng);
            // Considered on-site if distance <= 300 meters
            $isOnSite = ($distance !== null && $distance <= 300);
        }

        // 6. Record TimeLog
        $timeLog = TimeLog::create([
            'user_id' => $user->id,
            'department_id' => $validated['department_id'] ?? $user->departments()->first()?->id,
            'type' => $type,
            'punched_at' => $punchedAt,
            'offline_id' => $validated['offline_id'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => $distance,
            'is_on_site' => $isOnSite,
            'is_manual' => false,
            'synced_at' => now(),
        ]);

        // 7. Friendly user message
        $timeStr = $punchedAt->format('H:i');
        $locationText = $isOnSite ? '(sur site)' : ($distance ? '(' . round($distance) . 'm de l\'entreprise)' : '');
        if ($type === 'in') {
            $message = "Bonjour {$user->name}, votre arrivée a été enregistrée à {$timeStr} {$locationText}.";
        } else {
            $message = "Au revoir {$user->name}, votre départ a été enregistré à {$timeStr} {$locationText}.";
        }

        return response()->json([
            'success' => true,
            'is_duplicate' => false,
            'message' => trim($message),
            'action' => $type,
            'time_log' => $timeLog->load(['department']),
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ], 201);
    }
}

