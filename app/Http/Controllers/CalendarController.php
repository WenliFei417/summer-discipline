<?php

namespace App\Http\Controllers;

use App\Repositories\RecordFileRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request, RecordFileRepository $repository)
    {
        $monthInput = (string) $request->query('month', now()->format('Y-m'));
        $monthDate = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();

        return view('calendar.index', [
            'month' => $monthDate,
            'cardMap' => $repository->monthCardMap((int) $monthDate->format('Y'), (int) $monthDate->format('m')),
        ]);
    }
}
