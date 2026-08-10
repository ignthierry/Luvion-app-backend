<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'type', 'description', 'is_active'];

    public function journalDetails()
    {
        return $this->hasMany(JournalDetail::class);
    }
}
