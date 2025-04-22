<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Plan;
use DB;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function active()
    {
        \Log::info('Active method called');
        try {
            $plans = DB::connection('central_tenant_db')->table('plans')
                ->where('status', 'active')
                ->select('id', 'name')
                ->get();
    
            if ($plans->isEmpty()) {
                return response()->json(['message' => 'No active plans found'], 404);
            }
    
            return response()->json($plans);
        } catch (\Exception $e) {
            \Log::error('Error fetching active plans', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to fetch active plans'], 500);
        }
    }
        public function index(Request $request)
        {
            $query = Plan::query();
    
            // Apply filters if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('billing_cycle')) {
                $query->where('billing_cycle', $request->billing_cycle);
            }
    
            $perPage = $request->query('per_page', 10); // Default to 10, adjustable via query param
            $plans = $query->paginate($perPage);
    
            return response()->json([
                'data' => $plans->items(),
                'meta' => [
                    'total' => $plans->total(),
                    'current_page' => $plans->currentPage(),
                    'per_page' => $plans->perPage(),
                    'last_page' => $plans->lastPage(), // Added for frontend pagination
                ],
            ], 200);
        }

    public function show($id)
    {
        $plan = Plan::with('modules')->findOrFail($id);
        return response()->json(['data' => $plan], 200);
    }

    
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,annually',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer|min:1',
            'trial_period' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,archived',
            'whitelabeling' => 'boolean', // Validate whitelabeling as boolean
        ]);

        $plan = Plan::create($validated);

        return response()->json(['data' => $plan, 'message' => 'Plan created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,annually',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer|min:1',
            'trial_period' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,archived',
            'whitelabeling' => 'boolean', // Validate whitelabeling as boolean
        ]);

        $plan = Plan::findOrFail($id);
        $plan->update($validated);

        return response()->json(['data' => $plan, 'message' => 'Plan updated successfully'], 200);
    }

    public function destroy(Request $request,$id)
    {
       
    $plan = Plan::findOrFail($id);
    $currentStatus = $plan->status;
    // print_r($currentStatus);    
    $newStatus = $request->input('status');

    if ($currentStatus === $newStatus) {
        return response()->json(['message' => 'Plan status is already ' . $currentStatus], 400);
    }

    // Example business logic: Prevent direct archived → active transition
    if ($currentStatus === 'archived' && $newStatus === 'active') {
        return response()->json(['message' => 'Cannot directly activate an archived plan. Please set it to inactive first.'], 400);
    }

    // Update the plan status
    $plan->update(['status' => $newStatus]);

    return response()->json([
        'data' => $plan,
        'message' => 'Plan status updated to ' . $newStatus . ' successfully',
    ], 200);

    }
}