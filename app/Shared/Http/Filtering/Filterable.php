<?php

namespace App\Shared\Http\Filtering;

use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the shared ?sort=/?search= conventions to a model's query.
 * Add `use Filterable;` to a model to get these local scopes.
 *
 * Resource-specific filters (e.g. ?status=, ?venue_id=) are applied by each
 * Service directly via ->when(...) — they're too varied per-resource to be
 * worth a generic abstraction.
 */
trait Filterable
{
    /**
     * @param  array<int, string>  $allowedSorts  whitelist of sortable column names
     */
    public function scopeApplySort(Builder $query, QueryParams $params, array $allowedSorts, ?string $defaultSort = null): Builder
    {
        $applied = false;

        foreach ($params->sorts as $sort) {
            if (in_array($sort['field'], $allowedSorts, true)) {
                $query->orderBy($sort['field'], $sort['direction']);
                $applied = true;
            }
        }

        if (! $applied && $defaultSort !== null) {
            $query->orderBy(
                ltrim($defaultSort, '-'),
                str_starts_with($defaultSort, '-') ? 'desc' : 'asc',
            );
        }

        return $query;
    }

    /**
     * @param  array<int, string>  $searchableColumns
     */
    public function scopeApplySearch(Builder $query, QueryParams $params, array $searchableColumns): Builder
    {
        if ($params->search === null || $searchableColumns === []) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($params, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                $inner->orWhere($column, 'ilike', "%{$params->search}%");
            }
        });
    }
}
