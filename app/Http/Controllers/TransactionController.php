<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = $request->user()->transactions()
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', 'in:income,expense'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
        ]);

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json([
            'message' => 'Transaction created successfully.',
            'data' => $transaction,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $transaction = $this->findUserTransaction($request, $id);

        if (! $transaction) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => $transaction,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $transaction = $this->findUserTransaction($request, $id);

        if (! $transaction) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'type' => ['sometimes', 'required', 'in:income,expense'],
            'description' => ['sometimes', 'nullable', 'string'],
            'transaction_date' => ['sometimes', 'required', 'date'],
        ]);

        $transaction->update($validated);

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'data' => $transaction->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $transaction = $this->findUserTransaction($request, $id);

        if (! $transaction) {
            return $this->notFoundResponse();
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully.',
        ]);
    }

    private function findUserTransaction(Request $request, int $id): ?Transaction
    {
        return $request->user()->transactions()->find($id);
    }

    private function notFoundResponse()
    {
        return response()->json([
            'message' => 'Transaction not found.',
        ], 404);
    }
}
