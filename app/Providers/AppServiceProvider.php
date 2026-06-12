<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throws an exception if you forget to Eager Load relationships (e.g., Task::with('assignedUser')->get())
        Model::preventLazyLoading(! $this->app->isProduction());
    
        // Throws an exception if you try to mass-assign an unfillable attribute
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        
    }
}
