<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Jobs\translate;
use App\Answer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindMethod(Answer::class.'@handle', function ($job, $app) {
            return $job->handle($app->make(Answer::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
