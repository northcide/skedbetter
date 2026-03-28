<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamps();

            $table->index(['email', 'league_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_invitations');
    }
};
