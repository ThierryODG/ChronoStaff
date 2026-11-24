<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Justification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JustificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $justifications = Justification::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $justifications,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'nullable|string',
            'reason' => 'nullable|string',
            'details' => 'nullable|string',
        ]);

        $type = $request->input('type') ?? $request->input('reason') ?? 'autre';
        if (!in_array($type, ['retard', 'absence', 'depart_anticipe', 'oubli_pointage', 'autre'])) {
            $type = 'autre';
        }

        $reasonText = $request->input('details') ?? $request->input('reason') ?? 'Aucune précision';

        $justification = Justification::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'date' => $validated['date'],
            'type' => $type,
            'reason' => $reasonText,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre justification a été transmise à la direction avec succès.',
            'justification' => $justification,
        ], 201);
    }
}
