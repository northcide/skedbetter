<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->json('available_days')->nullable()->after('notes');     // [1,2,3,4,5] = Mon-Fri
            $table->time('available_start_time')->nullable()->after('available_days');  // earliest start
            $table->time('available_end_time')->nullable()->after('available_start_time'); // latest end
            $table->unsignedInteger('slot_interval_minutes')->nullable()->after('available_end_time'); // 15, 30, 60
            $table->unsignedInteger('min_event_minutes')->nullable()->after('slot_interval_minutes');
            $table->unsignedInteger('max_event_minutes')->nullable()->after('min_event_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->dropColumn([
                'available_days', 'available_start_time', 'available_end_time',
                'slot_interval_minutes', 'min_event_minutes', 'max_event_minutes',
            ]);
        });
    }
};
