<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\FileHelper;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FileHelper as singleton
        $this->app->singleton('filehelper', function ($app) {
            return new FileHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        DB::listen(function ($query) {
            Log::info("SQL: {$query->sql}, bindings: " . json_encode($query->bindings) . ", time: {$query->time}ms");
        });
    }
}
