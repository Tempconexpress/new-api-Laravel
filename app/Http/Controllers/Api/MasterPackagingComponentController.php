<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterPackagingComponent;
use Illuminate\Validation\ValidationException;

class MasterPackagingComponentController extends Controller
{
    /**
     * Display a listing of the packaging components.
     */
    public function index()
    {
        try {
            $components = MasterPackagingComponent::all();
            return response()->json($components, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created packaging component in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'packaging_name' => 'required|string|max:255',
            'shipment_temp' => 'nullable|numeric',
            'gelpack_names' => 'nullable|string|max:255',
            'gelpack_count' => 'nullable|integer',
            'cond_temp' => 'nullable|numeric',
            'cond_time' => 'nullable|integer',
            'is_active' => 'required|boolean',
            'deleted_by' => 'nullable|integer',
            'updated_by' => 'nullable|integer',
            'created_by' => 'nullable|integer',
        ]);

        try {
            $component = MasterPackagingComponent::create($validatedData);
            return response()->json($component, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified packaging component.
     */
    public function show($id)
    {
        try {
            $component = MasterPackagingComponent::findOrFail($id);
            return response()->json($component, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified packaging component in storage.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'packaging_name' => 'required|string|max:255',
            'shipment_temp' => 'nullable|numeric',
            'gelpack_names' => 'nullable|string|max:255',
            'gelpack_count' => 'nullable|integer',
            'cond_temp' => 'nullable|numeric',
            'cond_time' => 'nullable|integer',
            'is_active' => 'required|boolean',
            'deleted_by' => 'nullable|integer',
            'updated_by' => 'nullable|integer',
            'created_by' => 'nullable|integer',
        ]);

        try {
            $component = MasterPackagingComponent::findOrFail($id);
            $component->update($validatedData);
            return response()->json($component, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified packaging component from storage.
     */
    public function destroy($id)
    {
        try {
            $component = MasterPackagingComponent::findOrFail($id);
            $component->delete();
            return response()->json(['message' => 'Packaging component deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
