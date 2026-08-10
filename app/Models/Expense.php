<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'reference',
        'category',
        'description',
        'amount',
        'payment_method',
        'account_id',
        'journal_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Generate a unique reference for an expense.
     */
    public static function generateReference()
    {
        return 'BIAYA-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}