<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Sample;
use App\Policies\ClientPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SamplePolicy;
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
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Sample::class, SamplePolicy::class);
    }
}
