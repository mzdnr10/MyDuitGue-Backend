<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class StatisticsService
{
    public function forUser(User $user, array $filters): array
    {
        $query = $user->transactions()->with('category');

        if (isset($filters['month'], $filters['year'])) {
            $date = Carbon::createFromDate($filters['year'], $filters['month'], 1);
            $query->whereBetween('transaction_date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ]);
        } else {
            $query->when(isset($filters['from']), fn ($query) => $query->whereDate('transaction_date', '>=', $filters['from']));
            $query->when(isset($filters['to']), fn ($query) => $query->whereDate('transaction_date', '<=', $filters['to']));
        }

        $transactions = $query->get();
        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $monthly = $transactions->groupBy(fn ($transaction) => Carbon::parse($transaction->transaction_date)->format('Y-m'))
            ->map(fn ($items, $month) => [
                'month' => $month,
                'income' => $items->where('type', 'income')->sum('amount'),
                'expense' => $items->where('type', 'expense')->sum('amount'),
                'count' => $items->count(),
            ])->values();
        $byCategory = $transactions->where('type', 'expense')->groupBy('category_id')
            ->map(fn ($items) => [
                'category_id' => $items->first()->category_id,
                'category_name' => $items->first()->category?->name,
                'amount' => $items->sum('amount'),
                'count' => $items->count(),
            ])->values();

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
            'transaction_count' => $transactions->count(),
            'monthly' => $monthly,
            'expense_by_category' => $byCategory,
        ];
    }
}
