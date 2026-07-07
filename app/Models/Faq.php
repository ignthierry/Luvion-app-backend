<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'answer_id',
        'question_en',
        'answer_en',
        'is_active',
        'sort_order',
    ];
}
