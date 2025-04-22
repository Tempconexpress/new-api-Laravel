<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildModule;
use App\Models\Module;
use App\Models\SubModule;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::with('subModules')->get();
        return response()->json($modules);
    }
    public function updateHierarchy(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $modules = $request->input('modules', []);
            
            foreach ($modules as $index => $moduleData) {
                if (!isset($moduleData['id'])) {
                    return response()->json([
                        'message' => 'Invalid module data',
                        'error' => 'Module ID is missing in request data'
                    ], 400);
                }
                $this->updateModule($moduleData, $index + 1, null);
            }

            DB::commit();
            
            $updatedModules = Module::with(['subModules.childModules'])
                ->orderBy('order')
                ->get();

            return response()->json([
                'message' => 'Hierarchy updated successfully',
                'modules' => $updatedModules
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update hierarchy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateModule($moduleData, $order, $parentId = null)
    {
        $module = Module::find($moduleData['id']);
        if (!$module) {
            throw new \Exception("Module with ID {$moduleData['id']} not found");
        }

        $module->update([
            'order' => $order,
            'is_active' => $moduleData['is_active'] ?? true
        ]);

        if (isset($moduleData['sub_modules']) && is_array($moduleData['sub_modules'])) {
            foreach ($moduleData['sub_modules'] as $subIndex => $subModuleData) {
                if (!isset($subModuleData['id'])) {
                    throw new \Exception("SubModule ID missing in data");
                }
                $this->updateSubModule($subModuleData, $subIndex + 1, $module->id);
            }
        }
    }

    private function updateSubModule($subModuleData, $order, $moduleId)
    {
        $subModule = SubModule::find($subModuleData['id']);
        if (!$subModule) {
            throw new \Exception("SubModule with ID {$subModuleData['id']} not found");
        }

        $subModule->update([
            'module_id' => $moduleId,
            'order' => $order,
            'is_active' => $subModuleData['is_active'] ?? true
        ]);

        if (isset($subModuleData['child_modules']) && is_array($subModuleData['child_modules'])) {
            foreach ($subModuleData['child_modules'] as $childIndex => $childModuleData) {
                if (!isset($childModuleData['id'])) {
                    throw new \Exception("ChildModule ID missing in data");
                }
                $this->updateChildModule($childModuleData, $childIndex + 1, $subModule->id);
            }
        }
    }

    private function updateChildModule($childModuleData, $order, $subModuleId)
    {
        $childModule = ChildModule::find($childModuleData['id']);
        if (!$childModule) {
            throw new \Exception("ChildModule with ID {$childModuleData['id']} not found");
        }

        $childModule->update([
            'sub_module_id' => $subModuleId,
            'order' => $order,
            'is_active' => $childModuleData['is_active'] ?? true
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules,slug',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json'
        ]);

        $module = Module::create($data);
        return response()->json($module, 200);
    }

    public function update(Request $request, Module $module)
    {
        $data = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:modules,slug,' . $module->id,
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'extra_config' => 'nullable|json'
        ]);

        $module->update($data);
        return response()->json($module);
    }

    public function toggleStatus(Module $module)
{
    // Toggle is_active status
    $module->is_active = !$module->is_active;
    $module->save();

    return response()->json([
        'message' => 'Module status updated successfully',
        'is_active' => $module->is_active
    ]);
}

public function getModuleMasterTree(): JsonResponse
{
    $modules = Module::with([
        'subModules' => function ($query) {
            $query->with(['childModules'])->orderBy('order');
        }
    ])
    ->orderBy('order')
    ->get();

    // Remove duplicates in child_modules
    $modules->each(function ($module) {
        $module->subModules->each(function ($subModule) {
            $uniqueChildModules = $subModule->childModules->unique('id')->values();
            $subModule->setRelation('childModules', $uniqueChildModules);
        });
    });

    \Log::info('Module Master Tree Data:', $modules->toArray());
    return response()->json($modules);
}
}
