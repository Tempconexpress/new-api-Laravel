<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LinkBankDetail;
use App\Models\MasterCompany;
use App\Models\MasterCompanyContact;
use App\Models\MasterCompanyAddress;
use App\Models\MasterList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CustomHelpers;
use App\Services\DuplicateService;
use Illuminate\Support\Facades\DB;
use App\Services\URNGeneratorService;
use Illuminate\Support\Facades\Session;


class MasterCompanyController extends Controller
{

    protected $duplicateService;
    protected $urnService;

    public function __construct(DuplicateService $duplicateService,URNGeneratorService $urnService)
    {
        $this->duplicateService = $duplicateService;
        $this->urnService = $urnService;
    }
    public function someMethod()
    {
        $urn = $this->urnService->generate_URN_specific(['WL' => 4, 'WR' => 4, 'for' => 'company', 'TXT' => 'CMP']);
        // Do something with $urn
    }
   
//     public function index(Request $request)
//     {
//         // Fetch input companies from the database
//         $companies = MasterCompany::input();
// // $companies = MasterCompany::where('company_type', 'Vendor')->get();


//         // Format the data

//         $formattedData = $companies->map(function ($company) {
//             return [
//                 'id' => $company->company_urn,
//                 'company_name' => $company->company_name,
//                 'display_name' => $company->display_name,
//                 'sales_representative' => $company->sales_rep_id,
//                 'company_type' => $company->company_type,
//                 'entity_type' => $company->entity_type,
//                 'gst_number' => $company->gst_no,
//                 'pan_number' => $company->pan,
//                 'currency' => $company->currency,
//                 'credit_period' => $company->paymentTerm,
//                 'doe' => $company->doe ? Carbon::parse($company->doe)->format('d-M-Y H:i:s') : null,
//                 'gst_treatment' => $company->gst_treatment,
//                 'status' => $company->suspend ? '0' : '1',
//                 'contact_person' => $company->contact_id ? $company->contact_name : 'N/A',
//                 'phone' => $company->contact_phone ?? 'N/A',
//                 'email' => $company->contact_email ?? 'N/A',
//                 'address' => $company->address ?? 'N/A',
//                 'city' => $company->city ?? 'N/A',
//                 'state' => $company->state ?? 'N/A',
//                 'country' => $company->country ?? 'N/A',
//                 'status_display' => $company->suspend ? 'Inactive' : 'Active',
//                 'actions' => [
//                     // 'edit' => route('companies.edit', ['id' => $company->company_urn]),
//                     // 'delete' => route('companies.delete', ['id' => $company->company_urn]),
//                 ],
//             ];
//         });

//         // Pagination parameters
//         $page = $request->input('page', 1);
//         $perPage = $request->input('per_page', 10);

//         // Get items for the current page
//         $currentPageItems = $formattedData->slice(($page - 1) * $perPage, $perPage)->input();

//         // Create a paginator instance
//         $paginatedData = new LengthAwarePaginator(
//             $currentPageItems,
//             $formattedData->count(),
//             $perPage,
//             $page,
//             ['path' => $request->url(), 'query' => $request->query()]
//         );

//         // Prepare the response structure
//         return response()->json([
//             'status' => 200,
//             'message' => 'Company Master Data Fetched Successfully.',
//             'code' => 'company_master',
//             'data' => [
//                 'companies' => $paginatedData->items(),
//                 'pagination' => [
//                     'total' => $paginatedData->total(),
//                     'current_page' => $paginatedData->currentPage(),
//                     'last_page' => $paginatedData->lastPage(),
//                     'per_page' => $paginatedData->perPage(),
//                     'next_page_url' => $paginatedData->nextPageUrl(),
//                     'prev_page_url' => $paginatedData->previousPageUrl(),
//                 ],
//             ],
//         ]);
//     }



public function index(Request $request)
{
    // Fetch companies with 'Vendor' type from the database
    $companyType = $request->input('company_type');
    $companies = MasterCompany::where('company_type', $companyType)->orderBy('company_id','desc')->get();

    // Format the data
    $formattedData = $companies->map(function ($company) {
        $gstTreatment = MasterList::where('list_id', $company->gst_treatment)->get('display_as')->first();
        $gstTreatment = $gstTreatment ? $gstTreatment->display_as : 'N/A';

        $entityType = MasterList::where('list_id', $company->entity_type)->get('display_as')->first();
        $entityType = $entityType ? $entityType->display_as : 'N/A';
        
        $currency = MasterList::where('list_id', $company->currency)->get('list_code')->first();
        $currency = $currency ? $currency->list_code : 'N/A';
// print_r($company);die;
        return [
            'company_id' => $company->company_id,
            'company_urn' => $company->company_urn,
            'display_name' => $company->display_name,
            'entity_type' => $entityType,
            'gst_treatment' => $gstTreatment,
            'currency' => $currency,
            'status' => $company->is_active,
            'city' => $company->city ?? 'N/A',
            'state' => $company->state ?? 'N/A',
            'country' => $company->country ?? 'N/A',
            'status_display' => $company->suspend ? 'Inactive' : 'Active',
        ];
    });

    // Pagination parameters
    $page = $request->input('page', 1);
    $perPage = $request->input('per_page', 10);

    $currentPageItems = $formattedData->forPage($page, $perPage)->values();

    // Create a paginator instance
    $paginatedData = new LengthAwarePaginator(
        $currentPageItems,
        $formattedData->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // Prepare the response structure
    return response()->json([
        'status' => 200,
        'message' => 'Company Master Data Fetched Successfully.',
        'code' => 'company_master',
        'data' => [
            'companies' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(), // Corrected: Call `total()` on paginator
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'next_page_url' => $paginatedData->nextPageUrl(),
                'prev_page_url' => $paginatedData->previousPageUrl(),
            ],
        ],
    ]);
}

public function view(Request $request)
{
    $companyUrn = $request->input('companyUrn');
    $company = MasterCompany::where('company_urn', $companyUrn)->first();
    // print_r($company);die;
    if($company){
    $companyId = $company->company_id;
    $gstTreatment = MasterList::where('list_id', $company->gst_treatment)->get('display_as')->first();
        $gstTreatment = $gstTreatment ? $gstTreatment->display_as : 'N/A';

        $entityType = MasterList::where('list_id', $company->entity_type)->get('display_as')->first();
        $entityType = $entityType ? $entityType->display_as : 'N/A';

        $industryType = MasterList::where('list_id', $company->industry_type_id)->get('display_as')->first();
        $industryType = $industryType ? $industryType->display_as : 'N/A';

        $tds = MasterList::where('list_id', $company->tds_rate)->get('display_as')->first();
        $tds = $tds ? $tds->display_as : 'N/A';

        $vendorOfBranch = MasterCompany::where('company_id', $company->billing_company_id)->get('display_name')->first();
        $vendorOfBranch = $vendorOfBranch ? $vendorOfBranch->display_name : 'N/A';
        
        $transactionCategory = MasterList::where('list_id', $company->trans_category)->get('display_as')->first();
        $transactionCategory = $transactionCategory ? $transactionCategory->display_as : 'N/A';

        $currency = MasterList::where('list_id', $company->currency)->get('list_code')->first();
        $currency = $currency ? $currency->list_code : 'N/A';

        
        $portal_status = empty($company->is_active) ? 'Inactive' : 'Active'; 
        $status_color = ($portal_status === 'Active') ? 'green' : 'red';
        // print_r($companyId);die;
        // Fetch the company details using the model and its relationships
        $company = MasterCompany::with([
            'contacts',    // Related contact details
            'addresses',   // Related address details
            'bankDetails'  // Related bank details
            ])->find($companyId);

            
    if (!$company) {
        return response()->json([
            'status' => 404,
            'message' => 'Company not found.',
            'code' => 'company_not_found',
            'data' => null
        ]);
    }

    // Format the response data
    $formattedCompanyData = [
        'Basic Details' => [
            'Legal Name' => $company->company_name,
            'List Display Name' => $company->display_name,
            'Group Name' => $company->group_name,
            'Entity Type' => $entityType,
            'Industry Type' => $industryType,
            'Transaction Category' => $transactionCategory,
            'Vendor of Branch' => $vendorOfBranch,
            'Portal Status' => $portal_status,
            'GST Treatment' => $gstTreatment,
            'CIN' => $company->cin,
            'PAN' => $company->pan,
            'GST' => $company->gst_no,
            'MSME Registered' => $company->msme_registered ?? null,
            'MSME/Udyam Registration Type' => $company->msme_udyam_type ?? null,
            'MSME/Udyam Registration No' => $company->msme_udyam_no ?? null,
            'Payment Term' => $company->credit_period,
            'Currency' => $currency,
            'Account Opened' => $company->account_opened,
            'TDS' => $tds,
            'status_color' => $status_color     
           ],
        'Address Details' => $company->addresses->map(function ($address) {
            return [
                'Premises' => $address->co_hno,
                'Locality 1' => $address->co_locality,
                'Locality 2' => $address->co_locality2,
                'IATA' => $address->co_iata,
                // 'Code' => $address->code,
                'Pincode' => $address->co_pincode,
                'City' => $address->co_city,
                'State' => $address->co_state,
                'Country' => $address->co_country,
                'Reference' => $address->reference,
            ];
        }),
        'Contact Details' => $company->contacts->map(function ($contact) {
            return [
                'Contact Name' => $contact->contact_name,
                'Type/Dept' => $contact->contact_type,
                'Email' => $contact->contact_email,
                'Mobile' => $contact->contact_phone,
                'Landline' => $contact->landline,
            ];
        }),
        'Bank Details' => $company->bankDetails->map(function ($bankDetail) {
            $bank_ac_location = '';
            
                if($bankDetail->bank_ac_location === "IN"){
                    $bank_ac_location = 'India';
                }elseif($bankDetail->bank_ac_location == 'OS'){
                    $bank_ac_location = 'Overseas';
                }
                $bank_ac_type = '';
            
                if($bankDetail->bank_ac_type === "CA"){
                    $bank_ac_type = 'Current A/c';
                }elseif($bankDetail->bank_ac_type == 'SB'){
                    $bank_ac_type = 'Savings A/c';
                }elseif($bankDetail->bank_ac_type == 'OD'){
                    $bank_ac_type = 'Overdraft';
                }
            // print_r($bank_ac_location);die;
            return [
                'Account Number' => $bankDetail->bank_ac_no,
                'Payee Name' => $bankDetail->account_name,
                'Bank Name' => $bankDetail->bank_name,
                'Swift Code' => $bankDetail->swiftcode,
                'IFSC' => $bankDetail->ifsc,
                'Account Location' => $bank_ac_location,
                'Account Type' => $bank_ac_type,
                'Bank Address' => $bankDetail->bank_address,
                'Default' => $bankDetail->default_ac,
            ];
        }),
    ];

    return response()->json([
        'status' => 200,
        'message' => 'View Company Fetched Successfully.',
        'code' => 'company_master',
        'data' => $formattedCompanyData
    ]);
}else{
    return response()->json([
        'status' => 200,
        'message' => 'Error to View Company Details.',
        'code' => 'company_master',
    ]);
}
}


public function fetch_company_details(Request $request)
{
    $companyUrn = $request->input('branches');
    $rs = MasterCompany::select('*')->where('company_type','Branch')->whereIn('company_urn',$companyUrn)->get();
    return response()->json([
        'status' => 200,
        'message' => 'Company details fetched',
        'data' => $rs,
    ]);
}


public function filter(Request $request)
{
    // Retrieve input data from the request
    $companyType = $request->input('company_type');
    $placeOfSupply = $request->input('place_of_supply');
    $entityType = $request->input('entity_type');
    $exportToZoho = $request->input('export_to_zoho');

    // Base query
    $query = MasterCompany::select([
        'master_company.company_urn',
        'master_company.display_name',
        'master_company.entity_type',
        'master_company.gst_treatment',
        'master_company.currency',
        'master_company.is_active',
        'mca.co_city',
        'mca.co_state',
        'mca.co_country',
        'etl.display_as as entity_type_display',
        'gtl.display_as as gst_treatment_display',
    ])
    ->join('master_lists as etl', 'master_company.entity_type', '=', 'etl.list_id')  // Join for entity type
    ->join('master_lists as gtl', 'master_company.gst_treatment', '=', 'gtl.list_id') // Join for GST treatment
    ->join('master_company_address as mca', 'master_company.company_id', '=', 'mca.company_id') // Join for address details
    ->where('master_company.company_type', $companyType); // Filter by company type

    // Apply entityType filter if provided
    if ($entityType) {
        $query->where('master_company.entity_type', $entityType);
    }

    // Apply placeOfSupply filter if provided (uncommented the logic for use)
    if ($placeOfSupply) {
        $query->where('master_company.state', function ($subQuery) use ($placeOfSupply) {
            $subQuery->select('state_id')
                ->from('geo_locations')
                ->where('geo_location_id', $placeOfSupply)
                ->limit(1);
        });
    }

    // Apply exportToZoho filter if provided
    if ($exportToZoho) {
        $query->where(function ($q) use ($exportToZoho) {
            if ($exportToZoho == 1) {
                $q->whereNotNull('master_company.contact_id');
            } elseif ($exportToZoho == 0) {
                $q->whereNull('master_company.contact_id');
            }
        });
    }

    // Fetch data with eager loading for related information
    $companies = $query
        ->orderBy('master_company.company_id', 'desc')
        ->get();

    // Format the data
    $formattedData = $companies->map(function ($company) {
        $gstTreatment = MasterList::where('list_id', $company->gst_treatment)->first();
        $gstTreatment = $gstTreatment ? $gstTreatment->display_as : 'N/A';

        $entityType = MasterList::where('list_id', $company->entity_type)->first();
        $entityType = $entityType ? $entityType->display_as : 'N/A';
        
        $currency = MasterList::where('list_id', $company->currency)->first();
        $currency = $currency ? $currency->list_code : 'N/A';
        return [
            'company_urn'=>$company->company_urn,
            'display_name' => $company->display_name,
            'entity_type' => $entityType,
            'gst_treatment' => $gstTreatment,
            'currency' => $currency,
            'status' => $company->is_active,
            'city' => $company->co_city ?? 'N/A',
            'state' => $company->co_state ?? 'N/A',
            'country' => $company->co_country ?? 'N/A',
            'status_display' => $company->is_active ? 'Active' : 'Inactive',
        ];
    });
// print_r($formattedData);die;
    // Pagination parameters
    $page = $request->input('page', 1);
    $perPage = $request->input('per_page', 10);

    $currentPageItems = $formattedData->forPage($page, $perPage)->values();

    // Create a paginator instance
    $paginatedData = new LengthAwarePaginator(
        $currentPageItems,
        $formattedData->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // Prepare the response structure
    return response()->json([
        'status' => 200,
        'message' => 'Company Master Data Fetched Successfully.',
        'code' => 'company_master',
        'data' => [
            'companies' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(), // Corrected: Call `total()` on paginator
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'next_page_url' => $paginatedData->nextPageUrl(),
                'prev_page_url' => $paginatedData->previousPageUrl(),
            ],
        ],
    ]);
}




    public function add(Request $request) {
       
            ini_set('memory_limit', '256M');
            date_default_timezone_set('Asia/Kolkata');
            // $data = $request->input(); // This will return the decoded JSON data as an array
           
           
            $validator = Validator::make($request->input(), [
                'legalName' => 'required',
                'company_type' => 'required',
                'entityType' => 'required',
                'listDisplayName' => 'required',
                'transactionCategory' => 'required',
                // 'gst_treatment' => 'required',
                'gst' => [
                    function ($attribute, $value, $fail) use ($request) {
                        $gstTreatment = $request->input('gstTreatment');
                        if ($gstTreatment == '569' && empty($value)) {
                            $fail('For Registered Business (Regular), GST is required.');
                        }
                        if ($gstTreatment == '568' && empty($value)) {
                            $fail('For Registered Business (Composition), GST is required.');
                        }
                    }
                ],
            ]);
       
        // If validation fails, return the errors
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            // print_r($request->input('paymentTerm'));
            // die;
        
            if ($request->input('accountOpened')) {
                
            

                try {
                    $accountOpened = $request->input('accountOpened');
                
                    // Attempt to parse the date, if invalid, an exception will be thrown
                    $parsedDate = Carbon::parse($accountOpened);
                
                    $request->merge(['accountOpened' => $parsedDate->format('Y-m-d')]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 0,
                        'error' => 'Invalid date format for account_opened.',
                    ], 422);
                }
            }

    
        // Handle company URN
        $company_urn = $request->input('company_urn');
        
        
        // Handle `co_id`, finputback to session if not provided
        $co_id = !empty($request->input('co_id')) 
            ? $request->input('co_id')
            // CustomHelpers::getIDFromURN(['urn' => $request->input('co_id'), 'type' => 'company'])

            : session('co_id');
        
        
        $request->merge(['co_id' => $co_id]);
        
        // Handle `billing_company_id`, finputback to session if not provided
        $billing_company_id = !empty($request->input('vendorBranch'))
            ? $request->input('vendorBranch')
            // $this->getIDFromURN(['urn' => $request->input('billing_company_id'), 'type' => 'company'])
            : session('co_id', 0);
            
        $co_id = !empty($request->input('co_id')) 
            ? $request->input('co_id')
            // CustomHelpers::getIDFromURN(['urn' => $request->input('co_id'), 'type' => 'company'])

            : session('co_id');
        
        
        $request->merge(['billing_company_id' => $billing_company_id]);
        $ret = ['err' => 0];
        $check = true;

        // Check if the company exists by urn
        $company = MasterCompany::where('company_urn', $request->input('company_urn'))->first();
    
        // Check if the display name matches the existing company
        if ($request->input('company_urn') == ''&&$company && $request->input('listDisplayName') == $company->display_name) {
            $check = false;
        
        }
        
        // If a duplicate company is found, return immediately
        if (!$check) {
        
            

            return response()->json($ret);
        }
        
        // Cinput the duplicate check service (for COMPANY, name, GSTIN, and company ID)
        $duplicate = $this->duplicateService->isDuplicate([
            'checkin' => 'COMPANY',
            'name' => $request->input('display_name'),
            'gstin' => $request->input('gst_no'),
            'id' => $request->input('co_id')
        ]);
        

        $ret['duplicate'] = $duplicate;
    
        if ($duplicate['status']) {
            

            $ret['err'] = 200;
            $ret['msg'] = $duplicate['msg'];
            return response()->json($ret);
        }
    
        
    
    

        
        $urnData = [
            'urn' => $request->input('sales_rep_urn'),  // urn passed from request
            'type' => 'user' // Assuming you are checking for company URN
        ];
        // Handle sales representative URN
        
        
        // Handle notify triggers, implode array to a string
        $notify_triggers = '';
    if ($request->has('notify_triggers')) {
    
        $notify_triggers_input = $request->input('notify_triggers');
    
        // Check if it's an array before imploding
        if (is_array($notify_triggers_input)) {
            
            $notify_triggers = implode(",", $notify_triggers_input);
        } else {
            // Handle case where it's not an array
            $notify_triggers = $notify_triggers_input; // It's already a string, so no need to implode
        }
    }
        
        
        // Handle ledger ID
        $ledger_id = $request->filled('ledger_id') ? $request->input('ledger_id') : '';
    
        // Handle usage tags
        $usagetags = $request->has('usagetags') && count($request->input('usagetags')) > 0
            ? $request->input('usagetags')
            : [];
            $company_urn = $request->input('company_urn');
            $qtype = $request->input('qtype');
            $ret = [];
            
            DB::transaction(function () use ($request, &$ret, &$company_urn, &$qtype) {
                $sales_rep_id = !empty($request->input('sales_rep_urn'))
            ? $request->input('sales_rep_urn'): 0;
            \Log::info('Session user_id in MasterCompanyController:', [Session::get('user_id')]);
                // Handle Update
                if ($company_urn != '' && $qtype === "UPDATE") {
                    
                    $company = MasterCompany::where('company_urn', $company_urn)->first();
                    // $usagetags = json_encode($request->input('usagetags', [])); // Convert 'usagetags' array to JSON string
                    // $notify_emails = json_encode($request->input('notify_emails', [])); // Convert 'notify_emails' array to JSON string
                    // $notify_mobiles = json_encode($request->input('notify_mobiles', [])); // Convert 'notify_mobiles' array to JSON string
                    // $notify_triggers = json_encode($request->input('notify_triggers', [])); // Con
                    
                    if ($company) {
                        $company->trans_category = $request->input('transactionCategory');
                        $company->company_name = $request->input('legalName');
                        $company->company_type = $request->input('company_type');
                        $company->entity_type = $request->input('entityType');
                        // $company->usagetags = json_encode($request->input('usagetags'));
                        $company->display_name = $request->input('listDisplayName');
                        // $company->nick_name = html_entity_decode(strip_tags($request->input('nick_name')));
                        $company->group_name = html_entity_decode(strip_tags($request->input('groupName')));
                        $company->industry_type_id = $request->input('industryType');
                        // $company->msmeRegistered = $request->input('msmeRegistered');
                        $company->msme_udyam_type = $request->input('allStatuatoryData.msmeRegisteredType');
                        $company->msme_udyam_no = $request->input('allStatuatoryData.msmeRegisteredNo');
                        $company->billing_company_id = $request->input('vendorBranch');
                        $company->cin = $request->input('allStatuatoryData.cin');
                        $company->pan = $request->input('allStatuatoryData.pan');
                        $company->gst_no = $request->input('allStatuatoryData.gst');
                        $company->gst_treatment = $request->input('allStatuatoryData.gstTreatment');
                        $company->account_opened = $request->input('allStatuatoryData.accountOpened');
                        // $company->credit_status = $request->input('credit_status');
                        $company->credit_period = $request->input('allStatuatoryData.paymentTerm');
                        $company->currency = $request->input('allStatuatoryData.currency');
                        // $company->sales_rep_id = $request->input('sales_rep_urn');
                        // $company->notify_emails = str_replace(['&quot;', '"'], '', json_encode($request->input('notify_emails')));
                        // $company->notify_mobiles = str_replace(['&quot;', '"'], '', json_encode($request->input('notify_mobiles')));
                        // $company->notify_triggers = !empty($request->input('notify_triggers')) ? (str_replace(['&quot;', '"'], '', json_encode($request->input('notify_triggers')))) : "";
                        // $company->suspend = $request->input('suspend', 0);
                        // $company->disabled = $request->input('disabled', 0);
                        $company->doe_user_id = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                        $company->doe = date('Y-m-d H:i:s');
                        
                        
                        // Save the updated company details
                        $company->save();
                        }
                    } else {
                        
                            // Handle Insert
                            $qtype = "INSERT";
                            $prefix = 'TE'.strtoupper(substr($request->input('company_type'), 0, 2)).strtoupper(substr($request->input('company_name'), 0, 2));
                            // $company_urn = $this->URNGeneratorService->generate_URN_specific(['WL' => '0', 'WR' => '0', 'TXT' => $prefix, 'for' => 'company']);
                            // $prefix = 'CMP';  // Assuming this is set based on your logic

                            // Cinput the URN generation method from the service
                            $company_urn = $this->urnService->generate_URN_specific([
                                'WL' => '0', 
                                'WR' => '0', 
                                'TXT' => $prefix, 
                                'for' => 'company'
                            ]);
                            
                            // Convert array fields to JSON strings
                            // print_r(gettype(json_encode($request->input('trans_category'))));
                            // die();
                            // $trans_category = is_array($request->input('trans_category')) ? html_entity_decode(strip_tags(json_encode($request->input('trans_category')))): $request->input('trans_category');
                            // $trans_category = is_array($request->input('trans_category')) ? html_entity_decode(strip_tags(json_encode($request->input('trans_category')))): $request->input('trans_category');
                            // $usagetags = !empty($request->input('usagetags'))?json_encode($request->input('usagetags')):[];
                            // $notify_emails =  str_replace('&quot;', '', html_entity_decode(strip_tags(json_encode($request->input('notify_emails')))));
                            // $notify_mobiles = json_encode($request->input('notify_mobiles'));
                            // $notify_triggers = json_encode($request->input('notify_triggers'));

                            // Generate the company URN
                            $prefix = 'TE' . strtoupper(substr($request->input('company_type'), 0, 2)) . strtoupper(substr($request->input('company_name'), 0, 2));
                            $company_urn = $this->urnService->generate_URN_specific(['WL' => '0', 'WR' => '0', 'TXT' => $prefix, 'for' => 'company']);
                            $sales_rep_id = !empty($request->input('sales_rep_urn'))
                            ? $request->input('sales_rep_urn'): 0;
                            // print_r($request->input('allStatuatoryData.paymentTerm'));
                            // die;
                                                // Insert into database
                    $company = MasterCompany::create([
                        'company_urn' => $company_urn,
                        'trans_category' => $request->input('transactionCategory'),
                        'company_name' => $request->input('legalName'),
                        'company_type' => $request->input('company_type'),
                        'entity_type' => $request->input('entityType'),
                        // 'usagetags' => json_encode($request->input('usagetags')),
                        'display_name' => $request->input('listDisplayName'),
                        // 'nick_name' => html_entity_decode(strip_tags($request->input('nick_name'))),
                        'group_name' => html_entity_decode(strip_tags($request->input('groupName'))),
                        'industry_type_id' => $request->input('industryType'),

                        // 'msmeRegistered' => $request->input('msmeRegistered'),
                        'msme_udyam_type' => $request->input('allStatuatoryData.msmeRegisteredType'),
                        'msme_udyam_no' => $request->input('allStatuatoryData.msmeRegisteredNo'),
                        'billing_company_id' => $request->input('vendorBranch'),

                        'cin' => $request->input('allStatuatoryData.cin'),
                        'pan' => $request->input('allStatuatoryData.pan'),
                        'gst_no' => $request->input('allStatuatoryData.gst'),
                        'gst_treatment' => $request->input('allStatuatoryData.gstTreatment'),
                        'account_opened' => $request->input('allStatuatoryData.accountOpened'),
                        // 'credit_status' => $request->input('credit_status'),
                        'credit_period' => $request->input('allStatuatoryData.paymentTerm'),
                        'currency' => $request->input('allStatuatoryData.currency'),
                        // 'sales_rep_id' => $request->input('sales_rep_urn'),
                        // 'notify_emails' =>  str_replace(['&quot;', '"'], '', json_encode($request->input('notify_emails'))),
                        // 'notify_mobiles' => str_replace(['&quot;', '"'], '', json_encode($request->input('notify_mobiles'))),
                        // 'notify_triggers' => !empty($request->input('notify_triggers'))?(str_replace(['&quot;', '"'], '', json_encode($request->input('notify_triggers')))):"",
                        // 'suspend' => $request->input('suspend', 0),
                        // 'disabled' => $request->input('disabled', 0),
                        'doe_user_id' => !empty($_SESSION['user_id'])?$_SESSION['user_id']:0,
                        'doe' => date('Y-m-d H:i:s'),
                        // 'billing_company_id' => !empty($request->input('billing_company_id')[0])?$request->input('billing_company_id')[0]:0,
                        // 'co_id' => !empty($request->input('co_id')[0])?$request->input('co_id')[0]:0,
                    ]);
                
                
                        }
                        
                        if ($company) {
                            
                            
                            $ret['status'] = 200;
                            // $ret['message'] = Carbon::now()->format('H:i:s') . " Main information {$qtype}ed!";
                            $ret['message'] = Carbon::now()->format('H:i:s') . " Main information " . $qtype . "";

                            $ret['urn'] = $company_urn;
                            $allContactData = self::insert_master_company_contact($company['company_id'],$request->input('allContactData'));
                        // print_r($allContactData);die;
                        
                            if($allContactData){
                                $ret['status'] = 200;
                                // $ret['message'] = Carbon::now()->format('H:i:s') . " Main information {$qtype}ed!";
                                $ret['message'] .= " | " . $allContactData['msg'];
                
                                $ret['new contact id'] = $allContactData['contact_id'];
                            }

                            $allBankData = self::insert_link_bankdetails($company['company_id'],$request->input('allBankDetails'));
                        // print_r($allBankData);die;
                        
                            if($allBankData){
                                $ret['status'] = 200;
                                // $ret['message'] = Carbon::now()->format('H:i:s') . " Main information {$qtype}ed!";
                                $ret['message'] .= " | " . $allBankData['msg'];
                
                                $ret['new bank id'] = $allBankData['bd_id'];
                            }

                            $master_address = self::insert_master_company_address($company['company_id'],$company['company_urn'],$request->input('allAddressData'));
                            
                            if($master_address){
                                $ret['status'] = 200;
                                // $ret['message'] = Carbon::now()->format('H:i:s') . " Main information {$qtype}ed!";
                                $ret['message'] .= " | " . $master_address['msg'];
                
                                $ret['new address id'] = $master_address['address_id'];
                            }
                } else {
                    
                    $ret['status'] = 201;
                    $ret['msg'] = Carbon::now()->format('H:i:s') . " Main information not changed!";
                    $ret['urn'] = $company_urn;
                }
                
            });

            // Return the result as JSON
            return response()->json($ret);
    }
       
        public function insert_master_company_contact($company_id, $allContactData)
    {  
        

        if ($company_id) {
            // Validate the allContactData array
            $validator = Validator::make(['allContactData' => $allContactData], [
                'allContactData' => 'required|array',
                'allContactData.*.contactPerson' => 'required|string|max:255', // contact_name is required
            ]);

            // If validation fails, return the errors
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            foreach ($allContactData as $contact) {
                // Determine if we are updating or creating a contact
                if (!empty($contact['company_id']) && $contact['company_id'] > 0) {
                    // Update existing contact
                    $existingContact = MasterCompanyContact::find($contact['company_id']);

                    if ($existingContact) {
                        $existingContact->update([
                            'company_id' => $company_id,
                            'contact_name' => $contact['contactPerson'],
                            'contact_type' => $contact['contactType'],
                            'contact_email' => $contact['email'],
                            'contact_mobile' => $contact['mobile'],
                            'contact_phone' => $contact['landline'],
                            'disabled' => $contact['disabled'] ?? 0,
                        ]);

                        $ret['status'] = 200;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact Updated!";
                        $ret['contact_id'] = $contact['contact_id'];
                    } else {
                        $ret['status'] = 201;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact not found!";
                    }
                } else {
                    // Insert new contact
                    $newContact = MasterCompanyContact::create([
                        'company_id' => $company_id,
                            'contact_name' => $contact['contactPerson'],
                            'contact_type' => $contact['contactType'],
                            'contact_email' => $contact['email'],
                            'contact_mobile' => $contact['mobile'],
                            'contact_phone' => $contact['landline'],
                        'disabled' => 1, // Default value
                        'doe' => date('Y-m-d H:i:s'),
                        'deb_user_id' => !empty($_SESSION['user_id'])?$_SESSION['user_id']:0,
                        'co_id' => $company_id,
                    ]);

                    if ($newContact) {
                        $ret['status'] = 200;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact Created!";
                        $ret['contact_id'] = $newContact->contact_id;
                    } else {
                        $ret['status'] = 201;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact Not Created!";
                    }
                }
            }

            return $ret;  // Move return statement outside of the foreach loop to return after all contacts are processed
        } else {
            $ret['status'] = 201;
            $ret['msg'] = Carbon::now()->format('H:i:s') . " Invalid Company URN!";
            return $ret;
        }
    }
    public function insert_link_bankdetails($company_id, $allBankData)
    {  
        

        if ($company_id) {
            // Validate the allBankData array
            $validator = Validator::make(['allBankData' => $allBankData], [
                'allBankData' => 'required|array',
                // 'allBankData.*.contactPerson' => 'required|string|max:255', // contact_name is required
            ]);

            // If validation fails, return the errors
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            foreach ($allBankData as $bank) {
                // Determine if we are updating or creating a contact
                if (!empty($bank['company_id']) && $bank['company_id'] > 0) {
                    // Update existing contact
                    $existingBankDetail = LinkBankDetail::find($bank['company_id']);

                    if ($existingBankDetail) {
                        $existingBankDetail->update([
                            'ac_id' => $company_id,
                            'account_name' => $bank['payeeName'],
                            'bank_ac_no' => $bank['accountNo'],
                            'bank_ac_type' => $bank['accountType'],
                            'ifsc' => $bank['ifsc'],
                            'bank_name' => $bank['bankName'],
                            'bank_address' => $bank['bankAddress'],
                            'bank_ac_location' => $bank['accountLocation'],
                            'swiftcode' => $bank['swiftCode'],
                            'default_ac' => $bank['default'],
                            'is_active' => $bank['is_active'] ?? 0,
                        ]);

                        $ret['status'] = 200;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact Updated!";
                        $ret['contact_id'] = $bank['contact_id'];
                    } else {
                        $ret['status'] = 201;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Contact not found!";
                    }
                } else {
                    // Insert new contact
                    $newBank = LinkBankDetail::create([
                        'ac_id' => $company_id,
                            'account_name' => $bank['payeeName'],
                            'bank_ac_no' => $bank['accountNo'],
                            'bank_ac_type' => $bank['accountType'],
                            'ifsc' => $bank['ifsc'],
                            'bank_name' => $bank['bankName'],
                            'bank_address' => $bank['bankAddress'],
                            'bank_ac_location' => $bank['accountLocation'],
                            'swiftcode' => $bank['swiftCode'],
                            'default_ac' => $bank['default'],
                        'is_active' => 1, // Default value
                        'doe' => date('Y-m-d H:i:s'),
                        'deb_user_id' => !empty($_SESSION['user_id'])?$_SESSION['user_id']:0,
                        'co_id' => $company_id,
                    ]);

                    if ($newBank) {
                        $ret['status'] = 200;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Bank Record Created!";
                        $ret['bd_id'] = $newBank->bd_id;
                    } else {
                        $ret['status'] = 201;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Bank Record Not Created!";
                    }
                }
            }

            return $ret;  // Move return statement outside of the foreach loop to return after all contacts are processed
        } else {
            $ret['status'] = 201;
            $ret['msg'] = Carbon::now()->format('H:i:s') . " Invalid Company URN!";
            return $ret;
        }
    }
    public function insert_master_company_address($company_id, $company_urn,$allAddressData)
    {  
    

        if ($company_id) {
        
            
        
            foreach ($allAddressData as $address) {
                
                $address_id = !empty($address['address_id']) ? $address['address_id'] : 0;
                $co_gmap_place_id = !empty($address['co_gmap_place_id']) ? $address['co_gmap_place_id'] : '';
                $co_city  = !empty($address['city ']) ? $address['city '] : '';
                $co_state  = !empty($address['state ']) ? $address['state '] : '';
                $co_iata  = !empty($address['iata ']) ? $address['iata '] : '';
                $co_country  = !empty($address['country ']) ? $address['country '] : '';
                $co_country_code  = !empty($address['country_code ']) ? $address['country_code '] : '';

                if (!empty($company_id) && $company_id > 0) {
                    
                    $co_id = $company_id;
                    $address_type_csv = !empty($address['addressType']) ? implode( ",", $address['addressType'] ) : '';
                    // Update existing contact
                    $existingAddress = MasterCompanyAddress::find($address_id);
                    
                    if ($existingAddress) {
                    

                        $existingAddress->update([
                            'company_id' => $company_id,
                            'co_gmap_place_id' => $co_gmap_place_id,
                            'co_hno' => $address['company_details'],
                            'co_locality' => $address['co_locality'],
                            'co_locality2' => $address['co_locality2'],
                            'location_id' => $address['location_id'],
                            'co_city' => $co_city,
                            'co_state' => $co_state,
                            'co_pincode' => $address['co_pincode'] ?  $address['co_pincode'] : '',
                            'co_iata' => $co_iata,
                            'co_country' => $co_country,
                            'co_country_code' => $co_country_code,
                            'address_type_csv' => $address_type_csv,
                            'default' => $address['default'] ? $address['default']:0,

                        ]);

                        $ret['status'] = 200;
                        $ret['msg'] = Carbon::now()->format('H:i:s') . " Address Updated!";
                        $ret['address_id'] = $existingAddress['address_id'];
                    }else {
                
                        // Insert new Address
                        $newAddress = MasterCompanyAddress::create([
                                'company_id' => $company_id,
                                'co_gmap_place_id' => $co_gmap_place_id,
                                'co_hno' => $address['companyDetails'] ?$address['companyDetails'] :'' ,
                                'co_locality' => $address['locality1'],
                                'co_locality2' => $address['locality2'],
                                'co_placename' => $address['companyName'],
                                'co_city' => $co_city,
                                'co_state' => $co_state,
                                'co_pincode' => empty($address['pincode']) ? $address['pincode'] :'',
                                'co_iata' => $co_iata,
                                'co_country' => $co_country,
                                'co_country_code' => $co_country_code,
                                'address_type_csv' => $address_type_csv,
                                'default' => !empty($address['default']) ? $address['default'] : 0,
                        ]);
                        
                        if ($newAddress) {
                            $ret['status'] = 200;
                            $ret['msg'] = Carbon::now()->format('H:i:s') . "Address Created!";
                            $ret['address_id'] = $newAddress['address_id'];
                        }
                }
            }
        

            return $ret;  // Move return statement outside of the foreach loop to return after all contacts are processed
    
            }
        }
    } 
    public function enableDisableClient(Request $request)
    {
       
        $companyUrn = $request->input('company_urn');
        $status = $request->input('status');
// print_R($status);

        if (empty($companyUrn)) {
            return response()->json([
                'status' => 0,
                'msg' => 'Company URN is required',
            ], 400);
        }

        // Update the company's disable_status
        $company = MasterCompany::where('company_urn', $companyUrn)->first();

        if (!$company) {
            return response()->json([
                'status' => 0,
                'msg' => 'Company not found',
                'urn' => $companyUrn,
            ], 404);
        }

        $company->disable_status = !$status;
        $company->is_active = $status;
        if ($company->save()) {
            $msg = $status == 0 ? 'Company Disabled Successfully!!' : 'Company Enabled Successfully!!';
            return response()->json([
                'status' => 200,
                'msg' => $msg,
                'urn' => $companyUrn,
            ]);
        } else {
            // Handle the failure case
            return response()->json([
                'status' => 0,
                'msg' => 'Failed to update company status',
                'urn' => $companyUrn,
            ], 500);
        }
    }
    public function company_type(Request $request){

        $company_type = MasterCompany::where('company_type', $request->input('list_name'))->get();
        if($company_type){
        return response()->json([
            'status' => 200,
            'msg' => "Company Type fetched",
            'data' =>$company_type
        ]);
    }else{
        return response()->json([
            'status' => 201,
            'msg' => "Company Type Not  fetched",
            'data' =>[]
        ]); 
    }
}

    public function company_type_supplier(Request $request){

        $company_type = MasterCompany::where('company_type', "supplier")->get();
        if($company_type){
        return response()->json([
            'status' => 200,
            'msg' => "Company Type fetched",
            'data' =>$company_type
        ]);
    }else{
        return response()->json([
            'status' => 201,
            'msg' => "Company Type Not  fetched",
            'data' =>[]
        ]); 
    }
    

       
    }
    function companyList(array $arr = [])
{
    $ret = [];
    $whe = [1];
    $address = true;
    $noid = true;

    if (empty($arr)) {
        $arr = request()->all();
    }

    $orderBy = $arr['sort'] ?? 'mc.company_name';
    $orderBy = str_ireplace(['entry date', 'company name'], ['doe', 'company_name'], $orderBy);

    $query = DB::table('master_company as mc')
        ->leftJoin('master_company as mc2', 'mc.co_id', '=', 'mc2.company_id')
        ->leftJoin('master_company as mc3', 'mc.billing_company_id', '=', 'mc3.company_id')
        ->leftJoin('user_master as um', 'um.user_id', '=', 'mc.sales_rep_id')
        ->leftJoin('master_ledgers as ml', function ($join) {
            $join->on('mc.company_id', '=', 'ml.linked_id')
                ->where('ml.linked_type', 'company');
        })
        ->select([
            'mc.*',
            'mc2.company_urn as co_id2',
            'mc3.company_urn as billing_company_id2',
            DB::raw("IFNULL(ml.ledger_id, '') as ledger_id")
        ]);

    if (!empty($arr['company_urn'])) {
        $query->where('mc.company_urn', $arr['company_urn']);
    }

    $query->where('mc.disable_status', $arr['disable_client'] ?? 0);

    if (!empty($arr['noledger_id'])) {
        $query->whereNull('ml.ledger_id');
    }

    if (!empty($arr['company_type'])) {
        if (str_contains($arr['company_type'], ';')) {
            $query->whereIn('mc.company_type', ['vendor', 'Supplier']);
            $orderBy = 'mc.company_type, mc.display_name';
        } elseif (strtoupper($arr['company_type']) === 'VENDOR') {
            $query->whereIn('mc.company_type', ['vendor', 'Supplier']);
            $orderBy = 'mc.company_type, mc.display_name';
        } elseif ($arr['for'] === 'quotation') {
            $query->where('mc.company_type', 'Client');
        } else {
            $query->where('mc.company_type', $arr['company_type']);
        }
    }

    if (isset($arr['imported_ZOHO'])) {
        $query->where('mc.contact_id', $arr['imported_ZOHO'] ? '!=' : '=', '');
    }

    if ($arr['for'] == 'quotation' && !in_array('pricing_team', session('roles'))) {
        $query->where('um.user_name', session('user_name'));
    }

    if (!empty($arr['search'])) {
        $query->where(function ($q) use ($arr) {
            $q->where('mc.company_name', 'like', '%' . $arr['search'] . '%')
              ->orWhere('mc.display_name', 'like', '%' . $arr['search'] . '%');
        });
    }

    if ($arr['for'] == 'autocomplete') {
        $query->select([
            'mc.company_urn as id',
            'mc.display_name as label',
            'mc.company_name',
            'mc.company_type',
            'mc.shipment_category_id',
            'um.user_urn as sales_rep_urn',
            'um.user_name as sales_rep_name',
            'mc.pan',
            'mc.gst_no',
            'mc.tds_rate'
        ]);
        $address = false;
    }

    if (!empty($arr['place_of_supply'])) {
        $query->where('mc.zoho_placeof_contact', $arr['place_of_supply']);
    }

    // Apply ordering
    $query->orderByRaw($orderBy);

    if (empty(request('paging'))) {
        $ret['ds'] = $query->get()->toArray();
    } else {
        $perPage = $arr['pp'] ?? 10;
        $currentPage = $arr['cp'] ?? 1;
        $ret['ds'] = $query->paginate($perPage, ['*'], 'page', $currentPage);
    }

    if ($address) {
        $ret['ds'] = $ret['ds']->map(function ($item) use ($noid) {
            $companyId = $item->company_id;

            $item->address = $this->companyAddress($companyId);
            $item->contacts = $this->companyContacts($companyId);
            $item->sales_rep_urn1 = $this->salesRepresentative($companyId);
            $item->currency1 = $this->currency($companyId);
            $item->gst_treatment1 = $this->gstTreatment1($companyId);
            $item->banks = $this->bankDetails($companyId, 'company');
            $item->trans_category_name1 = $this->transCategory1($companyId);
            $item->approvers = $this->getApprovers($companyId, 'company');

            if ($noid) {
                unset($item->sales_rep_id, $item->company_id, $item->co_id2, $item->billing_company_id2);
            }
            $item->transc = $this->getTransNames($item->trans_category);
            return $item;
        });
    }

    return empty(request('paging')) ? $ret['ds'] : $ret;
}
public function company_details_from_id(Request $request) {
    $company_id = $request->input('company_id');

    $rs = DB::table('master_company_address')
        ->join('geo_locations', 'master_company_address.location_id', '=', 'geo_locations.location_id')
        ->where('master_company_address.company_id', $company_id)
        ->select(
            'master_company_address.*', 
            'geo_locations.*', 
           
        )
        ->get();

    if ($rs->isNotEmpty()) {
        return response()->json([
            'status' => 200,
            'msg' => "Company Address fetched",
            'data' => $rs
        ]);
    }

    return response()->json([
        'status' => 404,
        'msg' => "No data found",
        'data' => []
    ]);
}
}
    

   





