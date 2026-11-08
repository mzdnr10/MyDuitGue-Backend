<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        // LOGIC EXISTING - TIDAK DIUBAH
        $transactions = $user->transactions()->get(['amount', 'type', 'transaction_date']);
        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        $start = Carbon::now()->startOfMonth();

        $monthly = $user->transactions()
            ->whereBetween('transaction_date', [
                $start->copy()->subMonths(5)->toDateString(),
                Carbon::now()->endOfMonth()->toDateString()
            ])
            ->get(['amount', 'type', 'transaction_date'])
            ->groupBy(fn($transaction) => Carbon::parse($transaction->transaction_date)->format('Y-m'))
            ->map(fn($items, $month) => [
                'month' => $month,
                'income' => $items->where('type', 'income')->sum('amount'),
                'expense' => $items->where('type', 'expense')->sum('amount'),
            ])->values();


        // TAMBAHAN - WEEKLY GROWTH
        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekEnd->copy()->subWeek();

        $thisWeekIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $thisWeekStart->toDateString(),
                $thisWeekEnd->toDateString()
            ])
            ->sum('amount');

        $lastWeekIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $lastWeekStart->toDateString(),
                $lastWeekEnd->toDateString()
            ])
            ->sum('amount');

        $weeklyGrowthPercent = null;

        if ($lastWeekIncome > 0) {
            $weeklyGrowthPercent = round(
                (($thisWeekIncome - $lastWeekIncome) / $lastWeekIncome) * 100,
                2
            );
        }


        // RESPONSE EXISTING + 1 FIELD BARU
        return $this->successResponse('Dashboard retrieved successfully.', [
            'user_data' => $user->only([
                'id',
                'name',
                'avatar',
            ]),
            'balance' => $income - $expense,
            'income' => $income,
            'expense' => $expense,
            'weekly_growth_percent' => $weeklyGrowthPercent,
            'recent_transactions' => $user->transactions()->with('category')->latest('transaction_date')->latest('id')->limit(5)->get(),
            'monthly_summary' => $monthly,
        ]);
    }
}
