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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('contact_name_2')->nullable()->after('contact_phone');
            $table->string('contact_email_2')->nullable()->after('contact_name_2');
            $table->string('contact_name_3')->nullable()->after('contact_email_2');
            $table->string('contact_email_3')->nullable()->after('contact_name_3');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['contact_name_2', 'contact_email_2', 'contact_name_3', 'contact_email_3']);
        });
    }
};
