<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Facades\Tenancy;
class TenantController extends Controller
{
//     public function createTenant(Request $request)
//     {
//         $validated = $request->validate([
//             // 'id' => 'required|unique:tenants,id',
//             'name' => 'required',
//             'domain' => 'required|unique:tenants,domain',
//         ]);

//         $tenant = Tenant::create([
//             // 'id' => $validated['id'],
//             'name' => $validated['name'],
//             'domain' => $validated['domain'],
//         ]);
// // print_r($tenant);die;
//         return response()->json(['message' => 'Tenant created successfully!', 'tenant' => $tenant]);
//     }


public function createTenant(Request $request)
{
    Log::debug('Validating request', ['request' => $request->all()]);
    $validated = $request->validate([
        'name' => 'required',
        'domain' => 'required',
    ]);

    // 2. Insert tenant information into the central tenant table
    $tenantData = $validated;
    Log::debug('Tenant data after validation', ['tenantData' => $tenantData]);

    // Insert into the central database table 'tenants'
    $tenant = DB::connection('central_tenant_db')->table('tenants')->insert([
        'name' => $tenantData['name'],
        'domain' => $tenantData['domain'],
        'database' => 'tenant_' . $tenantData['name'],
        'created_at' => now(),
    ]);
    Log::debug('Inserted tenant data into central database', ['tenant' => $tenant]);

    // 3. Create a new tenant database
    try {
        Log::debug("Creating tenant database: tenant_{$tenantData['name']}");
        DB::statement("CREATE DATABASE `tenant_{$tenantData['name']}`");
        Log::debug("Tenant database created successfully.");
    } catch (\Exception $e) {
        Log::error('Failed to create tenant database', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create tenant database'], 500);
    }

    // 4. Switch the database connection to the newly created tenant database
    try {
        config(['database.connections.tenant' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => 'tenant_' . $tenantData['name'], // Dynamically set the tenant database
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        Log::debug('Switched database connection to the new tenant database');
    } catch (\Exception $e) {
        Log::error('Failed to switch database connection', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to switch database connection'], 500);
    }

    // 5. Run migrations for the new tenant database
    try {
        Log::debug('Initializing tenancy for the new tenant', ['tenant' => $tenantData['name']]);
        Tenancy::initialize($tenant);

        Log::debug('Running tenant migrations');
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant', // Specify the tenant connection
            '--path' => 'database/migrations/tenant', // Tenant-specific migrations
            '--force' => true, // Ensure migrations are run in production
        ]);
        if ($exitCode !== 0) {
            Log::error('Tenant migrations failed', ['exitCode' => $exitCode, 'output' => Artisan::output()]);
            return response()->json(['error' => 'Failed to run tenant migrations', 'details' => Artisan::output()], 500);
        }

        Log::debug('Tenant migrations completed successfully.');
    } catch (\Exception $e) {
        Log::error('Failed to run tenant migrations', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['error' => 'Failed to run tenant migrations', 'details' => $e->getMessage()], 500);
    }

    // 6. End tenant context
    Tenancy::end();
    Log::debug('Tenant context ended successfully.');

    return response()->json(['message' => 'Tenant created successfully']);
}
public function subscribeUser(Request $request, $userId, $planId)
{
    $user = User::find($userId);
    $plan = SubscriptionPlan::find($planId);

    if (!$user || !$plan) {
        return response()->json(['error' => 'User or Plan not found'], 404);
    }

    // Create a user subscription
    $subscription = new UserSubscription([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'start_date' => now(),
        'end_date' => now()->addMonths(1), // Example: 1-month subscription
    ]);

    $subscription->save();

    return response()->json(['message' => 'User subscribed to plan']);
}

}

