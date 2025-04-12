<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DatePeriod;
use App\Models\Round;
use App\Models\ParcelType;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed Date Periods
        $datePeriods = [
            ['name' => 'Period 12', 'start_date' => '2025-02-01', 'end_date' => '2025-02-28'],
            ['name' => 'Period 1', 'start_date' => '2025-03-01', 'end_date' => '2025-03-28'],
            ['name' => 'Period 2', 'start_date' => '2025-03-29', 'end_date' => '2025-05-02'],
            ['name' => 'Period 3', 'start_date' => '2025-05-03', 'end_date' => '2025-05-30'],
            ['name' => 'Period 4', 'start_date' => '2025-05-31', 'end_date' => '2025-06-27'],
            ['name' => 'Period 5', 'start_date' => '2025-06-28', 'end_date' => '2025-08-01'],
            ['name' => 'Period 6', 'start_date' => '2025-08-02', 'end_date' => '2025-08-29'],
            ['name' => 'Period 7', 'start_date' => '2025-08-30', 'end_date' => '2025-09-26'],
        ];
        foreach ($datePeriods as $period) {
            DatePeriod::create($period);
        }

        // Seed Rounds (Assuming a user exists with ID 1)
        $rounds = [
            ['user_id' => 1, 'name' => 'Round 4', 'description' => 'Wybunbury to Audlem', 'active' => true],
            ['user_id' => 1, 'name' => 'Round 14', 'description' => 'Audlem to Lightwood Green', 'active' => true],
        ];
        foreach ($rounds as $round) {
            Round::create($round);
        }

        // Seed Parcel Types (Assign to Round ID 1 for simplicity)
        $parcelTypes = [
            ['round_id' => 1, 'name' => 'Postable', 'max_weight' => 0.1, 'max_length' => 35.3, 'rate' => 0.75],
            ['round_id' => 1, 'name' => 'Small Packet', 'max_weight' => 0.5, 'max_length' => 45.0, 'rate' => 1.50],
            ['round_id' => 1, 'name' => 'Packet', 'max_weight' => 1.0, 'max_length' => 61.0, 'rate' => 2.50],
            ['round_id' => 1, 'name' => 'Parcel', 'max_weight' => 2.0, 'max_length' => 100.0, 'rate' => 4.00],
            ['round_id' => 1, 'name' => 'Heavy', 'max_weight' => 10.0, 'max_length' => 150.0, 'rate' => 8.00],
            ['round_id' => 1, 'name' => 'Heavy / Large', 'max_weight' => 20.0, 'max_length' => 200.0, 'rate' => 12.00],
            ['round_id' => 1, 'name' => 'Hanging Garment', 'max_weight' => 5.0, 'max_length' => 120.0, 'rate' => 6.00],
            ['round_id' => 1, 'name' => 'Manifested collections', 'max_weight' => 30.0, 'max_length' => 250.0, 'rate' => 15.00],
            ['round_id' => 2, 'name' => 'Postable', 'max_weight' => 0.1, 'max_length' => 35.3, 'rate' => 0.75],
            ['round_id' => 2, 'name' => 'Small Packet', 'max_weight' => 0.5, 'max_length' => 45.0, 'rate' => 1.50],
            ['round_id' => 2, 'name' => 'Packet', 'max_weight' => 1.0, 'max_length' => 61.0, 'rate' => 2.50],
            ['round_id' => 2, 'name' => 'Parcel', 'max_weight' => 2.0, 'max_length' => 100.0, 'rate' => 4.00],
            ['round_id' => 2, 'name' => 'Heavy', 'max_weight' => 10.0, 'max_length' => 150.0, 'rate' => 8.00],
            ['round_id' => 2, 'name' => 'Heavy / Large', 'max_weight' => 20.0, 'max_length' => 200.0, 'rate' => 12.00],
            ['round_id' => 2, 'name' => 'Hanging Garment', 'max_weight' => 5.0, 'max_length' => 120.0, 'rate' => 6.00],
            ['round_id' => 2, 'name' => 'Manifested collections', 'max_weight' => 30.0, 'max_length' => 250.0, 'rate' => 15.00],
        ];
        foreach ($parcelTypes as $type) {
            ParcelType::create($type);
        }
    }
}
