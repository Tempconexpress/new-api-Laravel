<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;
use Illuminate\Support\Facades\File;

class MigrateTenantDatabase extends Command
{
    protected $signature = 'migrate:tenant {tenant}';
    protected $description = 'Migrate the tenant\'s database by running specific tenant migration files';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (!$tenant) {
            $this->error('Tenant not found!');
            return;
        }

        // Set the tenant's database connection
        config(['database.connections.tenant.database' => $tenant->database]);
        \DB::purge('tenant'); // Purge old connection to refresh

        // Run the default migrations
        Artisan::call('migrate', ['--database' => 'tenant']);

        // Run tenant-specific migrations
        $tenantMigrationPath = database_path('migrations/tenant');
        if (File::exists($tenantMigrationPath)) {
            $migrations = File::files($tenantMigrationPath);

            foreach ($migrations as $migration) {
                $this->info('Running tenant migration: ' . $migration->getFilename());
                include_once $migration->getPathname();
                $migrationClass = 'Create' . ucfirst($migration->getBasename('.php')) . 'Table'; // Adjust according to naming convention
                (new $migrationClass)->up();
            }
        } else {
            $this->error('Tenant migrations directory not found.');
        }

        $this->info('Tenant migrations completed.');
    }
}
