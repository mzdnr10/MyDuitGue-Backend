<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private HomeService $homeService)
    {
    }

    public function index(Request $request)
    {
        return $this->successResponse(
            'Home retrieved successfully.',
            $this->homeService->summary($request->user())
        );
    }

    public function weekly_expense(Request $request)
    {
        return $this->successResponse(
            'Berhasil',
            $this->homeService->weeklyExpense($request->user())
        );
    }
}
