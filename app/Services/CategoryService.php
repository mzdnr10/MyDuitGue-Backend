<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

class CategoryService
{
    public function listFor(User $user)
    {
        return Category::visibleTo($user->id)->orderBy('name')->get();
    }

    public function create(User $user, array $attributes): Category
    {
        return $user->categories()->create($attributes)->refresh();
    }

    public function findVisible(User $user, int $categoryId): Category
    {
        return Category::visibleTo($user->id)->findOrFail($categoryId);
    }

    public function update(User $user, int $categoryId, array $attributes): ?Category
    {
        $category = $user->categories()->findOrFail($categoryId);

        if (isset($attributes['type']) && $attributes['type'] !== 'both') {
            $hasIncompatibleTransactions = $category->transactions()
                ->where('type', '!=', $attributes['type'])
                ->exists();

            if ($hasIncompatibleTransactions) {
                return null;
            }
        }

        $category->update($attributes);

        return $category->refresh();
    }

    public function delete(User $user, int $categoryId): void
    {
        $user->categories()->findOrFail($categoryId)->delete();
    }
}
