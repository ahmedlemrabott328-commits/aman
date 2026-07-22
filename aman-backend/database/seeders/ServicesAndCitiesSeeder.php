<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Service;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class ServicesAndCitiesSeeder extends Seeder
{
    public function run(): void
    {
        $ride = Service::firstOrCreate(['code' => 'ride'], [
            'name_ar' => 'نقل الركاب', 'name_fr' => 'Transport de passagers', 'name_en' => 'Ride', 'sort_order' => 1,
        ]);
        $airport = Service::firstOrCreate(['code' => 'airport'], [
            'name_ar' => 'خدمة المطار', 'name_fr' => 'Service Aéroport', 'name_en' => 'Airport', 'sort_order' => 2,
        ]);
        $delivery = Service::firstOrCreate(['code' => 'delivery'], [
            'name_ar' => 'التوصيل', 'name_fr' => 'Livraison', 'name_en' => 'Delivery', 'sort_order' => 3,
        ]);

        VehicleType::firstOrCreate(['service_id' => $ride->id, 'code' => 'economy'], [
            'name_ar' => 'اقتصادي', 'name_fr' => 'Économique', 'name_en' => 'Economy', 'capacity' => 4,
        ]);
        VehicleType::firstOrCreate(['service_id' => $ride->id, 'code' => 'comfort'], [
            'name_ar' => 'مريح', 'name_fr' => 'Confort', 'name_en' => 'Comfort', 'capacity' => 4,
        ]);

        foreach ([
            ['name_ar' => 'نواكشوط', 'name_fr' => 'Nouakchott', 'name_en' => 'Nouakchott', 'center_lat' => 18.0858, 'center_lng' => -15.9785],
            ['name_ar' => 'نواذيبو', 'name_fr' => 'Nouadhibou', 'name_en' => 'Nouadhibou', 'center_lat' => 20.9310, 'center_lng' => -17.0347],
        ] as $city) {
            City::firstOrCreate(['name_en' => $city['name_en']], $city + ['country_code' => 'MR']);
        }
    }
}
