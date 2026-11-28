<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function history(Request $request)
    {
        return $this->successResponse(
            'Transaction history retrieved successfully.',
            $this->transactionService->history($request->user())
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        $transaction = $this->transactionService->create($request->user(), $validated);

        return $this->successResponse(
            'Transaction created successfully.',
            $transaction->load('category'),
            201
        );
    }

    public function show(Request $request, int $transaction): JsonResponse
    {
        return $this->successResponse(
            'Transaction retrieved successfully.',
            $this->transactionService->find($request->user(), $transaction)
        );
    }

    public function update(Request $request, int $transaction): JsonResponse
    {
        $validated = $request->validate($this->rules(true));

        return $this->successResponse(
            'Transaction updated successfully.',
            $this->transactionService->update($request->user(), $transaction, $validated)
        );
    }

    public function destroy(Request $request, int $transaction): JsonResponse
    {
        $this->transactionService->delete($request->user(), $transaction);

        return $this->successResponse('Transaction deleted successfully.');
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_accountid' => ['required', 'integer', 'exists:accounts,id'],
            'to_accountid' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999.99'],
            'description' => ['sometimes', 'nullable', 'string'],
            'transaction_date' => ['required', 'date'],
        ]);

        return $this->successResponse(
            'Transfer completed successfully.',
            $this->transactionService->transfer($request->user(), $validated),
            201
        );
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'category_id' => [...$required, 'integer', 'exists:categories,id'],
            // 'title' => [...$required, 'string', 'max:255'],
            'amount' => [...$required, 'numeric', 'gt:0', 'max:9999999999999.99'],
            'type' => [...$required, Rule::in(['income', 'expense'])],
            'description' => ['sometimes', 'nullable', 'string'],
            'transaction_date' => [...$required, 'date'],
            'account_id' => [...$required, 'integer', 'exists:accounts,id'],
        ];
    }
}
