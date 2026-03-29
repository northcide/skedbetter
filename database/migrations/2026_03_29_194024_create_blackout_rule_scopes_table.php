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
        Schema::create('blackout_rule_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blackout_rule_id')->constrained('blackout_rules')->cascadeOnDelete();
            $table->string('scope_type'); // location, field
            $table->unsignedBigInteger('scope_id');
            $table->timestamps();

            $table->unique(['blackout_rule_id', 'scope_type', 'scope_id']);
        });

        // Backfill existing scope_id values
        $rules = DB::table('blackout_rules')->whereNotNull('scope_id')->where('scope_type', '!=', 'league')->get();
        foreach ($rules as $rule) {
            DB::table('blackout_rule_scopes')->insert([
                'blackout_rule_id' => $rule->id,
                'scope_type' => $rule->scope_type,
                'scope_id' => $rule->scope_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('blackout_rules', function (Blueprint $table) {
            $table->dropColumn('scope_id');
        });
    }

    public function down(): void
    {
        Schema::table('blackout_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('scope_id')->nullable();
        });

        // Restore first scope_id from pivot
        $scopes = DB::table('blackout_rule_scopes')
            ->select('blackout_rule_id', DB::raw('MIN(scope_id) as scope_id'))
            ->groupBy('blackout_rule_id')
            ->get();
        foreach ($scopes as $s) {
            DB::table('blackout_rules')->where('id', $s->blackout_rule_id)->update(['scope_id' => $s->scope_id]);
        }

        Schema::dropIfExists('blackout_rule_scopes');
    }
};
