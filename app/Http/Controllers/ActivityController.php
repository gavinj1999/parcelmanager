<?php

namespace App\Http\Controllers;

use App\Models\DatePeriod;
use App\Models\Activity;
use App\Models\ParcelType;
use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index()
    {
        // Fetch the date periods
        $datePeriods = DatePeriod::select('id', 'name', 'start_date', 'end_date')->get()->toArray();

        // Fetch activities with nested relationships
        $activities = Activity::with(['parcel_type.round'])->get()->toArray();

        // Fetch rounds with parcel types
        $rounds = Round::with('parcel_types')->get()->toArray();

        // Fetch parcel types with their round
        $parcelTypes = ParcelType::with('round')->get()->toArray();

        // Debug: Log the data to ensure relationships are loaded
        \Log::info('Activities with relationships:', $activities);
        \Log::info('Rounds:', $rounds);
        \Log::info('Parcel Types:', $parcelTypes);
        \Log::info('Date Periods:', $datePeriods);

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
