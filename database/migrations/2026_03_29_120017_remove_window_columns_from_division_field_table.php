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
        Schema::table('division_field', function (Blueprint $table) {
            $table->dropColumn(['priority', 'booking_window_type', 'booking_opens_date', 'booking_opens_days']);
        });
    }

    public function down(): void
    {
        Schema::table('division_field', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->nullable();
            $table->string('booking_window_type')->nullable();
            $table->date('booking_opens_date')->nullable();
            $table->unsignedInteger('booking_opens_days')->nullable();
        });
    }
};
