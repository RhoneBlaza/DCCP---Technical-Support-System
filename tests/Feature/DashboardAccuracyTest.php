<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Covers the dashboard acceptance criteria: a legend of only real statuses, an
 * Open card that agrees with the donut, a category axis that is not pinned to a
 * stale maximum, in-progress work that always has an owner, and trend data that
 * matches the stored creation timestamps.
 */
class DashboardAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // Keep the reference data the dashboard reads, but start from an empty
        // ticket table so every count below can be asserted exactly.
        Ticket::query()->delete();

        $this->admin = User::where('email', 'admin@sample.com')->firstOrFail();
    }

    public function test_the_status_legend_lists_only_real_statuses_and_the_counts_add_up(): void
    {
        $html = $this->html();

        $this->assertStringNotContainsStringIgnoringCase('quis illum', $html);
        $this->assertStringNotContainsStringIgnoringCase('lorem', $html);

        $counts = $this->statusCounts($html);

        $this->assertSame(
            TicketStatus::active()->ordered()->pluck('name')->all(),
            array_keys($counts),
            'The donut legend should contain exactly the active statuses, in display order.'
        );

        $this->assertSame(Ticket::count(), array_sum($counts), 'The donut counts should sum to the total ticket count.');

        foreach (TicketStatus::active()->get() as $status) {
            $this->assertSame(
                Ticket::where('status_id', $status->id)->count(),
                $counts[$status->name] ?? 0,
                'The '.$status->name.' slice should count the tickets in that status.'
            );
        }
    }

    public function test_the_open_card_counts_the_same_tickets_as_the_open_donut_slice(): void
    {
        $this->fileTicket(TicketStatus::where('key', 'open')->firstOrFail());
        $this->fileTicket(TicketStatus::where('key', 'assigned')->firstOrFail());
        $this->fileTicket(TicketStatus::where('key', 'in_progress')->firstOrFail());

        $html = $this->html();
        $counts = $this->statusCounts($html);

        $this->assertSame(1, $counts['Open']);
        $this->assertSame(1, $this->statCardValue($html, 'Open'));
        $this->assertSame($counts['Open'], $this->statCardValue($html, 'Open'));
        $this->assertSame($counts['In Progress'], $this->statCardValue($html, 'In progress'));
    }

    public function test_the_category_axis_scales_with_the_data(): void
    {
        $busy = Category::where('name', 'Hardware')->firstOrFail();

        $this->fileTicket(TicketStatus::where('key', 'open')->firstOrFail(), 25, ['category_id' => $busy->id]);

        $config = $this->chartConfig($this->html(), 'categoryChart');

        $this->assertStringContainsString('"Hardware"', $config);
        $this->assertMatchesRegularExpression(
            '/data:\s*\[25\]/',
            $config,
            'The busiest category should be rendered with its real count.'
        );
        $this->assertMatchesRegularExpression(
            '/x:\s*\{[^}]*beginAtZero:\s*true/',
            $config,
            'The category axis should start at zero.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/x:\s*\{[^}]*\bmax:\s*\d/',
            $config,
            'The category axis must not pin a fixed maximum, or bars could overrun the last label.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/x:\s*\{[^}]*suggestedMax/',
            $config,
            'The category axis should be left to scale from the data.'
        );
    }

    public function test_every_in_progress_ticket_is_counted_against_its_owner(): void
    {
        $inProgress = TicketStatus::where('key', 'in_progress')->firstOrFail();
        $staff = User::factory()->support()->create();

        Ticket::factory()->withStatus($inProgress)->create(['assigned_to' => $staff->id]);

        $html = $this->html();
        $counts = $this->statusCounts($html);
        $workload = $this->workloadData($html);

        $this->assertSame(1, $counts['In Progress']);
        $this->assertSame(1, $this->statCardValue($html, 'In progress'));
        $this->assertSame(1, array_sum($workload), 'The In Progress count should show up in the workload chart.');
        $this->assertSame($staff->full_name, array_key_first($workload));
    }

    public function test_the_workload_chart_says_so_when_nothing_is_assigned(): void
    {
        $this->fileTicket(TicketStatus::where('key', 'open')->firstOrFail());

        $this->dashboard()
            ->assertOk()
            ->assertSee('No open assignments');
    }

    public function test_the_trend_series_is_built_from_stored_creation_timestamps(): void
    {
        $open = TicketStatus::where('key', 'open')->firstOrFail();

        $this->fileTicket($open);
        $this->fileTicket($open);
        $this->fileTicket($open, 1, ['created_at' => now()->subDays(3)]);
        $this->fileTicket($open, 1, ['created_at' => now()->subDays(40)]);

        $html = $this->html();

        $this->assertCount(30, $this->trendLabels($html));
        $this->assertCount(30, $this->trendCounts($html));

        $counts = $this->trendCounts($html);
        $this->assertSame(2, $counts[29], 'Both tickets created today should land in the last bucket.');
        $this->assertSame(1, $counts[26], 'The ticket created three days ago should land in its own bucket.');
        $this->assertSame(3, array_sum($counts), 'Only tickets inside the 30 day window should be counted.');

        $expected = Ticket::where('created_at', '>=', now()->subDays(29)->startOfDay())->count();
        $this->assertSame($expected, array_sum($counts));
    }

    private function dashboard(): TestResponse
    {
        return $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
    }

    private function html(): string
    {
        return $this->dashboard()->getContent();
    }

    private function fileTicket(TicketStatus $status, int $count = 1, array $attributes = []): void
    {
        Ticket::factory()->withStatus($status)->count($count)->create($attributes);
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(string $html): array
    {
        preg_match('/labels:\s*(\[.*?\]),\s*\n\s*datasets/s', $this->statusChartConfig($html), $labels);
        preg_match('/backgroundColor:\s*(\[.*?\])/s', $this->statusChartConfig($html), $colors);
        preg_match('/data:\s*(\[[\d,\s]+\])/s', $this->statusChartConfig($html), $data);

        $names = json_decode(str_replace(['"', "'"], '"', $labels[1] ?? '[]'), true) ?? [];
        $names = array_map(fn (string $label) => preg_replace('/\s*\(\d+\)$/', '', $label), $names);
        $values = json_decode($data[1] ?? '[]', true) ?? [];

        $this->assertCount(
            count($names),
            $colors[1] ?? null ? json_decode(str_replace(["'", '"'], '"', $colors[1]), true) : [],
            'Every legend label should have a matching color.'
        );

        return array_combine($names, array_map('intval', $values));
    }

    /**
     * @return array<string, int>
     */
    private function workloadData(string $html): array
    {
        $config = $this->chartConfig($html, 'workloadChart');

        preg_match('/labels:\s*(\[[^\]]*\])/s', $config, $labels);
        preg_match('/label:\s*\'Open tickets\',\s*\n\s*data:\s*(\[[\d,\s]+\])/s', $config, $data);

        $names = json_decode(str_replace("'", '"', $labels[1] ?? '[]'), true) ?? [];
        $values = array_map('intval', json_decode($data[1] ?? '[]', true) ?? []);

        return array_combine($names, $values);
    }

    /**
     * @return array<int, int>
     */
    private function trendCounts(string $html): array
    {
        preg_match('/trend30:\s*\{\s*labels:\s*(\[.*?\]),\s*data:\s*(\[[\d,\s]+\])/s', $html, $matches);

        return array_map('intval', json_decode($matches[2] ?? '[]', true) ?? []);
    }

    /**
     * @return array<int, string>
     */
    private function trendLabels(string $html): array
    {
        preg_match('/trend30:\s*\{\s*labels:\s*(\[.*?\]),\s*data/s', $html, $matches);

        return json_decode($matches[1] ?? '[]', true) ?? [];
    }

    private function statCardValue(string $html, string $label): int
    {
        $pattern = '/'.preg_quote($label, '/').'<\/p>\s*<p class="[^"]*">\s*(\d+)\s*<\/p>/';

        $this->assertSame(
            1,
            preg_match($pattern, $html, $matches),
            'The dashboard is missing a stat card labelled "'.$label.'".'
        );

        return (int) $matches[1];
    }

    private function statusChartConfig(string $html): string
    {
        return $this->chartConfig($html, 'statusChart');
    }

    private function chartConfig(string $html, string $canvasId): string
    {
        $start = strpos($html, "getElementById('{$canvasId}')");
        $this->assertNotFalse($start, 'The page should render a '.$canvasId.' chart.');

        $end = strpos($html, '});', $start);

        return substr($html, $start, $end - $start);
    }
}
