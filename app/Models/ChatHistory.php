<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;

    protected $table = 'chat_histories';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'intent',
        'user_message',
        'agent_response',
        'agent_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
