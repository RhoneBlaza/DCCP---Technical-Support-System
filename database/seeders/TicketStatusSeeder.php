<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * [key, name, color, sort_order, type, pauses_sla]
     *
     * Colors are picked so that no two badges an agent sees side by side share
     * a color family. "pending_external" is purple rather than orange because
     * orange is already the "High" priority badge, and yellow/orange are too
     * close to tell apart at a glance.
     */
    protected const STATUSES = [
        ['open', 'Open', 'blue', 1, 'open', false],
        ['assigned', 'Assigned', 'indigo', 2, 'open', false],
        ['in_progress', 'In Progress', 'teal', 3, 'open', false],
        ['pending_user', 'Pending User', 'yellow', 4, 'pending', true],
        ['pending_external', 'Pending External', 'purple', 5, 'pending', true],
        ['resolved', 'Resolved', 'green', 6, 'resolved', false],
        ['closed', 'Closed', 'gray', 7, 'closed', false],
        ['reopened', 'Reopened', 'red', 8, 'open', false],
    ];

    public function run(): void
    {
        foreach (self::STATUSES as [$key, $name, $color, $sortOrder, $type, $pausesSla]) {
            TicketStatus::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'color' => $color,
                    'sort_order' => $sortOrder,
                    'type' => $type,
                    'pauses_sla' => $pausesSla,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
