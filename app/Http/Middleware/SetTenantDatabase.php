<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SetTenantDatabase
{
    // Fetch tenant based on the request header
    public function getTenantFromRequest(Request $request)
    {
        // Get the tenant name from the request header
        $tenantName = $request->header('Tenant-Name');
        
        // Log the tenant name to verify it's being passed correctly
        Log::info("Fetching tenant for: " . $tenantName);
        
        // If no tenant name is provided, return null
        if (!$tenantName) {
            Log::error("Tenant-Name header is missing.");
            return null;
        }

        // Fetch the tenant record from the database
        $tenant = Tenant::where('domain', $tenantName)->first();

        // Log the result of the tenant lookup
        if ($tenant) {
            Log::info("Tenant found: " . $tenant->domain . " with database: " . $tenant->database_name);
        } else {
            Log::error("Tenant not found for domain: " . $tenantName);
        }

        return $tenant;
    }

    // Middleware handle method
    public function handle($request, Closure $next)
    {
        // Fetch the tenant from the request
        $tenant = $this->getTenantFromRequest($request);

        // If a tenant is found and it has a database name, update the configuration
        if ($tenant && $tenant->database_name) {
            Log::info("Switching to database: " . $tenant->database_name);

            // Set the database connection dynamically
            config(['database.connections.mysql.database' => $tenant->database_name]);

            // Reconnect to the specified database
            \DB::reconnect();
        } else {
            // Return an error if the tenant is not found or doesn't have a database name
            Log::error("Unable to set database. Tenant is missing or invalid database name.");
            return response()->json(['error' => 'Tenant not found or invalid database name'], 404);
        }

        // Continue with the request
        return $next($request);
    }
}
