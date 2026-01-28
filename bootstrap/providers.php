<?php

use Illuminate\Support\ServiceProvider;

return ServiceProvider::defaultProviders()
    ->merge([
        App\Providers\AppServiceProvider::class,
    ])
    ->toArray();
