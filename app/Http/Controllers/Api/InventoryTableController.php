<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryTable;
use App\Models\Product;
use App\Models\MasterList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryTableController extends Controller
{
    public function fetch_inventory_details(Request $request)
    {
        $company_id = $request->input('branch_id');
           

        $rs = InventoryTable::select('*')->where('other_packing','Assets')->where('Branch',$company_id)->where('Stock',1)->get();
        return response()->json([
            'status' => 200,
            'message' => 'Inventory details fetched',
            'data' => $rs,
        ]);
    }
    public function getTempCode(Request $request)
    {
        // Validate input
        $request->validate([
            'tracking_name' => 'required|string'
        ]);

        $trackingName = $request->tracking_name;

        // Find product_id from inventory
        $product = InventoryTable::where('tracking_name', $trackingName)->first();
        //  return $product;
        if (!$product) {
            return response()->json(['product_code' => '']);
        }

        // Get product details
        $productDetails = Product::where('Product_id', $product->Product_id)->first();
          
        if (!$productDetails) {
            return response()->json(['product_code' => 'empty']);
        }

        // Decode JSON temperature control IDs
        $tempIds = json_decode($productDetails->temperature_control_id, true);
        $tempArr = [];
        $allTemp = [];

        if (!empty($tempIds)) {
            foreach ($tempIds as $temp) {
                $masterList = MasterList::where('list_id', $temp)->first();
                if ($masterList) {
                    $tempArr[] = ['id' => $temp, 'name' => $masterList->display_as];
                    $allTemp[] = $masterList->display_as;
                }
            }
        }

        // Return JSON response
        return response()->json([
            
            'productID' => $productDetails->Product_id,
            'product_code' => $productDetails,
            'alltemp' => $allTemp,
            'P_is' => $productDetails->Product_id,
            'P_code' => $productDetails->Product_code,
            'P_nmae' => $productDetails->Product_name,
            'temp_arr' => $tempArr,
            'temp_ids' => $tempIds
        ]);
    }
    public function generateTrackingID()
    {
        $existingTrackingCodes = DB::table('CTU_master_details')
            ->pluck('tracking_code')
            ->map(function ($code) {
                return substr($code, -5);
            })
            ->toArray();

        do {
            $randomNumber = mt_rand(1000000, 9999999);
        } while (in_array($randomNumber, $existingTrackingCodes));

        return response()->json(['tracking_id' => $randomNumber]);
    }


    
}
