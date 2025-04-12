<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ParcelType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function index()
    {
        return Inertia::render('Activities/Index', [
            'activities' => Activity::where('user_id', auth()->id())
                ->with('parcelType.round')
                ->get(),
            'parcelTypes' => ParcelType::whereHas('round', function ($query) {
                $query->where('user_id', auth()->id());
            })->get(),
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
