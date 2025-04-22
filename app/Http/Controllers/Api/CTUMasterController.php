<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\CTUMasterDetail;
use App\Models\UserMaster;
use App\Models\InventoryTable;
use App\Models\CTURacksCapacity;
use App\Models\MasterCompany;
use App\Models\Product;
use App\Models\MasterList;
use App\Models\CTUGelSub;

class CTUMasterController extends Controller
{
    public function getBranch(Request $request)
    {

      
        
        $email = $request->input('email');
        session(['user_email' => $email]);
        return $email;

        // Get the user ID from the session
        
    }

    // Helper method for tracking ID generation
    // private function generateTrackingId()
    // {
    //     return strtoupper(bin2hex(random_bytes(8)));  // Example of generating a tracking ID
    // }
    public function generateTrackingID()
    {
        $existingTrackingCodes = CTUMasterDetail::pluck('tracking_code')->map(function ($code) {
                return substr($code, -5);
            })
            ->toArray();

        do {
            $randomNumber = mt_rand(1000000, 9999999);
        } while (in_array($randomNumber, $existingTrackingCodes));

        return response()->json(['tracking_id' => $randomNumber]);
    }
    public function saveCTUForm(Request $request)
    {    
      
        $tempidsJSON = $request->input('temp_ids');
        // Get user ID from access_key & user_name
        $user = UserMaster::where('user_name', $request->input('user_name'))->first();
        // where('access_key', $request->input('access_key'))
        if (!$user) {
            return response()->json(['error' => 'Invalid User'], 400);
        }
        // Assign user ID
        $user_ID = $user->user_id;
        $oldTrakCtu = $request->input('old_trak_ctu');

        // Fetch records from ctu_master_details
        $tm = CtuMasterDetail::where('cnnect_id', $oldTrakCtu)->get();
    
        
        if (!$request->input('old_trak_ctu')) {
            // **INSERT new CTU record**
            $ctu = CTUMasterDetail::create([
                'cnnect_id' => $request->input('cnnect_id'),
                'tracking_code' => $request->input('traki_ctu'),
                'branch_id' => intval($request->input('branch')),
                'CTU_id' => intval($request->input('CTUName_id')),
                'CTU_product_code' => $request->input('ctu_product_code'),
                'CTU_Temperature' => is_array($tempidsJSON) ? json_encode($tempidsJSON) : $tempidsJSON,
                'CTU_calibration_date' => $request->input('ctu_calibration_date'),
                'CTU_Calibration_due_date' => $request->input('ctu_calibration_due_date'),
                'CTU_Mapping_date' => $request->input('ctu_mapping_date'),
                'CTU_Mapping_due_date' => $request->input('ctu_mapping_due_date'),
                'Number_of_Racks' => intval($request->input('rowcount')),
                'barcode' => null,
                'flag' => 0,
                'created_user' => $user_ID,
                'remark' => $request->input('ctu_remarks'),
            ]);
        
        } else {
            // **CHECK if record exists before updating**
            $ctu = CTUMasterDetail::where('id', $request->input('ctu_master_id'))->first();
              
            if ($ctu) {
                // **Perform Update**
                $ctu->update([
                    'cnnect_id' => $request->input('cnnect_id'),
                    'tracking_code' => $request->input('traki_ctu'),
                    'branch_id' => intval($request->input('branch')),
                    'CTU_id' => intval($request->input('CTUName_id')),
                    'CTU_product_code' => $request->input('ctu_product_code'),
                    'CTU_Temperature' => is_array($tempidsJSON) ? json_encode($tempidsJSON) : $tempidsJSON,
                    'CTU_calibration_date' => $request->input('ctu_calibration_date'),
                    'CTU_Calibration_due_date' => $request->input('ctu_calibration_due_date'),
                    'CTU_Mapping_date' => $request->input('ctu_mapping_date'),
                    'CTU_Mapping_due_date' => $request->input('ctu_mapping_due_date'),
                    'Number_of_Racks' => intval($request->input('rowcount')),
                    'barcode' => null,
                    'flag' => 0,
                    'created_user' => $user_ID,
                    'remark' => $request->input('ctu_remarks'),
                ]);
            } else {
                return response()->json(['error' => 'Record not found'], 404);
            }
        }
        
    
        // **Update Inventory Table**
        $inventoryUpdate = InventoryTable::where('tracking_name', $request->input('trak_ctu'))
            ->limit(1)
            ->update(['Stock' => 4]);
           
            $data = [
                'id' => $request->input('id'),
                'old_trak_ctu' => $request->input('old_trak_ctu'),
                'cnnect_id' => $request->input('cnnect_id'),
                'traki_ctu' => $request->input('traki_ctu'),
                'CTUid' => $request->input('CTUid'),
                'rack_id' => $request->input('rack_id'),
                'Rackorsalves' => $request->input('Rackorsalves'),
                'Capacity' => $request->input('Capacity'),
                'rwTemperature' => $request->input('rwTemperature'),
            ];
           
            $rs = self::RacksCapacityAjax($data);
          
            return response()->json([
            'status' => '200',
            'message' =>'success',
            'CTU' => $ctu,
            'inventoryUpdate' => $inventoryUpdate,
            'Racks Capacity ' => $rs
        ]);
    }

   
   

    public function RacksCapacityAjax($data)
    {   
        $old_trak_ctu = $data['old_trak_ctu'] ?? null;
    
        // If old_trak_ctu exists, update flag to 0 for existing records
        if (!empty($old_trak_ctu)) {
            CTURacksCapacity::where('cnnect_id', $old_trak_ctu)->update(['flag' => 0]);
        }
    
        // Extract values
        $cnnect_id = $data['cnnect_id'];
        $tracking_code = $data['traki_ctu'];
        $CTU_product_code = $data['CTUid'];
        $rack_names = $data['Rackorsalves'];
        $capacities = $data['Capacity'];
        $temperatures = $data['rwTemperature'];
        $rack_ids = $data['rack_id'] ?? []; // Existing rack IDs, if present
    
        $insertData = [];
    
        foreach ($rack_names as $index => $rack) {
            $temperature = isset($temperatures[$index]) ? trim($temperatures[$index]) : 'N/A';
    
            // If rack_id exists, update the record
            if (!empty($rack_ids[$index])) {
                CTURacksCapacity::where('id', $rack_ids[$index])->update([
                    'cnnect_id' => $cnnect_id,
                    'tracking_code' => $tracking_code,
                    'CTU_product_code' => $CTU_product_code,
                    'Racks_name' => $rack,
                    'Capacity' => intval($capacities[$index] ?? 0),
                    'CTU_Temperature' => $temperature,
                    'flag' => 1,
                    'updated_at' => now(),
                ]);
            } else {
                // If no rack_id, prepare new data for insert
                $insertData[] = [
                    'cnnect_id' => $cnnect_id,
                    'tracking_code' => $tracking_code,
                    'CTU_product_code' => $CTU_product_code,
                    'Racks_name' => $rack,
                    'Capacity' => intval($capacities[$index] ?? 0),
                    'CTU_Temperature' => $temperature,
                    'flag' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    
        // Perform batch insert if there are new entries
        if (!empty($insertData)) {
            CTURacksCapacity::insert($insertData);
        }
    
        return response()->json([
            'message' => 'Data processed successfully',
            'data' => $insertData
        ]);
    }
    



public function masterFetchList(Request $request) 
{
    $userId = $request->input('user_id');

    $branchArray = UserMaster::where('user_id', $userId)
        ->pluck('user_branches')
        ->first();

    if (!$branchArray) {
        return response()->json(['data' => []]);
    }

    $companyIds = MasterCompany::where('company_type', 'Branch')
        ->whereIn('company_urn', $branchArray)
        ->pluck('company_id')
        ->toArray();

    if (empty($companyIds)) {
        return response()->json(['data' => []]);
    }

    $ctuDetails = CTUMasterDetail::whereIn('branch_id', $companyIds)->get();

    if ($ctuDetails->isEmpty()) {
        return response()->json(['data' => []]);
    }

    $groupedData = [];

    foreach ($ctuDetails as $ctud) {
        $userName = UserMaster::where('user_id', $ctud->created_user)
            ->value('user_name');
        $productName = Product::where('Product_id', $ctud->CTU_id)
            ->value('Product_name') ?? '';
        $branchName = MasterCompany::where('company_id', $ctud->branch_id)
            ->value('display_name');

        $ctuRacks = CTURacksCapacity::where('cnnect_id', $ctud->cnnect_id)->where('flag', '1')->get();

        if ($ctuRacks->isEmpty()) {
            continue;
        }

        $groupKey = $ctud->branch_id . '|' . $ctud->CTU_id . '|' . $ctud->CTU_product_code . '|' . $ctud->CTU_calibration_date . '|' . $ctud->CTU_Calibration_due_date . '|' . $ctud->CTU_Mapping_date . '|' . $ctud->CTU_Mapping_due_date . '|' . $userName;

        if (!isset($groupedData[$groupKey])) {
            $groupedData[$groupKey] = [
                'ctu_master_id' => $ctud->id,
                'cnnect_id' =>$ctud->cnnect_id,
                'branch_id' => $ctud->branch_id,
                'display_name' => $branchName,
                'tracking_code' => $ctud->tracking_code,
                'CTU_id' => $ctud->CTU_id,
                'Product_name' => $productName,    
                'CTU_product_code' => $ctud->CTU_product_code,
                'CTU_calibration_date' => $ctud->CTU_calibration_date,
                'CTU_Calibration_due_date' => $ctud->CTU_Calibration_due_date,
                'CTU_Mapping_date' => $ctud->CTU_Mapping_date,
                'CTU_Mapping_due_date' => $ctud->CTU_Mapping_due_date,
                'user_name' => $userName,
                'remark' => $ctud->remark,
                'CTU_Temperature' => $ctud->CTU_Temperature,
                'flag' =>$ctud->flag,
                'racks' => []
            ];
        }

        foreach ($ctuRacks as $rack) {
            $temperature = MasterList::where('list_id', $rack->CTU_Temperature)
                ->value('display_as') ?? '';
             
            $groupedData[$groupKey]['racks'][] = [
                'id' => $rack->id ? $rack->id :'',
                'cnnect_id' => $rack->cnnect_id ? $rack->cnnect_id :'',
                'Racks_name' => $rack->Racks_name,
                'Capacity' => $rack->Capacity,
                'display_as' => $temperature,
                'temperature' => $rack->CTU_Temperature
            ];
        }
    }

    $finalData = [];
    foreach ($groupedData as $key => $group) {
        $rowspan = count($group['racks']);
        $firstRow = true;
        foreach ($group['racks'] as $rack) {
            $row = [
                'id' => $rack['id'] ? $rack['id'] :'',
                'cnnect_id' => $rack['cnnect_id'] ? $rack['cnnect_id'] :'',
                'Racks_name' => $rack['Racks_name'],
                'Capacity' => $rack['Capacity'],
                'display_as' => $rack['display_as']
            ];

            if ($firstRow) {
                $row = array_merge($group, $row);
                $row['rowspan'] = $rowspan;
                $firstRow = false;
            }
            $finalData[] = $row;
        }
    }

    return response()->json(['data' => $finalData, 'status' => 200, 'message' => 'CTU Master list Fetched']);
}

public function enabledisable(Request $request){
    $validated = $request->validate([
        'flag' => 'required|boolean', // Ensure flag is a boolean
        'cnnect_id' => 'required|integer|exists:CTU_master_details,cnnect_id' // Ensure id exists
    ]);

    // Update the record
    $updated = CTUMasterDetail::where('cnnect_id', $validated['cnnect_id'])
        ->update(['flag' => $validated['flag']]);

    if ($updated) {
        return response()->json(['message' => 'Updated successfully'], 200);
    }
   
}


}   

