<?php

namespace App\Services;

use App\Models\PaymentProof;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentProofService
{
    public function findOwnedTransaction(User $user, int $transactionId): Transaction
    {
        return $user->transactions()->findOrFail($transactionId);
    }

    public function listForTransaction(Transaction $transaction)
    {
        return $transaction->paymentProofs()->get();
    }

    public function upload(User $user, Transaction $transaction, UploadedFile $file): PaymentProof
    {
        $path = $file->store('payment-proofs', 'public');

        try {
            return DB::transaction(fn () => PaymentProof::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }
    }
}
