<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class BackofficeController extends Controller
{
    public function users()
    {
        return $this->successResponse('Users retrieved successfully.', User::query()->latest()->paginate(25));
    }

    public function transactions()
    {
        return $this->successResponse('Transactions retrieved successfully.', Transaction::with(['user', 'category'])->latest('transaction_date')->paginate(25));
    }

    public function categories()
    {
        return $this->successResponse('Categories retrieved successfully.', Category::with('user')->orderBy('name')->paginate(25));
    }

    public function dashboard(Request $request)
    {
        return $this->successResponse('Backoffice dashboard retrieved successfully.', [
            'users' => User::count(),
            'transactions' => Transaction::count(),
            'income' => Transaction::where('type', 'income')->sum('amount'),
            'expense' => Transaction::where('type', 'expense')->sum('amount'),
        ]);
    }
}
