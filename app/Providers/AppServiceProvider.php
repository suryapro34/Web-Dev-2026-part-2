<?php

namespace App\Providers;

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
    public function boot(): void{
    $product_abilities = ['insert_product', 'update_product', 'delete_product'];
    foreach ($product_abilities as $ability) {
        Gate::define($ability, function ($user) {
            return $user->roles()->where('role', 'admin')->exists();
        });
    }
}
}
