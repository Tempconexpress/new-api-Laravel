<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanModule;
use App\Models\Plan;
use App\Models\Module;
use App\Models\SubModule;
use App\Models\ChildModule;
use Illuminate\Http\Request;

class PlanModuleController extends Controller
{
    public function index($planId)
    {
        $plan = Plan::findOrFail($planId);

        // Fetch all top-level modules associated with the plan
        $modules = Module::with(['subModules.childModules'])
            ->whereHas('planModules', function ($query) use ($planId) {
                $query->where('plan_id', $planId);
            })
            ->orderBy('order')
            ->get();

            // Transform the data to include PlanModule pivot information
            $transformedModules = $modules->map(function ($module) use ($planId) {
                return $this->transformModule($module, $planId);
            })->values()->all();
            // print_r($transformedModules);die;

        return response()->json([
            'data' => $transformedModules,
            'message' => 'Plan module tree retrieved successfully',
        ], 200);
    }

    private function transformModule($module, $planId)
    {
        $planModule = PlanModule::where('plan_id', $planId)
            ->where('module_id', $module->id)
            ->where('module_type', 'module')
            ->first();

        return [
            'id' => $module->id,
            'name' => $module->name,
            'is_active' => $planModule ? (bool)$planModule->is_active : false,
            'module_type' => 'module',
            'sub_modules' => $module->subModules->map(function ($subModule) use ($planId) {
                return $this->transformSubModule($subModule, $planId);
            })->values()->all(),
        ];
    }

    private function transformSubModule($subModule, $planId)
    {
        $planModule = PlanModule::where('plan_id', $planId)
            ->where('module_id', $subModule->id)
            ->where('module_type', 'submodule')
            ->first();

        return [
            'id' => $subModule->id,
            'name' => $subModule->name,
            'is_active' => $planModule ? (bool)$planModule->is_active : false,
            'module_type' => 'submodule',
            'module_id' => $subModule->module_id,
            'child_modules' => $subModule->childModules->map(function ($childModule) use ($planId) {
                return $this->transformChildModule($childModule, $planId);
            })->values()->all(),
        ];
    }

    private function transformChildModule($childModule, $planId)
    {
        $planModule = PlanModule::where('plan_id', $planId)
            ->where('module_id', $childModule->id)
            ->where('module_type', 'childmodule')
            ->first();

        return [
            'id' => $childModule->id,
            'name' => $childModule->name,
            'is_active' => $planModule ? (bool)$planModule->is_active : false,
            'module_type' => 'childmodule',
            'sub_module_id' => $childModule->sub_module_id,
        ];
    }

    public function store(Request $request, $planId)
    {
        \Log::info("Received planId in store: " . $planId);

        $validated = $request->validate([
            'module_id' => 'required|numeric', // Validate as numeric
            'is_active' => 'boolean',
            'parent_module_id' => 'nullable|numeric', // For sub-modules/child-modules
            'module_type' => 'required|in:module,submodule,childmodule', // Use module_type instead of type
        ]);

        if (!is_numeric($planId) || $planId <= 0) {
            return response()->json(['message' => 'Invalid plan ID'], 400);
        }

        $plan = Plan::findOrFail($planId);

        // Determine the type of module and validate the module_id
        $moduleType = $validated['module_type'];
        $moduleId = (int)$validated['module_id'];
        $parentModuleId = $validated['parent_module_id'] ? (int)$validated['parent_module_id'] : null;

        if (!$this->validateModuleId($moduleId, $moduleType, $parentModuleId)) {
            return response()->json([
                'message' => 'The selected module id is invalid.',
                'errors' => ['module_id' => ['The selected module id is invalid.']],
            ], 422);
        }

        $planModule = new PlanModule();
        $planModule->plan_id = (int)$planId;
        $planModule->module_type = $moduleType;
        $planModule->module_id = $moduleId;
        $planModule->is_active = $validated['is_active'] ?? true;
        $planModule->parent_module_id = $parentModuleId;
        $planModule->save();

        return response()->json([
            'data' => $planModule,
            'message' => 'Module assigned to plan successfully',
        ], 201);
    }

    public function update(Request $request, $planId, $moduleId)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'parent_module_id' => 'nullable|numeric', // Optional for hierarchy updates
            'module_type' => 'required|in:module,submodule,childmodule', // Use module_type instead of type
        ]);

        $planModule = PlanModule::where('plan_id', $planId)->where('module_id', $moduleId)->firstOrFail();

        // Validate the module_id based on the provided type and parent
        if (!$this->validateModuleId($moduleId, $validated['module_type'], $validated['parent_module_id'])) {
            return response()->json([
                'message' => 'The selected module id is invalid.',
                'errors' => ['module_id' => ['The selected module id is invalid.']],
            ], 422);
        }

        $planModule->update([
            'is_active' => $validated['is_active'],
            'parent_module_id' => $validated['parent_module_id'],
            'module_type' => $validated['module_type'], // Update module_type
        ]);

        return response()->json([
            'data' => $planModule,
            'message' => 'Module association updated successfully',
        ], 200);
    }

    public function destroy($planId, $moduleId)
    {
        $planModule = PlanModule::where('plan_id', $planId)->where('module_id', $moduleId)->firstOrFail();

        $planModule->update(['is_active' => 0]);

        return response()->json(['message' => 'Module deactivated from plan successfully'], 200);
    }

    private function validateModuleId($moduleId, $moduleType, $parentModuleId = null)
    {
        switch ($moduleType) {
            case 'module':
                return Module::where('id', $moduleId)->whereNull('deleted_at')->exists();
            case 'submodule':
                return SubModule::where('id', $moduleId)->where('module_id', $parentModuleId)->whereNull('deleted_at')->exists();
            case 'childmodule':
                return ChildModule::where('id', $moduleId)->where('sub_module_id', $parentModuleId)->whereNull('deleted_at')->exists();
            default:
                return false;
        }
    }

    private function getModuleDataWithHierarchy($planModule)
    {
        $moduleType = $planModule->module_type;
        $moduleId = $planModule->module_id;
        $parentModuleId = $planModule->parent_module_id;

        $moduleData = null;
        $subModules = [];
        $childModules = [];

        switch ($moduleType) {
            case 'module':
                $moduleData = Module::withTrashed()->find($moduleId);
                if ($moduleData) {
                    $subModules = $this->buildSubModuleTree($moduleId, $planModule->plan_id);
                }
                break;
            case 'submodule':
                $moduleData = SubModule::withTrashed()->find($moduleId);
                if ($moduleData) {
                    $childModules = $this->buildChildModuleTree($moduleId, $planModule->plan_id);
                }
                break;
            case 'childmodule':
                $moduleData = ChildModule::withTrashed()->find($moduleId);
                break;
        }
// echo $moduleId, $moduleType, $planModule->is_active, $parentModuleId, $moduleData, $subModules, $childModules;die;
        return [
            'module_id' => $moduleId,
            'module_type' => $moduleType,
            'is_active' => $planModule->is_active,
            'parent_module_id' => $parentModuleId,
            'name' => $moduleData ? $moduleData->name : null,
            'sub_modules' => $subModules,
            'child_modules' => $childModules,
        ];
    }

    private function buildSubModuleTree($moduleId, $planId)
    {
        $subModules = SubModule::where('module_id', $moduleId)->with('childModules')->withTrashed()->get();
        if ($subModules->isEmpty()) {
            return [];
        }

        return $subModules->map(function ($subModule) use ($planId) {
            $planModule = PlanModule::where('plan_id', $planId)->where('module_id', $subModule->id)->first();
            return [
                'id' => $subModule->id,
                'name' => $subModule->name,
                'is_active' => $planModule ? $planModule->is_active : false,
                'parent_module_id' => $subModule->module_id,
                'child_modules' => $this->buildChildModuleTree($subModule->id, $planId),
            ];
        })->values()->all();
    }

    private function buildChildModuleTree($subModuleId, $planId)
    {
        $childModules = ChildModule::where('sub_module_id', $subModuleId)->withTrashed()->get();
        if ($childModules->isEmpty()) {
            return [];
        }

        return $childModules->map(function ($childModule) use ($planId) {
            $planModule = PlanModule::where('plan_id', $planId)->where('module_id', $childModule->id)->first();
            return [
                'id' => $childModule->id,
                'name' => $childModule->name,
                'is_active' => $planModule ? $planModule->is_active : false,
                'parent_module_id' => $childModule->sub_module_id,
            ];
        })->values()->all();
    }
}