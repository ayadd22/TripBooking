<?php

namespace App\Providers;

use App\Contracts\BookingServiceInterface;
use App\Services\BookingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
   
    public function register(): void
    {
        
        $this->app->bind(
            BookingServiceInterface::class,
            BookingService::class,
        );
    }

    
    public function boot(): void
    {
        //
    }
}
