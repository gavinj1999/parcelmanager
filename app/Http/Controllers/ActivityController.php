<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ParcelType;
use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index()
    {
        return Inertia::render('Activities/Index', [
            'activities' => Activity::where('user_id', auth()->id())
                ->with(['parcelType' => function ($query) {
                    $query->select('id', 'name', 'rate', 'round_id')->with(['round' => function ($q) {
                        $q->select('id', 'name');
                    }]);
                }])
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'parcel_type' => $activity->parcelType ? [
                            'id' => $activity->parcelType->id,
                            'name' => $activity->parcelType->name,
                            'rate' => $activity->parcelType->rate,
                            'round' => $activity->parcelType->round,
                        ] : null,
                        'activity_date' => $activity->activity_date,
                        'quantity' => $activity->quantity,
                    ];
                }),
            'parcelTypes' => \App\Models\ParcelType::whereHas('round', fn($q) => $q->where('user_id', auth()->id()))
                ->with('round')
                ->get(),
            'rounds' => Round::where('user_id', auth()->id())
                ->get()
                ->map(function ($round) {
                    return [
                        'id' => $round->id,
                        'name' => $round->name,
                        'parcel_types' => $round->parcelTypes->map(function ($parcelType) {
                            return [
                                'id' => $parcelType->id,
                                'name' => $parcelType->name,
                            ];
                        }),
                    ];
                }),
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
