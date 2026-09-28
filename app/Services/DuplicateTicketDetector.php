<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * Finds an open ticket that looks like the one a requester is trying to file
 * again, so the create form can warn them instead of quietly creating a twin.
 *
 * The candidate set is narrowed in SQL (same requester, same category, still
 * unresolved, inside the configured window) and the subject comparison happens
 * in PHP, because the normalisation used here is not portable across the
 * database drivers this app supports.
 */
class DuplicateTicketDetector
{
    public function isEnabled(): bool
    {
        return (bool) config('tsts.duplicate_detection.enabled', true);
    }

    /**
     * The window a near-identical ticket counts as a duplicate, in hours.
     */
    public function windowHours(): int
    {
        return max(1, (int) config('tsts.duplicate_detection.window_hours', 24));
    }

    /**
     * The most recent unresolved ticket from this requester in this category
     * with an equivalent subject, or null when the request looks new.
     */
    public function findDuplicate(int $requesterId, int $categoryId, string $subject, ?int $exceptTicketId = null): ?Ticket
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $target = $this->normalize($subject);

        if ($target === '') {
            return null;
        }

        return $this->candidates($requesterId, $categoryId, $exceptTicketId)
            ->first(fn (Ticket $candidate) => $this->normalize($candidate->subject) === $target);
    }

    /**
     * Every unresolved ticket from this requester in this category inside the
     * window, newest first.
     *
     * @return Collection<int, Ticket>
     */
    public function candidates(int $requesterId, int $categoryId, ?int $exceptTicketId = null): Collection
    {
        return Ticket::query()
            ->unresolved()
            ->where('requester_id', $requesterId)
            ->where('category_id', $categoryId)
            ->where('created_at', '>=', now()->subHours($this->windowHours()))
            ->when($exceptTicketId !== null, fn ($query) => $query->where('id', '!=', $exceptTicketId))
            ->latest()
            ->get();
    }

    /**
     * Reduce a subject to a comparable form: case-folded, punctuation-free and
     * whitespace-collapsed, so "Printer  jam!" and "printer jam" match.
     *
     * Punctuation and whitespace are folded in a single pass because replacing
     * them separately leaves ragged spacing behind, e.g. "floor 3 -- offline"
     * would otherwise normalize to "floor 3  offline".
     */
    public function normalize(string $subject): string
    {
        $reduced = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $subject) ?? $subject;

        return mb_strtolower(trim($reduced));
    }
}
