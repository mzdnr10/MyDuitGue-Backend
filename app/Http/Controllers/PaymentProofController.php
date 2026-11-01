<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use Illuminate\Http\Request;

class PaymentProofController extends Controller
{
    public function index(Request $request, int $transaction)
    {
        $ownerTransaction = $request->user()->transactions()->findOrFail($transaction);

        return $this->successResponse('Payment proofs retrieved successfully.', $ownerTransaction->paymentProofs()->get());
    }

    public function store(Request $request, int $transaction)
    {
        $ownerTransaction = $request->user()->transactions()->findOrFail($transaction);
        $validated = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $file = $validated['file'];
        $path = $file->store('payment-proofs', 'public');
        $proof = PaymentProof::create([
            'user_id' => $request->user()->id,
            'transaction_id' => $ownerTransaction->id,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return $this->successResponse('Payment proof uploaded successfully.', $proof, 201);
    }
}
