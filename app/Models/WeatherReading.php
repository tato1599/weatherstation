<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'temperature_c',
        'temperature_bmp280_c',
        'humidity_percent',
        'pressure_hpa',
        'altitude_m',
        'source',
        'recorded_at',
        'meta',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'meta' => 'array',
    ];
}
