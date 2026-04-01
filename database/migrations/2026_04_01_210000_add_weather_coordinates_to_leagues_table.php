<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->decimal('weather_latitude', 10, 7)->nullable()->after('public_token');
            $table->decimal('weather_longitude', 10, 7)->nullable()->after('weather_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['weather_latitude', 'weather_longitude']);
        });
    }
};
