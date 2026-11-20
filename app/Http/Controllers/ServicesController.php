<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;

class ServicesController extends Controller
{
    public function categoryAndAccount()
    {
        $categories = Category::where('type', 'income')
            ->get(['id', 'name']);
        
        $accounts = Account::get(['id', 'name', 'type']);
        $data = [
                'categories' => $categories,
                'accounts' => $accounts
            ];

        return $this->successResponse('Services retrieved successfully.', $data, 201);
    }
}
