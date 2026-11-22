<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function categoryAndAccount(Request $request)
    {

        $type = $request->input('type');
        $categories = Category::where('type', $type)
            ->get(['id', 'name']);
        
        $accounts = Account::get(['id', 'name', 'type']);
        $data = [
                'categories' => $categories,
                'accounts' => $accounts
            ];

        return $this->successResponse('Services retrieved successfully.', $data, 201);
    }
}
