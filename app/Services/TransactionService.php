<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function history(User $user): array
    {
        $transactions = $user->transactions()
            ->with(['category', 'account'])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10);

        return [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'has_more' => $transactions->hasMorePages(),
            ],
        ];
    }

    public function create(User $user, array $attributes): Transaction
    {
        $this->ensureCategoryCanBeUsed($user, $attributes['category_id'] ?? null, $attributes['type']);

        return $user->transactions()->create($attributes);
    }

    public function find(User $user, int $transactionId): Transaction
    {
        return $user->transactions()->with('category')->findOrFail($transactionId);
    }

    public function update(User $user, int $transactionId, array $attributes): Transaction
    {
        $transaction = $user->transactions()->findOrFail($transactionId);
        $categoryId = array_key_exists('category_id', $attributes)
            ? $attributes['category_id']
            : $transaction->category_id;
        $type = $attributes['type'] ?? $transaction->type;

        $this->ensureCategoryCanBeUsed($user, $categoryId, $type);
        $transaction->update($attributes);

        return $transaction->load('category');
    }

    public function delete(User $user, int $transactionId): void
    {
        $user->transactions()->findOrFail($transactionId)->delete();
    }

    public function transfer(User $user, array $attributes): array
    {
        return DB::transaction(function () use ($user, $attributes): array {
            $fromAccount = $user->accounts()->findOrFail($attributes['from_account_id']);
            $toAccount = $user->accounts()->findOrFail($attributes['to_account_id']);

            $sharedAttributes = [
                'category_id' => null,
                'amount' => $attributes['amount'],
                'description' => $attributes['description'] ?? null,
                'transaction_date' => $attributes['transaction_date'],
            ];

            // Insert Expense
            $expenseTransaction = $user->transactions()->create($sharedAttributes + [
                'type' => 'expense',
                'account_id' => $fromAccount->id,
            ]);

            // Generate transfer ID berdasarkan ID transaksi Expense
            $transferId = 'TRF' . $expenseTransaction->id;

            // Update transfer_id pada Expense
            $expenseTransaction->update([
                'transfer_id' => $transferId,
            ]);

            // Insert Income dengan transfer_id yang sama
            $incomeTransaction = $user->transactions()->create($sharedAttributes + [
                'type' => 'income',
                'account_id' => $toAccount->id,
                'transfer_id' => $transferId,
            ]);

            return [
                'expense_transaction' => $expenseTransaction->load('account'),
                'income_transaction' => $incomeTransaction->load('account'),
            ];
        });
    }

    private function ensureCategoryCanBeUsed(User $user, ?int $categoryId, string $type): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = Category::visibleTo($user->id)->find($categoryId);
        if (! $category || ! in_array($category->type, [$type, 'both'], true)) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category does not support this transaction type.'],
            ]);
        }
    }
}
