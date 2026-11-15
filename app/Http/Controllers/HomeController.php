<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        $accounts = DB::table('accounts')
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
            'balance' => $income - $expense
        ]);

        return $this->successResponse('Home retrieved successfully.', [
            'user_data' => $user->only([
                'name'
            ]),
            'balance' => $balance,
            'recent_transactions' => $user->transactions()
                ->with('category')
                ->latest('transaction_date')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function weekly_expense(Request $request)
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        
        $user = $request->user();

        $transactions = $user->transactions()
            ->selectRaw('DATE(transaction_date) as date')
            ->selectRaw('SUM(amount) as expense')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return $this->successResponse('Berhasil', $transactions);
    }
}
