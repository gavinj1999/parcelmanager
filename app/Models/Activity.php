<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;
    protected $fillable = ['user_id', 'parcel_type_id', 'activity_date', 'quantity'];
    protected $dates = ['activity_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parcelType()
    {
        return $this->belongsTo(ParcelType::class);
    }

    public function parcel_type()
    {
        return $this->belongsTo(ParcelType::class, 'parcel_type_id', 'id');
    }
}
