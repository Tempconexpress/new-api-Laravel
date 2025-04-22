<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectManagement;
use App\Models\ProjectManagementList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectManagementController extends Controller
{
    public function index(Request $request)
    {
        $deleted = $request->input('deleted', NULL);
        $query = ProjectManagement::with('lists')
            ->join('master_company', 'logistic_project_mgmt_1_main.client_id', '=', 'master_company.company_id')
            ->select('logistic_project_mgmt_1_main.*', 'master_company.company_urn as client_urn');
    
        if ($deleted === '0' || $deleted === '1') {
            $query->where('logistic_project_mgmt_1_main.deleted', $deleted=='0'?NULL:1);
        }
    
        $projects = $query->get();
    
        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $mandatory = ['study_no', 'client_urn']; // Validate client_urn
        $errors = [];

        foreach ($mandatory as $field) {
            if (empty($request->$field)) {
                $errors[] = $field;
            }
        }

        if (empty($request->contents_classification)) {
            $errors[] = 'contents_classification';
        } else {
            if (in_array('Animal Origin', $request->contents_classification) && empty(trim($request->contents_classification_anor))) {
                $errors[] = 'contents_classification_anor';
            }
        }

        if (!empty($errors)) {
            return response()->json(['status' => 0, 'mandatory' => $errors, 'msg' => 'Mandatory Input missing!'], 422);
        }

        // Convert client_urn to client_id
        $clientId = $this->getIdFromUrn($request->client_urn);
        if (!$clientId) {
            return response()->json(['status' => 0, 'msg' => 'Invalid client_urn provided!'], 422);
        }

        $data = $request->all();
        if (!empty($data['study_start_date'])) {
            $data['study_start_date'] = date('Y-m-d', strtotime($data['study_start_date']));
        }
        if (!empty($data['study_end_date'])) {
            $data['study_end_date'] = date('Y-m-d', strtotime($data['study_end_date']));
        }

        $data['client_id'] = $clientId; // Use the resolved client_id
        $data['doe_userid'] = Auth::id();
        $data['co_id'] = Auth::user()->co_id ?? null;
        $data['doe'] = now();
        $data['remote_ip'] = $request->ip();

        unset($data['client_urn']); // Remove client_urn since we store client_id

        \Log::info('Processed data for storage:', $data); // Log for debugging

        if ($request->lpm_id) {
            $project = ProjectManagement::find($request->lpm_id);
            if (!$project) {
                return response()->json(['status' => 0, 'msg' => 'Project not found!'], 404);
            }
            $project->update($data);
            $qtype = 'Updated';
        } else {
            $project = ProjectManagement::create($data);
            $qtype = 'Inserted';
        }

        $this->saveSubLists($project->lpm_id, $request);

        return response()->json([
            'status' => 1,
            'msg' => "Record $qtype!",
            'id' => $project->lpm_id,
            'sub' => $this->saveSubLists($project->lpm_id, $request)
        ]);
    }

    private function saveSubLists($lpm_id, Request $request)
    {
        ProjectManagementList::where('lpm_id', $lpm_id)->delete();

        $insertData = [];
        $multiFields = ['contents_classification', 'temperature_requirements', 'supply_requirements', 'document_support'];
        $typeCodes = [
            'contents_classification' => 'CtCn',
            'temperature_requirements' => 'TmRq',
            'supply_requirements' => 'SpRq',
            'document_support' => 'DoSu'
        ];

        foreach ($multiFields as $field) {
            if ($request->has($field)) {
                foreach ($request->$field as $value) {
                    $list_details = ($field === 'contents_classification' && $value === 'Animal Origin') ? $request->contents_classification_anor : '';
                    $insertData[] = [
                        'lpm_id' => $lpm_id,
                        'list_code' => $value,
                        'list_type_code' => $typeCodes[$field],
                        'list_details' => $list_details
                    ];
                }
            }
        }

        if (!empty($insertData)) {
            ProjectManagementList::insert($insertData);
            return ['status' => 1, 'msg' => 'Project Info-Lists Saved!'];
        }

        return ['status' => 0];
    }

    public function destroy(Request $request)
    {
        $dbid = (int)$request->dbid;
        if ($dbid > 0) {
            $project = ProjectManagement::find($dbid);
            if ($project) {
                $project->deleted = abs($project->deleted - 1);
                $project->save();
                return response()->json([
                    'status' => 1,
                    'deleted' => $project->deleted
                ]);
            }
        }
        return response()->json(['status' => 0], 404);
    }

    private function getIdFromUrn($urn)
    {
        if (!$urn) {
            return false;
        }

        // Query the master_company table to get company_id from company_urn
        $company = DB::table('master_company')
            ->where('company_urn', $urn)
            ->first();

        return $company ? $company->company_id : false;
    }
}