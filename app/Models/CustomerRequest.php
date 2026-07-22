<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'client_order_id',
        'request_type',
        'title',
        'description',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clientOrder()
    {
        return $this->belongsTo(ClientOrder::class);
    }
}
