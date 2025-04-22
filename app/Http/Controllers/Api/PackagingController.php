<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LogisticPackaging;
use App\Models\LogisticPackagingTemperatureControl;
use App\Models\LogisticPackagingGelpack;
use App\Models\MasterList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PackagingController extends Controller
{
    public function list(Request $request)
    {
        $wildcard = $request->input('wildcard');
        $cacheKey = 'packaging_list_' . md5($wildcard);

        $data = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($wildcard) {
            $query = LogisticPackaging::with('temperatureControls')
                ->where('is_active', 1)
                ->whereNull('deleted_at');

            if ($wildcard) {
                $query->where(function ($q) use ($wildcard) {
                    $q->where('packaging_name', 'LIKE', "%{$wildcard}%")
                      ->orWhere('search_tags', 'LIKE', "%{$wildcard}%")
                      ->orWhere('manufacturer', 'LIKE', "%{$wildcard}%");
                });
            }

            return $query->orderBy('packaging_id')->get()->map(function ($item) {
                return [
                    'packaging_id' => $item->packaging_id,
                    'name' => $item->packaging_name,
                    'usage' => $item->usage,
                    'temperature' => $item->temperatureControls->pluck('temperature_control_id')->map(function ($id) {
                        return MasterList::where('list_id', $id)->value('item_name');
                    })->implode(', '),
                    'actualWeight' => $item->actual_weight . ' kg',
                    'volumetricWeight' => $item->volumetric_weight . ' kg',
                    'validationTime' => $item->validation_time . ' hours',
                    'manufacturer' => $item->manufacturer, // Added for frontend
                ];
            });
        });

        return response()->json([
            'status' => 200,
            'data' => $data,
        ]);
    }

    public function show(Request $request, $id)
{
    $packaging = LogisticPackaging::with(['temperatureControls', 'gelpacks'])->findOrFail($id);

    $temperatureData = $packaging->temperatureControls->where('disabled', 0)->mapWithKeys(function ($tc) {
        $tempName = MasterList::where('list_id', $tc->temperature_control_id)->value('item_name');
        return [$tempName => $tc->qty_required_in_booking ? 'required' : 'outRequired'];
    });

    $formattedData = [
        'packaging_id' => $packaging->packaging_id,
        'packagingName' => $packaging->packaging_name,
        'packagingCode' => $packaging->search_tags,
        'manufacturerName' => $packaging->manufacturer,
        'rate' => $packaging->Rate_inpt,
        'usage' => $packaging->usage,
        'requireBillingCheck' => $packaging->require_billing_check ? 'yes' : 'no',
        'stockTracking' => $packaging->stock_tracking ? 'yes' : 'no',
        'requiredGelpack' => $packaging->Required_Gelpack > 0 ? 'yes' : 'no',
        'requiredGelpackqty' => $packaging->Required_Gelpack,
        'capacity' => $packaging->Capacity,
        'requiredQRCode' => $packaging->qr_code,
        'editableInBooking' => $packaging->editable_in_booking ? 'yes' : 'no',
        'volumetricDivisor' => $packaging->volumetric_divisor,
        'dimensions' => [
            'external' => [
                'length' => $packaging->external_dimension_length,
                'width' => $packaging->external_dimension_width,
                'height' => $packaging->external_dimension_height,
            ],
            'internal' => [
                'length' => $packaging->internal_dimension_length,
                'width' => $packaging->internal_dimension_width,
                'height' => $packaging->internal_dimension_height,
            ],
        ],
        'actualWeight' => $packaging->actual_weight,
        'volumetricWeight' => $packaging->volumetric_weight,
        'validationTime' => $packaging->validation_time,
        'defaultPackagingType' => $packaging->default_packaging_supplier === 'a' ? 'active' : 'passive',
        'shipperPackOut' => $packaging->Shipper_pack_out,
        'temperature' => $temperatureData->keys()->all(),
        'quantityInBooking' => $temperatureData->all(),
        'gelpacks' => $packaging->gelpacks->map(function ($gelpack) {
            return [
                'ticGelpackName' => $gelpack->tic_gelpack_name,
                'ticGelpackCode' => $gelpack->tic_gelpack_code,
                'requiredGelpackqty' => $gelpack->required_gelpack_qty,
            ];
        })->all(),
    ];

    return response()->json([
        'status' => 200,
        'data' => $formattedData,
    ]);
}

    public function addUpdate(Request $request)
    {
        $request->validate([
            'packagingName' => 'required|string|max:255',
            'manufacturerName' => 'required|string|max:255',
            'usage' => 'required|string|in:Reusable,Single Use',
            'rate' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
            'requiredGelpackqty' => 'nullable|integer|min:0',
            'actualWeight' => 'nullable|numeric',
            'volumetricWeight' => 'nullable|numeric',
            'validationTime' => 'nullable|integer',
            'dimensions.external.length' => 'nullable|numeric',
            'dimensions.external.width' => 'nullable|numeric',
            'dimensions.external.height' => 'nullable|numeric',
            'dimensions.internal.length' => 'nullable|numeric',
            'dimensions.internal.width' => 'nullable|numeric',
            'dimensions.internal.height' => 'nullable|numeric',
            'gelpacks' => 'nullable|array',
            'gelpacks.*.ticGelpackName' => 'nullable|string',
            'gelpacks.*.ticGelpackCode' => 'nullable|string',
            'gelpacks.*.requiredGelpackqty' => 'nullable|integer|min:0',
        ]);

        $packagingId = $request->input('packaging_id');
        $isUpdate = $packagingId !== null;

        DB::beginTransaction();
        try {
            $data = [
                'packaging_name' => $request->packagingName,
                'search_tags' => $request->packagingCode,
                'manufacturer' => $request->manufacturerName,
                'Rate_inpt' => $request->rate,
                'usage' => $request->usage,
                'require_billing_check' => $request->requireBillingCheck === 'yes' ? 1 : 0,
                'stock_tracking' => $request->stockTracking === 'yes' ? 1 : 0,
                'Required_Gelpack' => $request->requiredGelpack === 'yes' ? ($request->requiredGelpackqty ?? 0) : 0,
                'Capacity' => $request->capacity,
                'qr_code' => $request->requiredQRCode,
                'editable_in_booking' => $request->editableInBooking === 'yes' ? 1 : 0,
                'volumetric_divisor' => $request->volumetricDivisor,
                'external_dimension_length' => $request->dimensions['external']['length'],
                'external_dimension_width' => $request->dimensions['external']['width'],
                'external_dimension_height' => $request->dimensions['external']['height'],
                'internal_dimension_length' => $request->dimensions['internal']['length'],
                'internal_dimension_width' => $request->dimensions['internal']['width'],
                'internal_dimension_height' => $request->dimensions['internal']['height'],
                'actual_weight' => $request->actualWeight,
                'volumetric_weight' => $request->volumetricWeight,
                'validation_time' => $request->validationTime,
                'default_packaging_supplier' => $request->defaultPackagingType === 'active' ? 'a' : 'p',
                'Shipper_pack_out' => $request->shipperPackOut,
                'doe' => now(),
                'remote_ip' => $request->ip(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'is_active' => 1,
            ];

            if ($isUpdate) {
                $packaging = LogisticPackaging::findOrFail($packagingId);
                $packaging->update($data);
            } else {
                $packagingId = $this->generatePackagingId();
                $data['packaging_id'] = $packagingId;
                $packaging = LogisticPackaging::create($data);
            }

            // Handle Temperature Controls
            if ($request->has('temperature')) {
                $this->updateTemperatureControls($packagingId, $request->temperature, $request->quantityInBooking);
            }

            // Handle Gelpacks
            if ($request->has('gelpacks') && $request->requiredGelpack === 'yes') {
                LogisticPackagingGelpack::where('packaging_id', $packagingId)->delete();
                foreach ($request->gelpacks as $gelpack) {
                    LogisticPackagingGelpack::create([
                        'packaging_id' => $packagingId,
                        'tic_gelpack_name' => $gelpack['ticGelpackName'],
                        'tic_gelpack_code' => $gelpack['ticGelpackCode'],
                        'required_gelpack_qty' => $gelpack['requiredGelpackqty'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'msg' => "Packaging " . ($isUpdate ? 'Updated' : 'Inserted') . "!",
                'packaging_id' => $packagingId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'msg' => "Error: " . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : [],
            ], 500);
        }
    }

    private function generatePackagingId()
    {
        $lastId = LogisticPackaging::max('packaging_id') ?? 0;
        return $lastId + 1;
    }

    private function updateTemperatureControls($packagingId, $temperatures, $quantities)
    {
        LogisticPackagingTemperatureControl::where('packaging_id', $packagingId)->update(['disabled' => 1]);

        foreach ($temperatures as $tempName) {
            $tempId = MasterList::where('list_name', 'Temperature Control')
                ->where('item_name', $tempName)
                ->value('list_id');

            if ($tempId) {
                $qty = isset($quantities[$tempName]) && $quantities[$tempName] === 'required' ? 1 : 0;

                LogisticPackagingTemperatureControl::updateOrCreate(
                    [
                        'packaging_id' => $packagingId,
                        'temperature_control_id' => $tempId,
                    ],
                    [
                        'qty_required_in_booking' => $qty,
                        'disabled' => 0,
                    ]
                );
            }
        }
    }

    public function delete(Request $request, $id)
    {
        $packaging = LogisticPackaging::findOrFail($id);
        $packaging->update([
            'deleted_by' => Auth::id(),
            'is_active' => 0,
        ]);
        $packaging->delete();

        return response()->json([
            'status' => 200,
            'msg' => 'Packaging deleted successfully!',
        ]);
    }

    public function getTemperatureOptions()
    {
        $options = MasterList::where('list_name', 'Temperature Control')
            ->where('is_active', 1)
            ->pluck('item_name');
        return response()->json(['status' => 200, 'data' => $options]);
    }
}