<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'reference', 'description', 'total_amount'];

    public function details()
    {
        return $this->hasMany(JournalDetail::class);
    }
}
