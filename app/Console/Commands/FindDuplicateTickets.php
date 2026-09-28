<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\DuplicateTicketDetector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Creation-time duplicate detection only helps tickets filed from now on, so the
 * pairs that already exist still need a human to decide which one to keep.
 * This command reports them; it deliberately does not merge or delete anything.
 */
#[Signature('tsts:duplicate-tickets {--window= : Override the configured detection window, in hours}')]
#[Description('List groups of tickets that look like duplicates so an agent can review and close them.')]
class FindDuplicateTickets extends Command
{
    public function handle(DuplicateTicketDetector $detector): int
    {
        if ($this->option('window') !== null) {
            config(['tsts.duplicate_detection.window_hours' => (int) $this->option('window')]);
        }

        if (! $detector->isEnabled()) {
            $this->warn('Duplicate detection is disabled, so there is no window to search. Nothing was listed.');

            return self::SUCCESS;
        }

        $groups = $this->groupDuplicates($detector);

        if ($groups->isEmpty()) {
            $this->info('No duplicate-looking tickets found in the last '.$detector->windowHours().' hour(s).');

            return self::SUCCESS;
        }

        $this->table(
            ['Requester', 'Category', 'Subject', 'Ticket', 'Created'],
            $groups->flatMap(fn (Collection $group) => $group->map(fn (Ticket $ticket) => [
                $ticket->requester?->full_name ?? ('#'.$ticket->requester_id),
                $ticket->category?->name ?? ('#'.$ticket->category_id),
                $ticket->subject,
                $ticket->ticket_number,
                $ticket->created_at?->diffForHumans(),
            ]))->all()
        );

        $this->warn(sprintf(
            'Found %d duplicate-looking group(s). Close or merge the extras by hand; this command only reports.',
            $groups->count()
        ));

        return self::SUCCESS;
    }

    /**
     * Group the tickets that share a requester, category and normalized subject.
     *
     * Only unresolved tickets are considered, matching the rule the create form
     * applies, so a group never mixes a finished ticket with a live one.
     *
     * @return Collection<int, Collection<int, Ticket>>
     */
    private function groupDuplicates(DuplicateTicketDetector $detector): Collection
    {
        $windowHours = $detector->windowHours();

        return Ticket::query()
            ->unresolved()
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->with(['requester', 'category'])
            ->orderBy('requester_id')
            ->orderBy('category_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Ticket $ticket) => implode('|', [
                $ticket->requester_id,
                $ticket->category_id,
                $detector->normalize($ticket->subject),
            ]))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->values();
    }
}
