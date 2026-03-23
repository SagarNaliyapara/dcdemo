<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'orderdate'      => 'datetime',
        'sent_date'      => 'datetime',
        'transmit_date'  => 'datetime',
        'is_opened'      => 'boolean',
        'is_transmitted' => 'boolean',
        'quantity'       => 'decimal:2',
        'approved_qty'   => 'decimal:2',
        'price'          => 'decimal:4',
        'max_price'      => 'decimal:4',
        'dt_price'       => 'decimal:4',
        'rule_price'     => 'decimal:4',
    ];

    public function getResponseBadgeClassAttribute(): string
    {
        $response = strtoupper($this->response ?? '');

        return match (true) {
            str_contains($response, 'IN STOCK') && ! str_contains($response, 'OUT')
                => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            str_contains($response, 'AWAITING')
                => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            str_contains($response, 'NOT ORDERED') || str_contains($response, 'OUT OF STOCK')
                => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            str_contains($response, 'EXCESS')
                => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
            str_contains($response, 'ORDERED') || str_contains($response, 'CONFIRMED')
                => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            default
                => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }

    public function getSubTotalAttribute(): float
    {
        return (float) ($this->quantity ?? 0) * (float) ($this->price ?? 0);
    }

    public function getDiscountAttribute(): ?float
    {
        if ($this->max_price !== null && $this->price !== null) {
            return (float) $this->max_price - (float) $this->price;
        }

        return null;
    }
}
