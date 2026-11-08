<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        // Total pemasukan dan pengeluaran
        $transactions = $user->transactions()->get([
            'amount',
            'type',
            'transaction_date'
        ]);

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        // Ringkasan 6 bulan terakhir
        $start = Carbon::now();

        $monthly = $user->transactions()
            ->whereBetween('transaction_date', [
                $start->copy()->startOfMonth()->subMonths(5)->toDateString(),
                Carbon::now()->endOfMonth()->toDateString()
            ])
            ->get(['amount', 'type', 'transaction_date'])
            ->groupBy(fn($transaction) => Carbon::parse($transaction->transaction_date)->format('Y-m'))
            ->map(fn($items, $month) => [
                'month' => $month,
                'income' => $items->where('type', 'income')->sum('amount'),
                'expense' => $items->where('type', 'expense')->sum('amount'),
            ])->values();

        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        // Tentukan periode minggu lalu
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekEnd->copy()->subWeek();

        // Hitung pemasukan minggu ini
        $thisWeekIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $thisWeekStart->toDateString(),
                $thisWeekEnd->toDateString()
            ])
            ->sum('amount');

        // Hitung pemasukan minggu lalu
        $lastWeekIncome = $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [
                $lastWeekStart->toDateString(),
                $lastWeekEnd->toDateString()
            ])
            ->sum('amount');

        // Hitung persentase pertumbuhan
        $weeklyGrowthPercent = null;

        if ($lastWeekIncome > 0) {
            $weeklyGrowthPercent = round(
                (($thisWeekIncome - $lastWeekIncome) / $lastWeekIncome) * 100,
                2
            );
        }

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
            'recent_transactions' => $user->transactions()
                ->with('category')
                ->latest('transaction_date')
                ->latest('id')
                ->limit(4)
                ->get(),
            'monthly_summary' => $monthly,
        ]);
    }
}
