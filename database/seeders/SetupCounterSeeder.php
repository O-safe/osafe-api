<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupCounter;

class SetupCounterSeeder extends Seeder
{
    public function run(): void
    {
        $counters = [
            ['counter_id' => 'USR',  'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF USER'],
            ['counter_id' => 'STF',  'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF STAFF'],
            ['counter_id' => 'DEV',  'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF DEVICE'],
            ['counter_id' => 'FAM',  'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF FAMILY'],
            ['counter_id' => 'TKT',  'counter_value' => 0, 'counter_description' => 'COUNT NUMBER OF SUPPORT TICKET'],

            // Legacy backward-compatibility aliases
            ['counter_id' => 'MEM',  'counter_value' => 0, 'counter_description' => 'LEGACY COUNT NUMBER OF USER'],
            ['counter_id' => 'STFF', 'counter_value' => 0, 'counter_description' => 'LEGACY COUNT NUMBER OF STAFF'],
        ];

        SetupCounter::insertOrIgnore($counters);
    }
}
