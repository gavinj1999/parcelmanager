<?php

namespace App\Http\Controllers;

use App\Models\ParcelType;
use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParcelTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('ParcelTypes/Index', [
            'parcelTypes' => ParcelType::whereHas('round', fn($q) => $q->where('user_id', auth()->id()))->with('round')->get(),
            'rounds' => Round::where('user_id', auth()->id())->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'max_weight' => 'required|numeric|min:0',
            'max_length' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
        ]);

        ParcelType::create($validated);

        return redirect()->route('parcel-types.index');
    }

    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'parcel_types' => 'required|array',
            'parcel_types.*.name' => 'required|string|max:255',
            'parcel_types.*.max_weight' => 'required|numeric|min:0',
            'parcel_types.*.max_length' => 'required|numeric|min:0',
            'parcel_types.*.rate' => 'required|numeric|min:0',
        ]);

        foreach ($validated['parcel_types'] as $parcelType) {
            ParcelType::create([
                'round_id' => $validated['round_id'],
                'name' => $parcelType['name'],
                'max_weight' => $parcelType['max_weight'],
                'max_length' => $parcelType['max_length'],
                'rate' => $parcelType['rate'],
            ]);
        }

        return redirect()->back()->with('success', 'New parcel types created successfully');
    }

    public function update(Request $request, ParcelType $parcelType)
    {
        $this->authorize('update', $parcelType->round);

        $validated = $request->validate([
            'round_id' => 'required|exists:rounds,id',
            'name' => 'required|string|max:255',
            'max_weight' => 'required|numeric|min:0',
            'max_length' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
        ]);

        $parcelType->update($validated);

        return redirect()->route('parcel-types.index');
    }

    public function destroy(ParcelType $parcelType)
    {
        $this->authorize('delete', $parcelType->round);

        $parcelType->delete();

        return redirect()->route('parcel-types.index');
    }
}
