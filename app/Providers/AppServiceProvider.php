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
        $this->registerSearchMacro();
    }

    /**
     * Búsqueda de texto uniforme en cualquier motor:
     *
     *   Patient::query()->whereSearch(['first_name', 'last_name', 'dpi'], $request->q)->get();
     *
     * - PostgreSQL: sin distinguir mayúsculas NI acentos (ILIKE + unaccent).
     * - SQLite/MySQL: sin distinguir mayúsculas (LIKE).
     * Usar SIEMPRE esto para buscadores; en PostgreSQL un where('col', 'like', ...)
     * distingue mayúsculas y "maria" no encontraría "María".
     */
    private function registerSearchMacro(): void
    {
        Builder::macro('whereSearch', function (array|string $columns, ?string $term): Builder {
            /** @var Builder $this */
            $term = trim((string) $term);

            if ($term === '') {
                return $this;
            }

            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $pattern = "%{$escaped}%";
            $isPgsql = $this->getConnection()->getDriverName() === 'pgsql';

            return $this->where(function (Builder $query) use ($columns, $pattern, $isPgsql): void {
                foreach ((array) $columns as $column) {
                    $qualified = $query->getModel()->qualifyColumn($column);
                    $wrapped = $query->getQuery()->getGrammar()->wrap($qualified);

                    if ($isPgsql) {
                        $query->orWhereRaw("unaccent({$wrapped}::text) ILIKE unaccent(?)", [$pattern]);
                    } else {
                        $query->orWhereLike($qualified, $pattern);
                    }
                }
            });
        });
    }
}
