<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function show($id)
    {
        $plan = SubscriptionPlan::with('modules')->find($id);

        if (!$plan) {
            return response()->json(['error' => 'Plan not found'], 404);
        }

        return response()->json($plan);
    }


    public function checkModuleAccess($planId, $moduleId)
{
    $plan = SubscriptionPlan::find($planId);

    if (!$plan) {
        return response()->json(['error' => 'Plan not found'], 404);
    }

    $module = $plan->modules()->where('module_id', $moduleId)->first();

    if (!$module) {
        return response()->json(['error' => 'Module not found in this plan'], 404);
    }

    $accessLevel = $module->pivot->access_level; // Get the access level

    if ($accessLevel == 'admin' || $accessLevel == 'edit') {
        return response()->json(['message' => 'Access granted to the module']);
    } else {
        return response()->json(['error' => 'Access denied to the module'], 403);
    }
}

}
