<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubModule;
use Illuminate\Http\Request;

class SubModuleController extends Controller
{
    public function index()
    {
        $subModules = SubModule::with('module', 'childModules')->get();
        return response()->json($subModules);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sub_modules,slug',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json',
            'created_by' => 'nullable|integer'
        ]);

        $subModule = SubModule::create($data);
        return response()->json($subModule, 201);
    }

    public function update(Request $request, SubModule $subModule)
    {
        $data = $request->validate([
            'module_id' => 'exists:modules,id',
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:sub_modules,slug,' . $subModule->id,
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json',
            'updated_by' => 'nullable|integer'
        ]);

        $subModule->update($data);
        return response()->json($subModule);
    }

    // public function destroy(SubModule $subModule)
    // {
    //     $subModule->delete();
    //     return response()->json(['message' => 'Sub-Module deleted successfully']);
    // }

    public function toggleStatus(SubModule $subModule)
{
    // Toggle is_active status
    $subModule->is_active = !$subModule->is_active;
    $subModule->save();

    return response()->json([
        'message' => 'subModule status updated successfully',
        'is_active' => $subModule->is_active
    ]);
}
}
