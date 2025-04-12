<?php

namespace App\Http\Controllers;

use App\Models\DatePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DatePeriodController extends Controller
{
    public function index()
    {
        return Inertia::render('DatePeriods/Index', [
            'datePeriods' => DatePeriod::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        DatePeriod::create($validated);

        return redirect()->route('date-periods.index');
    }

    public function update(Request $request, DatePeriod $datePeriod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $datePeriod->update($validated);

        return redirect()->route('date-periods.index');
    }

    public function destroy(DatePeriod $datePeriod)
    {
        $datePeriod->delete();

        return redirect()->route('date-periods.index');
    }
}
