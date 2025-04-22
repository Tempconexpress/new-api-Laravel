<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\MasterList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

use Exception;

class MasterProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function fetch(Request $request)
{
    // Fetch all products and join with suppliers and master_company
    $products = Product::select('products.*', 'master_company.display_name','master_lists.display_as' )
        // Ensure supplier_id is correct
        ->leftJoin('master_company', 'master_company.company_id', '=', 'products.supplier')
        ->leftJoin('master_lists', 'master_lists.list_id', '=', 'products.temperature')
         // Correct join
        ->get();

    // Format the products as needed
    $formattedProducts = $products->map(function ($product) {
        // Fetch the temperature with a default value
        // $temperature = $product->temperature ?? 'N/A';
        $temperatureIds = json_decode($product->temperature, true) ?? [];

        // Fetch the corresponding `display_as` values from `master_lists`
        $temperatureNames = MasterList::whereIn('list_id', $temperatureIds)->pluck('display_as')->toArray();

        // Get the product type from MasterList
        // $productType = MasterList::where('list_id', $product->product_type)
        //     ->value('display_as','list_id') ?? 'N/A';
            $productType = MasterList::where('list_id', $product->product_type)
            ->first(['list_id', 'display_as']);
         
        return [
            'id' => $product->Product_id,
            'product_name' => $product->Product_name,
            'supplier_id' => $product->supplier, // Use supplier_id instead of supplier
            'supplier' => $product->display_name, // Get company name from master_company
            'product_code' => $product->Product_code,
            'hsn_sac_code' => $product->hsn_sac_code,
            'usage' => $product->usage ?? 'N/A',
            'price' => $product->Price,
            'cgst' => $product->cgst,
            'sgst' => $product->sgst,
            'igst' => $product->igst,
            'temperature' => str_replace('&deg;', '°', html_entity_decode(implode(", ", $temperatureNames))),
            'product_type_id'=> $productType->list_id ?? null,  // Include Product Type ID
            'product_type'   => $productType->display_as ?? 'N/A', // Product Type Name
            'description' =>$product->description,
            'temperature_id' => $temperatureIds
        ];
    });

    // Return the formatted products as JSON
    return response()->json(['status' => 200, 'data' => $formattedProducts]);
}


    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        'product_name' => 'required|string|max:255',
        'supplier' => 'required|string|max:255',
        'product_code' => 'required|string|max:255',
        'hsn_sac_code' => 'required|string|max:255',
        'usage' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'cgst' => 'required|numeric|min:0',
        'sgst' => 'required|numeric|min:0',
        'igst' => 'required|numeric|min:0',
        
        // Allow temperature to be an array
        'temperature' => 'nullable|array',        // Validate as an array
        'temperature.*' => 'integer',              // Ensure each item in the array is an integer
        'description' =>'string|max:255',
        'product_type' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Extract validated data
        $data = $validator->validated();

        // If temperature is not empty, convert to JSON format for storage
        if (!empty($data['temperature'])) {
            $data['temperature'] = json_encode($data['temperature']);
        }

        // Save the product
        $product = Product::create($data);

        return response()->json(['status' => 200,'message' => 'Product created successfully', 'product' => $product]);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to create product', 'details' => $e->getMessage()], 500);
    }
}


    /**
     * Update an existing product.
     */
    public function update(Request $request)
    {  
        ini_set('memory_limit', '512M');
        

        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'supplier' => 'required|string|max:255',
            'product_code' => 'required|string|max:255',
            'hsn_sac_code' => 'required|string|max:255',
            'usage' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cgst' => 'required|numeric|min:0',
            'sgst' => 'required|numeric|min:0',
            'igst' => 'required|numeric|min:0',
            
            // Allow temperature to be an array
            'temperature' => 'nullable|array',        // Validate as an array
            'temperature.*' => 'integer',              // Ensure each item in the array is an integer
            
            'product_type' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        
       // Safely dump the request and stop execution

    
            $product = Product::findOrFail($request->id);
           
            $product->update($validator->validated());
               
                
            return response()->json(['status'=> 200,'message' => 'Product updated successfully', 'product' => $product]);
        
    }

    /**
     * Remove a product from storage.
     */
    public function destroy($id)
    {
            
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(['status' => 200,'message' => 'Product deleted successfully']);
       
    }

    public  function supplierList(Request $request)
    {
        $productTypes = MasterList::where('list_name', 'Product_type')->get();
        $suppliers = MasterCompany::where('company_type', 'supplier')->get();
        $logistics = LogisticPackaging::all();
        $tracking = $this->getTracking();

        $ordr_id = $request->input('ordr_id', '');
        $pod = [];
        $all_temp_ctutemp1 = [];

        if ($ordr_id) {
            $products = Product::where('Product_id', $ordr_id)->get();

            foreach ($products as $product) {
                $ctu_tempar1 = [];

                if ($product->temperature_control_id === null) {
                    $all_temp_ctutemp1 = null;
                } else {
                    $ctu_tempar1 = json_decode($product->temperature_control_id, true);
                    $all_temp_ctutemp1 = array_merge($all_temp_ctutemp1, $ctu_tempar1);
                }
            }

            $pod = $products;
        }

        return response()->json([
            "all_temp_ctutemp" => $all_temp_ctutemp1,
            "pro_ty" => $productTypes,
            "supplier" => $suppliers,
            "tracking" => $tracking,
            "ordr_id" => $ordr_id,
            "purches_order" => $pod,
            "log" => $logistics,
        ]);
    }

}