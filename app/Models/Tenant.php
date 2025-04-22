<?php
namespace App\Models;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Request;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Facades\Tenancy;
class Tenant extends BaseTenant implements TenantContract
{
    // Set the database name dynamically
    protected $fillable = ['id', 'name', 'domain', 'database'];
    public function tenantModuleAccess()
    {
        return $this->hasMany(TenantModuleAccess::class);
    }
    public function databaseName(): string
    {
        return 'tenant_' . $this->name;
    }

    // Implement other required methods from the TenantContract
    public function getTenantKey(): string
    {
        return $this->getKey();  // Use the tenant's primary key
    }

    public function getTenantKeyName(): string
    {
        return 'id';  // Assuming your tenant model's primary key is 'id'
    }

    public function run(callable $callback)
    {
        return $callback($this);
    }

    public function setInternal(string $key, $value)
    {
        // Implement this method if needed, depending on your requirements
    }
    public function createTenant(Request $request)
{
    Log::info('Test log entry');
    // 1. Validate the incoming request
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'domain' => 'required|unique:tenants,domain',
    ]);

    // Log validated data
    Log::info('Validated tenant data:', $validated);

    // 2. Insert tenant information into the central tenant table
    $tenantData = $validated;

    // Log tenant data before inserting
    Log::info('Inserting tenant into central database:', $tenantData);

    // Insert tenant data into the central tenant database
    $tenant = DB::connection('central_tenant_db')->table('tenants')->insert([
        'name' => $tenantData['name'],
        'domain' => $tenantData['domain'],
        'database' => 'tenant_' . $tenantData['name'],
        'created_at' => now(),
    ]);

    // Log the result of tenant insertion
    Log::info('Tenant inserted into central database:', ['tenant' => $tenant]);

    // 3. Create a new tenant database
    try {
        DB::statement("CREATE DATABASE `tenant_{$tenantData['name']}`");
        Log::info('Tenant database created:', ['database' => 'tenant_' . $tenantData['name']]);
    } catch (\Exception $e) {
        Log::error('Failed to create tenant database:', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create tenant database'], 500);
    }

    // 4. Switch the database connection to the newly created tenant database
    config(['database.connections.tenant' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => 'tenant_' . $tenantData['name'],
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]]);

    // Log the database configuration for debugging
    Log::info('Database connection switched to tenant database', ['tenant_db' => 'tenant_' . $tenantData['name']]);

    // 5. Run migrations for the new tenant database
    try {
        Artisan::call('migrate', [
            '--database' => 'tenant', // Specify the tenant connection
            '--path' => 'database/migrations/tenant', // Tenant-specific migrations
            '--force' => true, // Ensure migrations are run in production
        ]);
        Log::info('Tenant migrations run successfully');
    } catch (\Exception $e) {
        Log::error('Failed to run tenant migrations:', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to run tenant migrations'], 500);
    }

    // Optionally, you can also seed the tenant database with initial data
    // Artisan::call('db:seed', ['--class' => 'TenantSeeder', '--force' => true]);

    // 6. End tenant context
    Tenancy::end();

    // Log success message
    Log::info('Tenant creation and migrations completed successfully');

    return response()->json(['message' => 'Tenant created and migrations run successfully']);
}
}
