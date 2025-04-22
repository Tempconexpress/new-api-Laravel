<?php

namespace App\Http\Controllers\Api;;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogisticPackaging;
use App\Models\LogisticPackagingTemperatureControl;
class LogisticPackagingController extends Controller
{
    public function index(Request $request)
    {
        $query = LogisticPackaging::query();

        if ($request->has('packaging_id') && $request->packaging_id > 0) {
            $query->where('packaging_id', $request->packaging_id);
        }

        if ($request->has('packaginglistwildcard') && $request->packaginglistwildcard != '') {
            $query->where(function ($q) use ($request) {
                $q->where('packaging_name', 'like', '%' . $request->packaginglistwildcard . '%')
                    ->orWhere('search_tags', 'like', '%' . $request->packaginglistwildcard . '%')
                    ->orWhere('manufacturer', 'like', '%' . $request->packaginglistwildcard . '%');
            });
        }

        $packaging = $query->with('temperatureControls')->orderBy('packaging_name')->get();

        if ($request->has('for') && $request->for == 'form') {
            $packaging->transform(function ($item) {
                return [
                    'id' => $item->packaging_id,
                    'name' => $item->packaging_name,
                    'temperature_control' => $item->temperatureControls
                ];
            });
        }

        return response()->json([
            "status" => 200,
            "data" => $packaging,
            "message" => "Packaging Fetched"
        ]);
        
    }
    public function temperatureControls()
{
    return $this->hasMany(LogisticPackagingTemperatureControl::class, 'packaging_id', 'packaging_id');
}

    
}
