<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'company_name',
        'email',
        'phone',
        'website',
        'plan_name',
        'billing_cycle',
        'users_count',
        'purpose',
        'addons',
        'integration_needs',
        'subdomain',
        'logo_path',
        'theme_color',
        'notes',
        'timeline',
        'status',
        'payment_status',
        'billing_due_day',
        'pricing_payment',
        'payment_url',
        'snap_token',
    ];

    protected $casts = [
        'addons' => 'array',
        'timeline' => 'date',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_order_id');
    }
}
