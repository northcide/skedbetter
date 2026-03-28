<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('division_field', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->nullable()->after('max_weekly_slots');
            $table->string('booking_window_type')->nullable()->after('priority'); // 'calendar' or 'rolling'
            $table->date('booking_opens_date')->nullable()->after('booking_window_type'); // for calendar mode
            $table->unsignedInteger('booking_opens_days')->nullable()->after('booking_opens_date'); // for rolling mode
        });
    }

    public function down(): void
    {
        Schema::table('division_field', function (Blueprint $table) {
            $table->dropColumn(['priority', 'booking_window_type', 'booking_opens_date', 'booking_opens_days']);
        });
    }
};
