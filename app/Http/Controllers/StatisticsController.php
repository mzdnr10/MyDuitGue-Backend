<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(private StatisticsService $statisticsService)
    {
    }

    public function index(Request $request)
    {
        return $this->successResponse(
            'Statistics retrieved successfully.',
            $this->statisticsService->forUser($request->user(), $request->all())
        );
    }
}
