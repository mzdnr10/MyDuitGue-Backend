<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->transactions()->with('category');
        if ($request->filled('month') && $request->filled('year')) {
            $date = Carbon::createFromDate($request->integer('year'), $request->integer('month'), 1);
            $query->whereBetween('transaction_date', [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()]);
        } else {
            $query->when($request->filled('from'), fn($query) => $query->whereDate('transaction_date', '>=', $request->date('from')));
            $query->when($request->filled('to'), fn($query) => $query->whereDate('transaction_date', '<=', $request->date('to')));
        }

        $transactions = $query->get();
        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $monthly = $transactions->groupBy(fn($transaction) => Carbon::parse($transaction->transaction_date)->format('Y-m'))
            ->map(fn($items, $month) => [
                'month' => $month,
                'income' => $items->where('type', 'income')->sum('amount'),
                'expense' => $items->where('type', 'expense')->sum('amount'),
                'count' => $items->count(),
            ])->values();
        $byCategory = $transactions->where('type', 'expense')->groupBy('category_id')
            ->map(fn($items) => [
                'category_id' => $items->first()->category_id,
                'category_name' => $items->first()->category?->name,
                'amount' => $items->sum('amount'),
                'count' => $items->count(),
            ])->values();

        return $this->successResponse('Statistics retrieved successfully.', [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
            'transaction_count' => $transactions->count(),
            'monthly' => $monthly,
            'expense_by_category' => $byCategory,
        ]);
    }
}
