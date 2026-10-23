<?php

namespace App\Providers;

use App\Repositories\Contracts\AppointmentRepositoryContract;
use App\Repositories\EloquentAppointmentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AppointmentRepositoryContract::class, EloquentAppointmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
