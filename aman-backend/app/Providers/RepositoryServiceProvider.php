<?php

namespace App\Providers;

use App\Repositories\Contracts\CaptainRepositoryInterface;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Repositories\Eloquent\CaptainRepository;
use App\Repositories\Eloquent\CityRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\PricingRuleRepository;
use App\Repositories\Eloquent\TripRepository;
use App\Repositories\Eloquent\WalletRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * ربط كل واجهة Repository بتنفيذها Eloquent.
     * إضافة Repository جديد = سطر واحد هنا فقط (مبدأ Open/Closed).
     */
    public array $bindings = [
        CustomerRepositoryInterface::class => CustomerRepository::class,
        CaptainRepositoryInterface::class => CaptainRepository::class,
        TripRepositoryInterface::class => TripRepository::class,
        WalletRepositoryInterface::class => WalletRepository::class,
        PricingRuleRepositoryInterface::class => PricingRuleRepository::class,
        CityRepositoryInterface::class => CityRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
