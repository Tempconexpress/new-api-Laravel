<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Schema;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\TemperatureMaster;
use App\Models\MasterList;
use App\Models\TempRange;
use App\Http\Controllers\MasterlistController;

class TemperatureMasterController extends Controller
{
    
    public function add_update(Request $request)
{
    // Validate input
    $request->validate([
        'temperature_id' => 'required|integer',
        'temperature_from' => 'required|numeric',
        'temperature_to' => 'required|numeric',
    ]);

    $data = $request->only([
        'temperature_id', 'temperature_from', 'temperature_to'
    ]);

    $id = $request->input('id');
    $isUpdate = $id > 0;

    $qtype = $isUpdate ? 'Updated' : 'Inserted';

    if ($isUpdate) {
        // Find and update existing record
        $tempRange = TempRange::findOrFail($id);
        $tempRange->update($data);
    } else {
        // Create a new record
        $tempRange = TempRange::create($data);
    }

    return response()->json([
        'status' => 200,
        'message' => "Temperature Master Record {$qtype}!",
        'data' => $tempRange
    ]);
}

    public function fetch(Request $request)
    {
        try {
            
            $tempRanges = TempRange::leftJoin('master_lists', 'master_lists.list_id', '=', 'temp_range.temperature_id')
                ->select('temp_range.is_active','temp_range.id', 'temp_range.temperature_id', 'temp_range.temperature_from', 'temp_range.temperature_to', 'master_lists.item_name')
                
                ->get();
    
            // Format output   
            $formattedTempRanges = $tempRanges->map(function ($tempRange) {
                return [
                    'id' => $tempRange->id,
                    'temperature_id' => $tempRange->temperature_id,
                    'temperature_from' => $tempRange->temperature_from,
                    'temperature_to' => $tempRange->temperature_to,
                    'item_name' => str_replace('&deg;', '°', html_entity_decode($tempRange->item_name) ?? 'N/A'),  
                    'is_active'=>  $tempRange->is_active,// Avoid null errors
                ];
            });
            
    
            return response()->json(['status' => 200, 'data' => $formattedTempRanges]);
    
        } catch (QueryException $e) {
            return response()->json(['status' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function toggle_status(Request $request)
    {
        // Validate input
        
         
        // Find the record by ID
        $tempRange = TempRange::findOrFail($request->id);

        if($request->is_active == 1 ){
            $is_Active = 0; 
        }else{
            $is_Active = 1; 
        }
    
        // Update the is_active status
        $tempRange->is_active = $is_Active;
        $tempRange->save();
    
        return response()->json([
            'status' => 200,
            'message' => 'Status updated successfully',
            'data' => $tempRange
        ]);
    }
    
    

  
        
        

   
     
    
}
