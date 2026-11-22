<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->transactions()
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $query->when($request->filled('type'), fn($query) => $query->where('type', $request->string('type')));
        $query->when($request->filled('category_id'), fn($query) => $query->where('category_id', $request->integer('category_id')));
        $query->when($request->filled('from'), fn($query) => $query->whereDate('transaction_date', '>=', $request->date('from')));
        $query->when($request->filled('to'), fn($query) => $query->whereDate('transaction_date', '<=', $request->date('to')));
        $query->when($request->filled('search'), fn($query) => $query->where(function ($query) use ($request) {
            $term = '%' . $request->string('search') . '%';
            $query->where('title', 'like', $term)->orWhere('description', 'like', $term);
        }));

        $transactions = $request->filled('per_page')
            ? $query->paginate(min($request->integer('per_page', 20), 100))->withQueryString()
            : $query->get();

        return $this->successResponse('Transactions retrieved successfully.', $transactions);
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
