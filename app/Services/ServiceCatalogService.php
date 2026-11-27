<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;

class ServiceCatalogService
{
    public function categoriesAndAccounts(User $user, ?string $type): array
    {
        return [
            'categories' => Category::visibleTo($user->id)
                ->where('type', $type)
                ->get(['id', 'name']),
            'accounts' => Account::get(['id', 'name', 'type']),
        ];
    }
}
