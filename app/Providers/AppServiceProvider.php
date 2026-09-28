<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
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
