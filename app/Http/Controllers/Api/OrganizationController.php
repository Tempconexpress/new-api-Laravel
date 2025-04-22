<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMaster;
use App\Models\CentralUserMaster;
use App\Models\Tenant;
use App\Models\Tenant\UserMaster as TenantUserMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeAdminEmail;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'org_name' => 'required|string|max:255',
                'org_industry' => 'required|string',
                'org_address' => 'required|string',
                'org_website' => 'nullable|url',
                'enable_2fa' => 'boolean',
                'plan_id' => 'required|exists:plans,id',
            ]);

            // Generate org_id
            $baseLetters = substr(preg_replace('/[^a-zA-Z]/', '', $validatedData['org_name']), 0, 4);
            $baseLetters = strtoupper($baseLetters ?: 'ORG');
            $maxSequence = OrganizationMaster::max(DB::raw('CAST(SUBSTRING_INDEX(org_id, "_", -1) AS UNSIGNED)')) ?? 0;
            $newSequence = $maxSequence + 1;
            $randomNumbers = Str::random(3);
            $orgId = $baseLetters . $randomNumbers . '_' . $newSequence;

            // Create organization
            $organization = OrganizationMaster::create([
                'org_id' => $orgId,
                'org_name' => $validatedData['org_name'],
                'org_industry' => $validatedData['org_industry'],
                'org_address' => $validatedData['org_address'],
                'org_website' => $validatedData['org_website'],
                'plan_id' => $validatedData['plan_id'],
                'enable_2fa' => $validatedData['enable_2fa'] ?? false,
                'is_active' => true,
                'created_by' => auth()->user()->id ?? 'system',
            ]);

            // Generate and store user
            $userId = 'USER_' . Str::random(8);
            $password = Str::random(12);
            $user = CentralUserMaster::create([
                'user_id' => $userId,
                'org_id' => $orgId,
                'password' => bcrypt($password),
                'email' => $request->input('contact_email', 'admin@' . Str::slug($validatedData['org_name']) . '.com'),
                'mobile' => $request->input('mobile', null),
                'is_active' => true,
                'created_by' => auth()->user()->id ?? 'system',
            ]);

            // Tenant creation
            $tenantDomain = $orgId . '.' . config('tenancy.tenant_domain_prefix', 'tenant') . '.' . config('tenancy.central_domain', 'yourcentraldomain.com');
            $tenantDatabase = 'tenant_' . Str::slug($orgId);

            DB::connection('central_tenant_db')->table('tenants')->insert([
                'name' => $validatedData['org_name'],
                'domain' => $tenantDomain,
                'database' => $tenantDatabase,
                'created_at' => now(),
            ]);

            $tenantId = DB::connection('central_tenant_db')->getPdo()->lastInsertId();
            $tenantModel = Tenant::find($tenantId);

            Tenancy::initialize($tenantModel);

            config(['database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $tenantDatabase,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]]);
            DB::purge('tenant');

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // $tenantUser = new TenantUserMaster([
            //     'user_id' => $userId,
            //     'password' => bcrypt($password),
            //     'email' => $user->email,
            //     'mobile' => $user->mobile,
            //     'is_active' => true,
            //     'created_by' => auth()->user()->id ?? 'system',
            // ]);
            // $tenantUser->save();

            Tenancy::end();

            // Send welcome email
            Mail::to($user->email)->send(new WelcomeAdminEmail($userId, $password));

            return response()->json([
                'message' => 'Organization and admin user created successfully!',
                'data' => [
                    'org_id' => $orgId,
                    'user_id' => $userId,
                    'tenant_domain' => $tenantDomain,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create organization', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Failed to create organization: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $data = OrganizationMaster::leftJoin('central_user_master', 'organization_master.org_id', '=', 'central_user_master.org_id')
                ->select(
                    'organization_master.id',
                    'organization_master.org_name as orgName',
                    'organization_master.org_industry as orgIndustry',
                    'organization_master.org_address as orgLocation',
                    'organization_master.org_website as orgWebsite',
                    
                    'organization_master.is_active as isEnabled',
                    'central_user_master.name as userContactName',
                    'central_user_master.email as userContactEmail',
                    'central_user_master.mobile as userContactMobile'
                )
                ->get()
                ->map(function ($org) {
                    return [
                        'id' => $org->id,
                        'orgName' => $org->orgName,
                        'orgIndustry' => $org->orgIndustry,
                        'orgLocation' => $org->orgLocation,
                        'orgWebsite' => $org->orgWebsite ?? '',
                       'isEnabled' => $org->isEnabled,
                        'ContactName' => $org->userContactName ?? 'N/A', // User's name from CentralUserMaster
                        'ContactEmail' => $org->userContactEmail ?? 'N/A', // User's email from CentralUserMaster
                        'ContactMobile' => $org->userContactMobile ?? 'N/A', // User's mobile from CentralUserMaster
                    ];
                });
                // print_r($data);die;
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching organizations', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch organizations'], 500);
        }
    }

    /**
     * View a specific organization
     */
    public function show($id)
    {
        try {
            $org = OrganizationMaster::leftJoin('central_user_master', 'organization_master.org_id', '=', 'central_user_master.org_id')
                ->select(
                    'organization_master.id',
                    'organization_master.org_name as orgName',
                    'organization_master.org_industry as orgIndustry',
                    'organization_master.org_address as orgLocation',
                    'organization_master.org_website as orgWebsite',
                    'organization_master.is_active as isEnabled',
                    'central_user_master.name as userContactName', // Assuming 'name' column exists now
                    'central_user_master.email as userContactEmail',
                    'central_user_master.mobile as userContactMobile'
                )
                ->where('organization_master.id', $id)
                ->firstOrFail();
    
            $organization = [
                'id' => $org->id,
                'orgName' => $org->orgName,
                'orgIndustry' => $org->orgIndustry,
                'orgLocation' => $org->orgLocation,
                'orgWebsite' => $org->orgWebsite ?? '',
                // 'contactName' => $org->contactName ?? 'N/A', // Organization's contact name
                // '' => $org->contactEmail ?? 'N/A', // Organization's contact email
                'isEnabled' => $org->isEnabled,
                'ContactName' => $org->userContactName ?? 'N/A', // User's name from CentralUserMaster
                'ContactEmail' => $org->userContactEmail ?? 'N/A', // User's email from CentralUserMaster
                'ContactMobile' => $org->userContactMobile ?? 'N/A', // User's mobile from CentralUserMaster
            ];
    
            return response()->json(['data' => $organization], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching organization', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Organization not found'], 404);
        }
    }

    /**
     * Update organization (full edit or status only)
     */
    public function update(Request $request, $id)
    {
        try {
            // Fetch the organization
            $org = OrganizationMaster::findOrFail($id);
    
            // Fetch the associated user using org_id
            $user = CentralUserMaster::where('org_id', $org->org_id)->first();
    
            // Determine if it's a full edit or status-only update
            $isFullEdit = $request->hasAny(['org_name', 'org_industry', 'org_address', 'org_website', 'contact_name', 'contact_email', 'contact_mobile', 'plan_id']);
    
            if ($isFullEdit) {
                $validated = $request->validate([
                    'org_name' => 'required|string|max:255',
                    'org_industry' => 'required|string',
                    'org_address' => 'required|string',
                    'org_website' => 'nullable|url',
                    'contact_name' => 'nullable|string|max:255',
                    'contact_email' => 'nullable|email',
                    'contact_mobile' => 'nullable|regex:/^[0-9]{10,15}$/',
                    'plan_id' => 'required|exists:plans,id',
                    'isEnabled' => 'boolean',
                ]);
    
                // Update organization details
                $org->update([
                    'org_name' => $validated['org_name'],
                    'org_industry' => $validated['org_industry'],
                    'org_address' => $validated['org_address'],
                    'org_website' => $validated['org_website'] ?? '',
                    'plan_id' => $validated['plan_id'],
                    'is_active' => $validated['isEnabled'] ?? $org->is_active,
                ]);
    
                // Update or create user details
                if ($user) {
                    $user->update([
                        'name' => $validated['contact_name'],
                        'email' => $validated['contact_email'],
                        'mobile' => $validated['contact_mobile'],
                    ]);
                    $user->refresh();
                } else {
                    $user = CentralUserMaster::create([
                        'org_id' => $org->org_id,
                        'name' => $validated['contact_name'] ?? 'N/A',
                        'email' => $validated['contact_email'] ?? 'N/A',
                        'mobile' => $validated['contact_mobile'] ?? 'N/A',
                    ]);
                }
            } else {
                $validated = $request->validate([
                    'isEnabled' => 'required|boolean',
                ]);
    
                $org->update(['is_active' => $validated['isEnabled']]);
            }
    
            // Fetch the updated data directly
            $updatedOrg = OrganizationMaster::find($org->id);
            $user = CentralUserMaster::where('org_id', $org->org_id)->first();
    
            $responseData = [
                'id' => $updatedOrg->id,
                'orgName' => $updatedOrg->org_name,
                'orgIndustry' => $updatedOrg->org_industry,
                'orgLocation' => $updatedOrg->org_address,
                'orgWebsite' => $updatedOrg->org_website ?? '',
                'isEnabled' => $updatedOrg->is_active,
                'userContactName' => $user ? $user->name : 'N/A',
                'userContactEmail' => $user ? $user->email : 'N/A',
                'userContactMobile' => $user ? $user->mobile : 'N/A',
                'planId' => $updatedOrg->plan_id ?? null,
            ];
    
            return response()->json([
                'message' => $isFullEdit ? 'Organization updated successfully' : 'Organization status updated successfully',
                'data' => $responseData,
            ], 200);
        } catch (ValidationException $e) {
            \Log::error('Validation error updating organization', [
                'id' => $id,
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating organization', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'Failed to update organization'], 500);
        }
    }
}