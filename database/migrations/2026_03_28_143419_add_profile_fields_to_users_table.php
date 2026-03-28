<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('timezone')->default('America/Chicago')->after('phone');
            $table->boolean('is_superadmin')->default(false)->after('timezone');
            $table->timestamp('last_login_at')->nullable()->after('is_superadmin');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'timezone', 'is_superadmin', 'last_login_at']);
            $table->dropSoftDeletes();
        });
    }
};
