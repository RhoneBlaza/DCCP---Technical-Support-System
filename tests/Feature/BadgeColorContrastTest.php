<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BadgeColorContrastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_the_two_pending_statuses_are_not_the_same_color(): void
    {
        $pending = TicketStatus::where('type', 'pending')->pluck('color', 'key');

        $this->assertEqualsCanonicalizing(['pending_user', 'pending_external'], $pending->keys()->all());
        $this->assertNotSame(
            $pending['pending_user'],
            $pending['pending_external'],
            'Two statuses from the same family must be told apart by color alone.'
        );
    }

    public function test_pending_statuses_do_not_reuse_a_priority_badge_color(): void
    {
        $priorityColors = Priority::query()
            ->whereIn('key', ['low', 'normal', 'high', 'urgent'])
            ->get()
            ->mapWithKeys(fn (Priority $priority): array => [
                $priority->key => $this->renderedColor('<x-priority-badge :priority="$priority" />', $priority),
            ]);

        $pendingColors = TicketStatus::where('type', 'pending')
            ->get()
            ->mapWithKeys(fn (TicketStatus $status): array => [
                $status->key => $this->renderedColor('<x-status-badge :status="$status" />', $status),
            ]);

        $this->assertSame(
            [],
            array_intersect($pendingColors->all(), $priorityColors->all()),
            'A pending status and a priority that look alike make the two columns ambiguous.'
        );
    }

    public function test_every_badge_color_is_one_the_palette_actually_defines(): void
    {
        $palette = [
            'gray', 'blue', 'indigo', 'teal', 'green',
            'yellow', 'orange', 'red', 'rose', 'purple',
        ];

        foreach (TicketStatus::query()->get() as $status) {
            $this->assertContains(
                $status->color,
                $palette,
                "The {$status->name} status uses a color the badge component does not render."
            );
        }
    }

    private function renderedColor(string $template, Priority|TicketStatus $model): string
    {
        $html = Blade::render($template, ['priority' => $model, 'status' => $model]);

        $this->assertMatchesRegularExpression(
            '/\bbg-([a-z]+)-\d{2,3}\b/',
            $html,
            'The badge should render a colored background.'
        );

        preg_match('/\bbg-([a-z]+)-\d{2,3}\b/', $html, $matches);

        return $matches[1];
    }
}
