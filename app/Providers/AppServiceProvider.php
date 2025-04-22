<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use App\Services\URNGeneratorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
{
    // Bind the URNGeneratorService to the container
    $this->app->singleton(URNGeneratorService::class, function ($app) {
        return new URNGeneratorService();
    });
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'module' => \App\Models\Module::class,
            'submodule' => \App\Models\SubModule::class,
            'childmodule' => \App\Models\ChildModule::class,
        ]);
    }
}
