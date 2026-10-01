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

    /**
     * Get a preview of the next ticket number without incrementing the sequence.
     *
     * Format: {PREFIX}-{YYYY}-{NNNNNN}, sequence resets each year.
     */
    public function preview(): string
    {
        $prefix = strtoupper((string) (new SettingsService)->get('ticket_prefix'));
        $year = (int) now()->year;

        $sequence = TicketSequence::find($year);
        $nextNumber = ($sequence ? $sequence->last_number + 1 : 1);

        return sprintf('%s-%d-%06d', $prefix, $year, $nextNumber);
    }
}
