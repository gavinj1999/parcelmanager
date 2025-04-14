<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DatePeriod;
use App\Models\Round;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        // Cache date periods for 24 hours
        $datePeriods = Cache::remember('date_periods', 60 * 60 * 24, function () {
            return DatePeriod::select('id', 'name', 'start_date', 'end_date')
                ->get()
                ->toArray();
        });

        // Fetch activities with necessary relationships
        $activities = Activity::select('id', 'activity_date', 'parcel_type_id', 'quantity')
            ->with([
                'parcel_type' => function ($query) {
                    $query->select('id', 'name', 'round_id', 'rate');
                },
                'parcel_type.round' => function ($query) {
                    $query->select('id', 'name');
                },
            ])
            ->get()
            ->toArray();

        // Cache rounds for 24 hours
        $rounds = Cache::remember('rounds', 60 * 60 * 24, function () {
            return Round::select('id', 'name')
                ->get()
                ->toArray();
        });

        // Log the data to verify
        Log::info('Reports data sent to frontend:', [
            'activities' => $activities,
            'rounds' => $rounds,
            'datePeriods' => $datePeriods,
        ]);

        return Inertia::render('Reports/Index', [
            'activities' => $activities,
            'rounds' => $rounds,
            'datePeriods' => $datePeriods ?: [],
        ]);
    }
}
