<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeService
{
    public function summary(User $user): array
    {
        $accounts = Account::query()
            ->leftJoin('transactions', function ($join) use ($user) {
                $join->on('accounts.id', '=', 'transactions.account_id')
                    ->where('transactions.user_id', $user->id);
            })
            ->select(
                'accounts.id as account_id',
                'accounts.name as account_name',
                DB::raw("COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END), 0) as income"),
                DB::raw("COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END), 0) as expense")
            )
            ->groupBy('accounts.id', 'accounts.name')
            ->get()
            ->map(function ($account) {
                $account->income = (int) $account->income;
                $account->expense = (int) $account->expense;
                $account->balance = $account->income - $account->expense;

                return $account;
            });

        $income = $accounts->sum('income');
        $expense = $accounts->sum('expense');
        $balance = $accounts->prepend((object) [
            'account_id' => 0,
            'account_name' => 'SEMUA',
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ]);

        return [
            'user_data' => $user->only(['name']),
            'balance' => $balance,
            'recent_transactions' => $user->transactions()
                ->with('category')
                ->latest('transaction_date')
                ->latest('id')
                ->limit(5)
                ->get(),
        ];
    }

    public function weeklyExpense(User $user): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $transactions = $user->transactions()
            ->selectRaw('DATE(transaction_date) as date')
            ->selectRaw('SUM(amount) as expense')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyExpenses = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dailyExpenses[] = [
                'date' => $date,
                'day_name' => match (now()->subDays($i)->dayOfWeek) {
                    0 => 'Min',
                    1 => 'Sen',
                    2 => 'Sel',
                    3 => 'Rab',
                    4 => 'Kam',
                    5 => 'Jum',
                    6 => 'Sab',
                },
                'expense' => (int) ($transactions[$date]->expense ?? 0),
            ];
        }

        return [
            'total_expense' => collect($dailyExpenses)->sum('expense'),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'daily_expenses' => $dailyExpenses,
        ];
    }
}
