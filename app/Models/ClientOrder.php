<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientOrder extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'addons' => 'array',
        'timeline' => 'date',
    ];
}
