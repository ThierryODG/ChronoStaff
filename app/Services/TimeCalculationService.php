<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;

class TimeCalculationService
{
    /**
     * Apply rounding rule to a Carbon timestamp.
     * Rules: 'minute', '5min', '15min'
     */
    public static function roundTime(Carbon $time, string $rule): Carbon
    {
        $rounded = $time->copy();

        switch ($rule) {
            case '15min':
                $minute = $rounded->minute;
                $rem = $minute % 15;
                if ($rem >= 8) {
                    $rounded->addMinutes(15 - $rem)->second(0);
                } else {
                    $rounded->subMinutes($rem)->second(0);
                }
                break;

            case '5min':
                $minute = $rounded->minute;
                $rem = $minute % 5;
                if ($rem >= 3) {
                    $rounded->addMinutes(5 - $rem)->second(0);
                } else {
                    $rounded->subMinutes($rem)->second(0);
                }
                break;

            case 'minute':
            default:
                $rounded->second(0);
                break;
        }

        return $rounded;
    }

    /**
     * Calculate worked hours between an IN and an OUT punch,
     * applying rounding rules and automatic break deduction.
     *
     * @return array [
     *   'raw_duration_minutes' => int,
     *   'adjusted_duration_minutes' => int,
     *   'break_deducted_minutes' => int,
     *   'hours_decimal' => float,
     *   'formatted' => string (e.g. '7h 30m')
     * ]
     */
    public static function calculateShift(
        Carbon $inTime,
        Carbon $outTime,
        Company $company
    ): array {
        $rounding = $company->time_rounding ?? 'minute';
        $thresholdHours = $company->break_threshold_hours ?? 6.0;
        $deductionMinutes = $company->break_deduction_minutes ?? 30;

        $roundedIn = self::roundTime($inTime, $rounding);
        $roundedOut = self::roundTime($outTime, $rounding);

        $rawMinutes = max(0, $inTime->diffInMinutes($outTime));
        $roundedMinutes = max(0, $roundedIn->diffInMinutes($roundedOut));

        $breakDeducted = 0;
        // Automatic break deduction: if shift > threshold, deduct configured minutes
        if ($thresholdHours > 0 && ($roundedMinutes / 60) >= $thresholdHours) {
            $breakDeducted = $deductionMinutes;
        }

        $adjustedMinutes = max(0, $roundedMinutes - $breakDeducted);
        $hoursDecimal = round($adjustedMinutes / 60, 2);

        $hours = floor($adjustedMinutes / 60);
        $minutes = $adjustedMinutes % 60;
        $formatted = sprintf('%dh %02dm', $hours, $minutes);

        return [
            'raw_duration_minutes' => $rawMinutes,
            'adjusted_duration_minutes' => $adjustedMinutes,
            'break_deducted_minutes' => $breakDeducted,
            'hours_decimal' => $hoursDecimal,
            'formatted' => $formatted,
            'rounded_in' => $roundedIn->toIso8601String(),
            'rounded_out' => $roundedOut->toIso8601String(),
        ];
    }
}

