<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_readings', function (Blueprint $table) {
            $table->id();
            $table->decimal('temperature_c', 5, 2);
            $table->decimal('humidity_percent', 5, 2);
            $table->decimal('pressure_hpa', 7, 2);
            $table->string('source', 20);
            $table->timestamp('recorded_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('recorded_at');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};
