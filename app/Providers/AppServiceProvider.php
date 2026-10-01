<?php

namespace App\Providers;

use App\Enums\ThemePreference;
use App\Models\Ticket;
use App\Observers\TicketObserver;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerLikeMacros();
        $this->registerModelObservers();
        $this->shareBrandingWithViews();
        $this->shareThemePreferenceWithViews();
    }

    /**
     * Status timestamps are maintained centrally so every transition agrees.
     */
    protected function registerModelObservers(): void
    {
        Ticket::observe(TicketObserver::class);
    }

    /**
     * Share the admin-editable branding with every view.
     *
     * A view composer (rather than View::share at boot) resolves the values at
     * render time, so a name changed earlier in the same request — or in a test
     * that updates a setting after boot — is reflected immediately. The service
     * memoises the lookup, so this does not add a query per view.
     */
    protected function shareBrandingWithViews(): void
    {
        View::composer('*', function ($view): void {
            $view->with(app(SettingsService::class)->branding());
        });
    }

    /**
     * Share the signed-in user's theme preference so the anti-flash script in
     * each layout can paint the right theme on the very first frame. Guests get
     * the default and rely purely on localStorage.
     */
    protected function shareThemePreferenceWithViews(): void
    {
        View::composer('*', function ($view): void {
            $view->with('themePreference', auth()->user()?->theme?->value ?? ThemePreference::System->value);
        });
    }

    /**
     * Register case-insensitive, wildcard-safe "contains" query macros.
     *
     * A plain `like '%term%'` treats `%` and `_` in user input as wildcards.
     * Escaping them is not enough on its own: SQLite (and other engines) only
     * honour the backslash escape when the query carries an explicit
     * `escape '\'` clause. These macros add it, so user input can never widen
     * a match with wildcard characters.
     */
    protected function registerLikeMacros(): void
    {
        $pattern = static function (string $term): string {
            return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        };

        Builder::macro('whereLike', function (string $column, string $term) use ($pattern) {
            /** @var Builder $this */
            return $this->whereRaw(
                'lower('.$this->getQuery()->getGrammar()->wrap($column).") like lower(?) escape '\\'",
                [$pattern($term)]
            );
        });

        Builder::macro('orWhereLike', function (string $column, string $term) use ($pattern) {
            /** @var Builder $this */
            return $this->orWhereRaw(
                'lower('.$this->getQuery()->getGrammar()->wrap($column).") like lower(?) escape '\\'",
                [$pattern($term)]
            );
        });
    }
}
