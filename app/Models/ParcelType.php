<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelType extends Model
{
    /** @use HasFactory<\Database\Factories\ParcelTypeFactory> */
    use HasFactory;

    protected $fillable = ['round_id', 'name', 'max_weight', 'max_length', 'rate'];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
