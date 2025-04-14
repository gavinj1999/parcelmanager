<?php

namespace App\Http\Controllers;

use App\Models\DatePeriod;
use App\Models\Activity;
use App\Models\ParcelType;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Add this import
use Illuminate\Support\Facades\Log; // For logging
use Inertia\Inertia;

class ActivityController extends Controller
{
    // app/Http/Controllers/ActivityController.php
public function index()
{
    // Cache date periods for 24 hours
    $datePeriods = Cache::remember('date_periods', 60 * 60 * 24, function () {
        return DatePeriod::select('id', 'name', 'start_date', 'end_date')
            ->get()
            ->toArray();
    });

    // Fetch activities with only necessary columns
    $activities = Activity::select('id', 'activity_date', 'parcel_type_id', 'quantity')
        ->with([
            'parcel_type' => function ($query) {
                $query->select('id', 'name', 'round_id', 'rate');
            },
            'parcel_type.round' => function ($query) {
                $query->select('id', 'name');
            }
        ])
        ->paginate(50); // Load 50 activities per page

    // Cache rounds for 24 hours
    $rounds = Cache::remember('rounds', 60 * 60 * 24, function () {
        return Round::select('id', 'name')
            ->with(['parcel_types' => function ($query) {
                $query->select('id', 'name', 'round_id');
            }])
            ->get()
            ->toArray();
    });

    // Fetch parcel types with only necessary columns
    $parcelTypes = ParcelType::select('id', 'name', 'round_id')
        ->with(['round' => function ($query) {
            $query->select('id', 'name');
        }])
        ->get()
        ->toArray();

    return Inertia::render('Activities/Index', [
        'activities' => $activities,
        'parcelTypes' => $parcelTypes,
        'rounds' => $rounds,
        'datePeriods' => $datePeriods ?: [],
    ]);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parcel_type_id' => 'required|exists:parcel_types,id',
            'activity_date' => 'required|date',
            'quantity' => 'required|integer|min:0',
        ]);

        Activity::create(array_merge($validated, ['user_id' => auth()->id()]));

        return redirect()->route('activities.index');
    }


    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'activity_date' => 'required|date',
            'round_id' => 'required|exists:rounds,id',
            'quantities' => 'required|array',
            'quantities.*.parcel_type_id' => 'required|exists:parcel_types,id',
            'quantities.*.quantity' => 'required|integer|min:0',
        ]);

        foreach ($validated['quantities'] as $entry) {
            if ($entry['quantity'] > 0) {
                Activity::create([
                    'user_id' => auth()->id(),
                    'parcel_type_id' => $entry['parcel_type_id'],
                    'activity_date' => $validated['activity_date'],
                    'quantity' => $entry['quantity'],
                ]);
            }
        }

        return redirect()->route('activities.index')->with('success', 'Activities recorded successfully');
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorize('update', $activity);

        $validated = $request->validate([
            'parcel_type_id' => 'required|exists:parcel_types,id',
            'activity_date' => 'required|date',
            'quantity' => 'required|integer|min:0',
        ]);

        $activity->update($validated);

        return redirect()->route('activities.index');
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return redirect()->route('activities.index');
    }
}
