<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\DailyCashRecapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyRecapController extends Controller
{
    public function __construct(
        protected DailyCashRecapService $recapService
    ) {}

    /**
     * Display the Daily Cash Recap (Rekap Kas Harian) page.
     */
    public function index(Request $request): Response
    {
        $date = $request->query('date', now()->toDateString());
        $recap = $this->recapService->getRecapForDate($date);

        return Inertia::render('Owner/DailyRecap', [
            'recap' => $recap,
            'selected_date' => $date,
        ]);
    }

    /**
     * Get thermal print slip data in JSON format for POS / direct printer consumption.
     */
    public function printData(Request $request, ?string $date = null): JsonResponse
    {
        $targetDate = $date ?? $request->query('date', now()->toDateString());
        $recap = $this->recapService->getRecapForDate($targetDate);

        return response()->json([
            'status' => 'success',
            'data' => $recap,
        ]);
    }
}
