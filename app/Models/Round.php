<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    /** @use HasFactory<\Database\Factories\RoundFactory> */
    use HasFactory;
    protected $fillable = ['user_id', 'name', 'description', 'active'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parcelTypes()
    {
        return $this->hasMany(ParcelType::class);
    }
}
