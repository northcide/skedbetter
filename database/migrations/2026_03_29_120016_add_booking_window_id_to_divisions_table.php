<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->foreignId('booking_window_id')->nullable()->after('scheduling_priority')
                ->constrained('booking_windows')->nullOnDelete();
            $table->dropColumn('scheduling_priority');
        });
    }

    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->dropForeign(['booking_window_id']);
            $table->dropColumn('booking_window_id');
            $table->unsignedTinyInteger('scheduling_priority')->nullable();
        });
    }
};
