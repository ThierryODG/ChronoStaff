<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    /**
     * Get company parameters.
     */
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company ?? Company::firstOrCreate(['id' => 1], ['name' => 'ChronoStaff Company']);

        return response()->json([
            'company' => $company,
        ]);
    }

    /**
     * Update company settings: location, rounding rules, automatic break deductions, timezone.
     */
    public function update(Request $request): JsonResponse
    {
        $company = $request->user()->company ?? Company::first();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'time_rounding' => 'sometimes|in:minute,5min,15min',
            'break_threshold_hours' => 'sometimes|numeric|min:0|max:24',
            'break_deduction_minutes' => 'sometimes|integer|min:0|max:180',
            'timezone' => 'sometimes|string|timezone',
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Paramètres d\'entreprise mis à jour avec succès.',
            'company' => $company->fresh(),
        ]);
    }
}

