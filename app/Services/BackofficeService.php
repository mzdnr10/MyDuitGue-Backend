<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

class BackofficeService
{
    public function users()
    {
        return User::query()->latest()->paginate(25);
    }

    public function transactions()
    {
        return Transaction::with(['user', 'category'])->latest('transaction_date')->paginate(25);
    }

    public function categories()
    {
        return Category::with('user')->orderBy('name')->paginate(25);
    }

    public function summary(): array
    {
        return [
            'users' => User::count(),
            'transactions' => Transaction::count(),
            'income' => Transaction::where('type', 'income')->sum('amount'),
            'expense' => Transaction::where('type', 'expense')->sum('amount'),
        ];
    }
}
