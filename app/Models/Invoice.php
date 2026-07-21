<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_order_id',
        'invoice_number',
        'amount',
        'status',
        'due_date',
        'payment_url',
        'snap_token',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function clientOrder()
    {
        return $this->belongsTo(ClientOrder::class, 'client_order_id');
    }
}
