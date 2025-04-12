<?php

namespace App\Http\Controllers;

use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoundController extends Controller
{
    public function index()
    {
        return Inertia::render('Rounds/Index', [
            'rounds' => Round::where('user_id', auth()->id())->with('parcelTypes')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        Round::create(array_merge($validated, ['user_id' => auth()->id()]));

        return redirect()->route('rounds.index');
    }

    public function update(Request $request, Round $round)
    {
        $this->authorize('update', $round);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $round->update($validated);

        return redirect()->route('rounds.index');
    }

    public function destroy(Round $round)
    {
        $this->authorize('delete', $round);

        $round->delete();

        return redirect()->route('rounds.index');
    }
}
