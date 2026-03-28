<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which divisions are allowed to use which fields
        // If no rows exist for a field, ALL divisions can use it (open access)
        // If any rows exist, ONLY those divisions can use it (restricted)
        Schema::create('division_field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('max_weekly_slots')->nullable(); // per-division weekly limit on this field
            $table->timestamps();

            $table->unique(['division_id', 'field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('division_field');
    }
};
