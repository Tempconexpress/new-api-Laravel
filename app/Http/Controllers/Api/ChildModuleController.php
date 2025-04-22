<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildModule;
use Illuminate\Http\Request;

class ChildModuleController extends Controller
{
    public function index()
    {
        $childModules = ChildModule::with('subModule')->get();
        return response()->json($childModules);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sub_module_id' => 'required|exists:sub_modules,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:child_modules,slug',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json',
            'created_by' => 'nullable|integer'
        ]);

        $childModule = ChildModule::create($data);
        return response()->json($childModule, 201);
    }

    public function update(Request $request, ChildModule $childModule)
    {
        $data = $request->validate([
            'sub_module_id' => 'exists:sub_modules,id',
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:child_modules,slug,' . $childModule->id,
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json',
            'updated_by' => 'nullable|integer'
        ]);

        $childModule->update($data);
        return response()->json($childModule);
    }

    public function destroy(ChildModule $childModule)
    {
        $childModule->delete();
        return response()->json(['message' => 'Child-Module deleted successfully']);
    }

    public function toggleStatus(ChildModule $childModule)
{
    // Toggle is_active status
    $childModule->is_active = !$childModule->is_active;
    $childModule->save();

    return response()->json([
        'message' => 'childModule status updated successfully',
        'is_active' => $childModule->is_active
    ]);
}
}
