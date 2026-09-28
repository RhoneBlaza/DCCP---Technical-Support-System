<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * [key, name, sla_hours, level, requester_selectable, description]
     */
    protected const PRIORITIES = [
        ['low', 'Low', 72, 1, true, 'Non-urgent requests that can be addressed in due time.'],
        ['normal', 'Normal', 48, 2, true, 'Standard requests that should be handled within two business days.'],
        ['high', 'High', 24, 3, true, 'Urgent requests impacting work that need attention within a day.'],
        ['urgent', 'Urgent', 4, 4, false, 'Critical issues affecting operations; handle immediately.'],
    ];

    public function run(): void
    {
        foreach (self::PRIORITIES as [$key, $name, $slaHours, $level, $selectable, $description]) {
            Priority::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $description,
                    'sla_hours' => $slaHours,
                    'level' => $level,
                    'is_requester_selectable' => $selectable,
                    'is_active' => true,
                    'is_system' => true,
                ]
            );
        }
    }
}
