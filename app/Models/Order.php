<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use vahidkaargar\LaravelWallet\Models\Wallet;

class Order extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'wallet_id',
        'status',
        'purchase_amount',
        'sale_amount',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => OrderStatusEnum::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Release the funds reserved at checkout.
     */
    public function releaseFunds(): void
    {
        $this->user->unlockFunds($this->wallet->slug, $this->sale_amount, $this->walletReference());
    }

    /**
     * Turn the reservation into a charge.
     */
    public function chargeFunds(): void
    {
        $this->releaseFunds();
        $this->user->withdraw($this->wallet->slug, $this->sale_amount, $this->walletReference(), meta: ['order_id' => $this->id]);
    }

    /**
     * Idempotency key sent to the supplier for this order.
     */
    public function supplierRequestId(): string
    {
        return 'mobo-order-'.$this->id;
    }

    /**
     * Wallet transaction reference for this order.
     */
    public function walletReference(): string
    {
        return 'order:'.$this->id;
    }
}
