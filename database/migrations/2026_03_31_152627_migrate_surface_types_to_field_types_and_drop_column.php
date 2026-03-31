<?php

use App\Models\FieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seed the old enum values as field types
        $surfaceTypes = ['Grass', 'Turf', 'Indoor', 'Court'];
        $order = 1;
        foreach ($surfaceTypes as $name) {
            FieldType::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $order++, 'is_active' => true]
            );
        }

        // Migrate existing field surface_type values to field_type_id
        $fieldTypes = FieldType::all()->keyBy(fn ($ft) => strtolower($ft->name));
        DB::table('fields')->whereNotNull('surface_type')->get()->each(function ($field) use ($fieldTypes) {
            $key = strtolower($field->surface_type);
            if (isset($fieldTypes[$key])) {
                DB::table('fields')->where('id', $field->id)->update(['field_type_id' => $fieldTypes[$key]->id]);
            }
        });

        // Drop the old column
        Schema::table('fields', function (Blueprint $table) {
            $table->dropColumn('surface_type');
        });
    }

    public function down(): void
    {
        Schema::table('fields', function (Blueprint $table) {
            $table->string('surface_type')->nullable()->after('name');
        });

        // Restore surface_type from field_type_id
        $fieldTypes = FieldType::all()->pluck('name', 'id');
        DB::table('fields')->whereNotNull('field_type_id')->get()->each(function ($field) use ($fieldTypes) {
            $name = $fieldTypes[$field->field_type_id] ?? null;
            if ($name && in_array(strtolower($name), ['grass', 'turf', 'indoor', 'court'])) {
                DB::table('fields')->where('id', $field->id)->update(['surface_type' => strtolower($name)]);
            }
        });
    }
};
