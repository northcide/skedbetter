<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduling_constraints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('constraint_type');
            $table->json('value');
            $table->string('scope_type')->nullable(); // league, division, team
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['league_id', 'season_id', 'constraint_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduling_constraints');
    }
};
