<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminFieldTypeController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $fieldTypes = FieldType::withCount('fields')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/FieldTypes', [
            'fieldTypes' => $fieldTypes,
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:field_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        $maxSort = FieldType::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSort + 1;

        FieldType::create($validated);

        return back()->with('success', "Field type \"{$validated['name']}\" created.");
    }

    public function update(Request $request, FieldType $fieldType)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:field_types,name,' . $fieldType->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $fieldType->update($validated);

        return back()->with('success', "Field type \"{$fieldType->name}\" updated.");
    }

    public function destroy(Request $request, FieldType $fieldType)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        if ($fieldType->fields()->count() > 0) {
            return back()->with('error', "Cannot delete \"{$fieldType->name}\" — it is assigned to {$fieldType->fields()->count()} field(s).");
        }

        $name = $fieldType->name;
        $fieldType->delete();

        return back()->with('success', "Field type \"{$name}\" deleted.");
    }
}
