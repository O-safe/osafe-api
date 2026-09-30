<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setup\SetupStatus;


class SetupStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'ACTIVE',
            'INACTIVE',
            'SUSPENDED',
            'DELETED',
            'PENDING',
            'APPROVED',
            'DECLINED',
            'REJECTED',
            'CANCELLED',
            'PROCESSING',
            'COMPLETED',
            'FAILED',
            'REVERSED',
            'DISBURSED',
            'ONGOING',
            'OVERDUE',
            'CLOSED',
            'DEFAULTED',
            'LOCKED',
            'UNLOCKED',
            'PAID',
            'UNPAID',
        ];

        $insertData = [];
        $statusId = 1;

        foreach ($statuses as $status) {
            $insertData[] = [
                'status_id' => $statusId++,
                'status_name' => $status,
            ];
        }

        SetupStatus::insertOrIgnore($insertData);
    }
}
