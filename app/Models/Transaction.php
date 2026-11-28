<?php

namespace App\Models;

use App\Models\PaymentProof;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'amount',
        'type',
        'description',
        'transaction_date',
        'account_id',
        'transfer_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
