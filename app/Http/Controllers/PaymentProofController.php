<?php

namespace App\Http\Controllers;

use App\Services\PaymentProofService;
use Illuminate\Http\Request;

class PaymentProofController extends Controller
{
    public function __construct(private PaymentProofService $paymentProofService)
    {
    }

    public function index(Request $request, int $transaction)
    {
        $ownerTransaction = $this->paymentProofService->findOwnedTransaction($request->user(), $transaction);

        return $this->successResponse(
            'Payment proofs retrieved successfully.',
            $this->paymentProofService->listForTransaction($ownerTransaction)
        );
    }

    public function store(Request $request, int $transaction)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $ownerTransaction = $this->paymentProofService->findOwnedTransaction($request->user(), $transaction);
        $proof = $this->paymentProofService->upload(
            $request->user(),
            $ownerTransaction,
            $validated['file']
        );

        return $this->successResponse('Payment proof uploaded successfully.', $proof, 201);
    }
}
