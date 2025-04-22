<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MasterList;
use Illuminate\Support\Facades\Cache;

class MasterlistController extends Controller
{
    // public function list(Request $request) {
    //     // Retrieve 'list_name' from the request
    //     $list_name = $request->input('list_name');

    //     // Fetch records based on the condition or fetch all if 'list_name' is not provided
    //     if ($list_name) {
    //         $masterListData = MasterList::where('list_name', $list_name)->where('deleted',0)->get();
    //     } else {
    //         $masterListData = MasterList::all();
    //     }
    //     if (!empty($masterListData)) {
    //         if ($list_name == "List Type") {
    //             $ds = self::getListCounts($masterListData);
    //         }     

    //           return response()->json([
    //             'status'=>200,
    //             'data' =>$masterListData]
    //             );
    //     }else{
    //         return response()->json([
    //             'status'=>201,
    //             'data' =>[]
    //             ]);
    //     }







    //     // Return the data as JSON


    // }


    public function list(Request $request)
    {
        // Retrieve 'list_name' and 'item_name' from the request
        $list_name = $request->input('list_name');
        $item_name = $request->input('item_name');
    
        // Generate a unique cache key based on 'list_name' and 'item_name'
        $cacheKey = "master_list_{$list_name}_{$item_name}";
    
        // Try to retrieve the data from the cache
        $masterListData = Cache::get($cacheKey);
    
        // If data is not found in the cache, fetch it from the database and cache it
        if (!$masterListData) {
            $query = MasterList::where('deleted', 0);
    
            if ($list_name) {
                $query->where('list_name', $list_name);
            }
    
            if ($item_name) {
                $query->where('item_name', 'LIKE', "%{$item_name}%");

            }
    
            $masterListData = $query->get();
    
            // Cache the data if it's not empty
            if ($masterListData->isNotEmpty()) {
                Cache::put($cacheKey, $masterListData, now()->addMinutes(60)); // Cache for 60 minutes
            }
        }
    
        // If the 'list_name' is "List Type", calculate the counts
        if ($list_name === "List Type" && $masterListData->isNotEmpty()) {
            $ds = self::getListCounts($masterListData);
        }
    
        // Return the response with the data
        return response()->json([
            'status' => 200,
            'data' => $masterListData
        ]);
    }
    

    public function getListCounts($dsm)
    {

        $ds = MasterList::select('list_name')
            ->where('list_name', '<>', 'List Type')
            ->selectRaw('COUNT(list_name) AS count')
            ->groupBy('list_name')
            ->orderBy('list_name')
            ->get();
        foreach ($dsm as $km => $drm) {
            $count = 0;
            foreach ($ds as $k => $dr) {
                if ($dr['list_name'] == $drm['item_name']) {

                    $count = $dr['count'];
                }
            }
            $dsm[$km]['listcount'] = $count;
        }

        return $dsm;
    }
    public function getListCounts1($dsm)
    {

        $ds = MasterList::select('list_name')
            ->where('list_name', '<>', 'List Type')
            ->selectRaw('COUNT(list_name) AS count')
            ->groupBy('list_name')
            ->orderBy('list_name')
            ->get();
        foreach ($dsm as $km => $drm) {
            $count = 0;
            foreach ($ds as $k => $dr) {
                if ($dr['list_name'] == $drm['item_name']) {

                    $count = $dr['count'];
                }
            }
            $dsm[$km]['listcount'] = $count;
        }

        return $dsm;
    }
    public function list_name(Request $request)
    {

        $list_name = "List Type";
        $masterListData = MasterList::where('list_name', $list_name)->where('deleted', 0)->get();




        $ds = self::getListCounts1($masterListData);


        return response()->json([
            'status' => 200,
            'data' => $masterListData,
            'ds' => $ds
        ]);



    }
    public function addUpdateMasterList(Request $request)
    {

        $request->validate([
            'list_name' => 'required|string',
            'item_name' => 'required|string',
            'display_as' => 'required|string',
        ]);

        $data = $request->only([
            'list_name',
            'item_name',
            'display_as',
            'list_code',
            'list_description',
            'abbrv',
            'group_tag',
            'sw_fixed',
            'default_option',
            'display_order',
            'company_id',
            'disabled'
        ]);

        $listId = $request->input('list_id');

        $isUpdate = $listId > 0;

        $qtype = $isUpdate ? 'Updated' : 'Inserted';


        if ($isUpdate) {
            $masterList = MasterList::findOrFail($listId);
            if ($masterList->list_name === 'List Type' && $masterList->item_name !== $request->input('item_name')) {
                // Additional SQL update for renaming
                DB::table('master_lists')
                    ->where('list_name', $masterList->item_name)
                    ->update(['list_name' => $request->input('item_name')]);
            }
            $masterList->update($data);
        } else {

            $masterList = MasterList::create($data);
        }


        if ($request->input('default_option', 0) > 0) {
            // Reset other default options
            MasterList::where('list_name', $request->input('list_name'))
                ->where('list_id', '<>', $masterList['list_id'])
                ->update(['default_option' => 0]);
        }

        return response()->json([
            'status' => 200,
            'msg' => "Mater List Record {$qtype}!",
        ]);
    }
    public function toggle(Request $request)
    {

        $listId = $request->input('list_id', 0);
        $col = $request->input('col', '');

        // Initialize response
        $response = [
            'status' => 0,
            'toggle' => null,
        ];

        // Validate inputs
        if ($listId > 0 && !empty($col)) {


            // Find the record
            $masterList = MasterList::find($listId);


            if ($masterList && $masterList->isFillable($col)) {
                // Toggle the specified column
                $masterList->$col = !$masterList->$col;
                $masterList->save();

                // Prepare response
                $response['status'] = 200;
                $response['toggle'] = $masterList->$col;
            }
        }



        return response()->json($response);
    }
    public function getmasterlistfromids(Request $request) {
        $temp = $request->input('temp'); // Get input
    
        // Decode JSON if it's a JSON string
        if (is_string($temp) && str_starts_with($temp, '[') && str_ends_with($temp, ']')) {
            $temp = json_decode($temp, true);
        }
    
        // If decoded JSON is an array but contains a single string, split it by commas
        if (is_array($temp) && count($temp) == 1 && is_string($temp[0])) {
            $temp = explode(',', $temp[0]);
        }
    
        // Ensure temp is a valid array
        if (!is_array($temp)) {
            return response()->json(['error' => 'Invalid temp format'], 400);
        }
    
        // Fetch the records
        $rs = Masterlist::whereIn('list_id', $temp)->get();
    
        // Correct JSON response format
        return response()->json([
            "data" => $rs,
            "status" => 200
        ]);
    }

    public function get_details_from_id(Request $request)
    {
        $list_id = $request->input('listId');
        $rs = Masterlist::where('list_id', $list_id)->get();
        return response()->json([
            "data" => $rs,
            "status" => 200
        ]);



    }
    
    
    
    
    
    
}
