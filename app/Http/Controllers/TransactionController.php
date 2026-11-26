<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function history(Request $request)
    {
        $user = $request->user();

        $transactions = $user->transactions()
            ->with('category')
            ->with('account')
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(10);

        return $this->successResponse('Transaction history retrieved successfully.', [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'has_more' => $transactions->hasMorePages(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        
        $validated = $request->validate($this->rules());
        $transaction = $request->user()->transactions()->create($validated);

        return $this->successResponse('Transaction created successfully.', $transaction->load('category'), 201);
    }

    public function show(Request $request, int $transaction): JsonResponse
    {
        $transaction = $request->user()->transactions()->with('category')->findOrFail($transaction);

        return $this->successResponse('Transaction retrieved successfully.', $transaction);
    }

    public function update(Request $request, int $transaction): JsonResponse
    {
        $transaction = $request->user()->transactions()->findOrFail($transaction);
        $validated = $request->validate($this->rules(true));
        $categoryId = array_key_exists('category_id', $validated)
            ? $validated['category_id']
            : $transaction->category_id;
        $type = $validated['type'] ?? $transaction->type;
        $this->ensureCategoryCanBeUsed($request, $categoryId, $type);

        $transaction->update($validated);

        return $this->successResponse('Transaction updated successfully.', $transaction->load('category'));
    }

    public function destroy(Request $request, int $transaction): JsonResponse
    {
        $request->user()->transactions()->findOrFail($transaction)->delete();

        return $this->successResponse('Transaction deleted successfully.');
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'category_id' => [...$required, 'integer', 'exists:categories,id'],
            'amount' => [...$required, 'numeric', 'gt:0', 'max:9999999999999.99'],
            'type' => [...$required, Rule::in(['income', 'expense'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'transaction_date' => [...$required, 'date'],
            'account_id' => [...$required,'integer', 'exists:accounts,id'],
        ];
    }
}
