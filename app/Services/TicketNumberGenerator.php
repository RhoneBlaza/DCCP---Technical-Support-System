<?php

namespace App\Services;

use App\Models\TicketSequence;
use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    /**
     * Generate the next ticket number, guaranteed unique under concurrency.
     *
     * Format: {PREFIX}-{YYYY}-{NNNNNN}, sequence resets each year.
     */
    public function next(): string
    {
        $prefix = strtoupper((string) (new SettingsService)->get('ticket_prefix'));
        $year = (int) now()->year;

        return DB::transaction(function () use ($year, $prefix) {
            $sequence = TicketSequence::lockForUpdate()->find($year);

            if ($sequence === null) {
                $sequence = TicketSequence::create(['year' => $year, 'last_number' => 0]);
            }

            $number = $sequence->last_number + 1;
            $sequence->update(['last_number' => $number]);

            return sprintf('%s-%d-%06d', $prefix, $year, $number);
        });
    }
}
