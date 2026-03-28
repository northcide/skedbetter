<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blackout_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type'); // league, location, field
            $table->unsignedBigInteger('scope_id');
            $table->string('name');
            $table->string('reason')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('recurrence')->default('none');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['league_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blackout_rules');
    }
};
