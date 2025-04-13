<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatePeriod extends Model
{
    /** @use HasFactory<\Database\Factories\DatePeriodFactory> */
    use HasFactory;
    protected $fillable = ['name', 'start_date', 'end_date'];
    protected $dates = ['start_date', 'end_date'];
}
