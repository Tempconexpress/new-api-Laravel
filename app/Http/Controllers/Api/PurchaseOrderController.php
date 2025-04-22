<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller; // ✅ Correct import
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\UserMaster;
use App\Models\ApprovalStatusCheck;
use App\Models\PoOrderProduct;
use App\Models\LogisticPackaging;
use App\Models\MasterCompany;
use App\Models\Product;
class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function generate_po(){
        $po_number = PurchaseOrder::generatePoNumber();
       
        return $po_number;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function save_product($order_id, $items)
{
    // ✅ Assign row numbers dynamically
    $itemsWithRowNumbers = array_map(function ($item, $index) {
        $item['row_no'] = $index + 1;
        return $item;
    }, $items, array_keys($items));

    $responses = [];
   
    foreach ($itemsWithRowNumbers as $item) {
       
        // ✅ Validate request data
        $validator = Validator::make($item, [
            'row_no' => 'required|integer',
            'packaging_id' => 'nullable|integer',
            'Product_Name' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'rqnt' => 'nullable|integer|min:0',
            'unit_id' => 'nullable|integer',
            'rate' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'CGST' => 'nullable|numeric|min:0',
            'SGST' => 'nullable|numeric|min:0',
            'IGST' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'hsn_code' => 'nullable|string|max:50',
            'row_reference' => 'nullable|string|max:255',
            'row_total' => 'nullable|numeric|min:0',
            'product_other' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $responses[] = [
                'status' => 0,
                'msg' => 'Validation failed',
                'errors' => $validator->errors(),
                'row_no' => $item['row_no']
            ];
            continue; // Skip invalid entries
        }

        $validatedData = $validator->validated();
          
        // ✅ Check if the product already exists
        $product = PoOrderProduct::where('order_id', $order_id)
                    ->where('row_no', $validatedData['row_no'])
                    ->first();
       
        if ($product) {
            // 🛠️ Update existing product
            $product->update([
                'item_id' => $validatedData['packaging_id'],
                'item_name' => $validatedData['Product_Name'],
                'quantity' => $validatedData['qty'],
                'received_quantity' => $validatedData['rqnt'] ?? 0,
                'unit_id' => $validatedData['unit_id'],
                'rate' => $validatedData['rate'],
                'amount' => $validatedData['amount'],
                'CGST' => $validatedData['CGST'],
                'SGST' => $validatedData['SGST'],
                'IGST' => $validatedData['IGST'],
                'tax_amount' => $validatedData['tax_amount'],
                'hsn_code' => $validatedData['hsn_code'],
                'reference' => $validatedData['row_reference'],
                'row_total' => $validatedData['row_total'],
                'product_type' => $validatedData['product_other'],
            ]);

            return response()->json([
                'status' => 200,
                'msg' => 'Product updated successfully',
                'product' => $product,
                'row_no' => $validatedData['row_no']
            ]);
        } else {
            // 🛠️ Insert new product
            $newProduct = PoOrderProduct::create([
                'order_id' => $order_id,
                'row_no' => $validatedData['row_no'],
                'item_id' => $validatedData['packaging_id'],
                'item_name' => $validatedData['Product_Name'],
                'quentity' => $validatedData['qty'],
                'received_quantity' => 0,
                'unit_id' => $validatedData['unit_id'],
                'rate' => $validatedData['rate'],
                'amount' => $validatedData['tax_amount'],
                'CGST' => !empty($validatedData['CGST'])?$validatedData['CGST']:0.00,
                'SGST' => !empty($validatedData['SGST'])?$validatedData['SGST']:0.00,
                'IGST' => !empty($validatedData['IGST'])?$validatedData['IGST']:0.00,
                'tax_amount' => $validatedData['tax_amount'],
                'hsn_code' => $validatedData['hsn_code'],
                'reference' => !empty($validatedData['reference'])?$validatedData['reference']:'',
                'row_total' => $validatedData['row_total'],
                'product_type' => 'Packaging',
            ]);
             
              return response()->json([
                'status' => 200,
                'msg' => 'Product inserted successfully',
                'product' => $newProduct,
                'row_no' => $validatedData['row_no']
            ]);
        }
    }

    return response()->json([
        'status' => 200,
        'message' => 'Processing completed',
        'responses' => $responses
    ]);
}


    
public function saveOrder(Request $request)
{
    $order_id = $request->input('order_id');
    
    if (empty($order_id)) {
        $user = UserMaster::where('access_key', $request->input('access_key'))
            ->where('user_name', $request->input('user_name'))
            ->first();
        
        if (!$user) {
            return response()->json(['status' => "notSave", 'msg' => "User not found!"]);
        }
        
        $PO_approvers = json_decode($user->PO_approvers, true);
        
        if (empty($PO_approvers)) {
            return response()->json(['status' => "notSave", 'msg' => "You cannot save the order!"]);
        }
        
        $order = PurchaseOrder::create($request->only([
            'order_urn', 'supplier_id', 'invoice_to', 'delivery_to', 'delivery_address',
            'delivery_state', 'delivery_statecode', 'delivery_pin', 'order_date',
            'supplier_reference', 'other_reference', 'payment_mode', 'payment_terms',
            'delivery_date', 'dispatch_through', 'non_taxable', 'taxable', 'IGST', 'CGST', 'SGST',
            'round_off', 'total_b', 'remarks', 'conditions'
        ]) + ['is_received' => 0, 'Approval_status' => 0]);
        
        $taxable_amount = $order->taxable;
        
        $approval_levels = [50000, 50001, 100000];
        foreach ($PO_approvers as $approver) {
            $approval_levels[$approver['poapprover_level'] - 1] = $approver['POamount'];
        }
        
        $lvl = ($taxable_amount <= $approval_levels[0]) ? 1 : (($taxable_amount <= $approval_levels[1]) ? 2 : 3);
        
        $user_ids = [0, 0, 0];
        foreach ($PO_approvers as $approver) {
            if ($approver['poapprover_level'] <= $lvl) {
                $user_ids[$approver['poapprover_level'] - 1] = $approver['poapprover'];
            }
        }
        
        ApprovalStatusCheck::create([
            'order-id' => $order->order_id,
            'Level_check' => $lvl,
            'user_id1' => $user_ids[0], 'status1' => 'pending',
            'user_id2' => $user_ids[1], 'status2' => 'pending',
            'user_id3' => $user_ids[2], 'status3' => 'pending',
            'check_final_status' => 0
        ]);
         self::save_product($order->order_id,$request->input('items'));
          
        return response()->json(['status' => 200, 'order_id' => $order->order_id, 'msg' => "New Order Saved"]);
    
    }
    
    $order = PurchaseOrder::find($order_id);
    
    if (!$order) {
        return response()->json(['status' => 201, 'msg' => "Order not found!"]);
    }
    
    $order->update($request->only([
        'supplier_id', 'invoice_to', 'delivery_to', 'delivery_address', 'delivery_state',
        'delivery_statecode', 'order_date', 'supplier_reference', 'other_reference', 'payment_mode',
        'payment_terms', 'delivery_date', 'dispatch_through', 'non_taxable', 'taxable', 'IGST',
        'CGST', 'SGST', 'round_off', 'total_b', 'remarks', 'conditions'
    ]));
    
    return response()->json(['status' => 200, 'order_id' => $order_id, 'msg' => "Order Updated"]);
}

public function get_branch_and_s(Request $request)
{
    $branches = MasterCompany::where('company_type', 'Branch')->get();
    $suppliers = MasterCompany::where('company_type', 'supplier')->get();
    $logistics = LogisticPackaging::all();
    $products = Product::all();
    $poCount = $this->poNumber();
    
    $orderId = $request->input('ordr_id', '');
    
    $purchaseOrder = $orderId ? PurchaseOrder::where('order_id', $orderId)->get() : [];
    $poOrderProducts = $orderId ? PoOrderProduct::where('order_id', $orderId)->get() : [];

    return response()->json([
        'branch' => $branches,
        'supplier' => $suppliers,
        'po_count' => $poCount,
        'ordr_id' => $orderId,
        'purches_order' => $purchaseOrder,
        'po_order_products' => $poOrderProducts,
        'log' => $logistics,
        'prdct' => $products,
    ]);
}

public function fetch_orders(Request $request) 
{
    // ✅ Get pagination params (default page = 1, per_page = 5)
    $page = $request->input('page', 1);
    $perPage = $request->input('per_page', 5);
    $offset = ($page - 1) * $perPage;

    // ✅ Get filters from request
    $poId = $request->input('poId');
    $supplier = $request->input('supplier');
    $invoiceTo = $request->input('invoiceTo');
    $search = $request->input('search');

    // ✅ Build Query
    $query = PurchaseOrder::select(
        'ms.display_name as supplier',
        'ms.company_name as invoiceto',
        'ns.display_name as invoicee',
        'ss.display_name as delivery',
        'purchase_order.*'
    )
    ->join('master_company as ms', 'ms.company_id', '=', 'purchase_order.supplier_id')
    ->join('master_company as ss', 'purchase_order.delivery_to', '=', 'ss.company_id')
    ->join('master_company as ns', 'purchase_order.invoice_to', '=', 'ns.company_id')
    ->where('ns.company_type', 'Branch');

    // ✅ Apply filters if provided
    if (!empty($poId)) {
        $query->where('purchase_order.order_urn', 'LIKE', "%$poId%");
    }
    if (!empty($supplier)) {
        $query->where('ms.display_name', 'LIKE', "%$supplier%");
    }
    if (!empty($invoiceTo)) {
        $query->where('ns.display_name', 'LIKE', "%$invoiceTo%");
    }
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('purchase_order.order_urn', 'LIKE', "%$search%")
              ->orWhere('ms.display_name', 'LIKE', "%$search%")
              ->orWhere('ns.display_name', 'LIKE', "%$search%");
        });
    }

    // ✅ Get total count before pagination
    $total = $query->count();

    // ✅ Apply pagination
    $orders = $query->offset($offset)->limit($perPage)->get();

    return response()->json([
        "status" => 200,
        "orders" => $orders,
        "pagination" => [
            "total" => $total,
            "page" => $page,
            "per_page" => $perPage,
        ],
    ]);
}

private function poNumber()
{
    // Implement your logic for generating or retrieving the purchase order number
    return PurchaseOrder::count();
}

  


    /**
     * Store a newly created resource in storage.
     */
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
}
