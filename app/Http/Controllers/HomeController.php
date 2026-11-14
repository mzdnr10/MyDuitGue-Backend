<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        $transactions = $user->transactions()
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->get([
                'transactions.amount',
                'transactions.type',
                'transactions.transaction_date',
                'transactions.account_id',
                'accounts.name as account_name'
            ]);

        $balance = $transactions->groupBy('account_id')
            ->map(function ($items) {

                $income = $items->where('type', 'income')->sum('amount');
                $expense = $items->where('type', 'expense')->sum('amount');

                return [
                    'account_id' => $items->first()->account_id,
                    'account_name' => $items->first()->account_name,
                    'income' => $income,
                    'expense' => $expense,
                    'balance' => $income - $expense
                ];
            })->values();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        $balance->prepend([
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
}
