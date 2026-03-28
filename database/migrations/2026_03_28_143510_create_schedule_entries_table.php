<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type')->default('practice');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->string('status')->default('confirmed');
            $table->foreignId('recurrence_group_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Critical indexes for conflict detection and calendar views
            $table->index(['field_id', 'date', 'start_time', 'end_time'], 'schedule_field_time_idx');
            $table->index(['team_id', 'date'], 'schedule_team_date_idx');
            $table->index(['league_id', 'season_id', 'date'], 'schedule_league_season_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
    }
};
