<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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

        // Register policies (Laravel auto-discovers, but explicit for clarity)
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
