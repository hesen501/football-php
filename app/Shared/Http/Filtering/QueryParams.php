<?php

namespace App\Shared\Http\Filtering;

use Illuminate\Http\Request;

/**
 * Normalizes the shared list-endpoint query params (page/per_page/sort/search)
 * from the request. Resource-specific filters (status, venue_id, date range, ...)
 * stay in each module's List*Request, which validates them explicitly rather
 * than routing everything through a generic/magic filter object.
 */
final class QueryParams
{
    /** @param array<int, array{field: string, direction: string}> $sorts */
    private function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly array $sorts,
    ) {}

    public static function fromRequest(Request $request, int $defaultPerPage = 15, int $maxPerPage = 100): self
    {
        $perPage = (int) $request->query('per_page', $defaultPerPage);
        $perPage = max(1, min($perPage ?: $defaultPerPage, $maxPerPage));

        $sorts = [];
        foreach (explode(',', (string) $request->query('sort', '')) as $field) {
            $field = trim($field);

            if ($field === '') {
                continue;
            }

            $sorts[] = str_starts_with($field, '-')
                ? ['field' => substr($field, 1), 'direction' => 'desc']
                : ['field' => $field, 'direction' => 'asc'];
        }

        return new self(
            page: max(1, (int) $request->query('page', 1)),
            perPage: $perPage,
            search: $request->filled('search') ? (string) $request->query('search') : null,
            sorts: $sorts,
        );
    }
}
