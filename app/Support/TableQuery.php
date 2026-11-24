<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Server-side search, sort and pagination for the data tables, driven by the
 * query string (?q=&sort=-created_at&per_page=15&page=2). Only whitelisted
 * columns can be searched or sorted.
 */
final class TableQuery
{
    public const PER_PAGE = [10, 15, 25, 50];

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $searchable  columns, or "relation.column"
     * @param  list<string>  $sortable
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function paginate(
        Builder $query,
        Request $request,
        array $searchable,
        array $sortable,
        string $defaultSort = '-created_at',
    ): LengthAwarePaginator {
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');

        if ($search !== '') {
            $query->where(function (Builder $q) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $field] = explode('.', $column, 2);
                        $q->orWhereHas($relation, fn (Builder $r) => $r->where($field, 'like', "%{$search}%"));
                    } else {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        [$column, $direction] = self::sort((string) $request->query('sort', $defaultSort), $sortable, $defaultSort);
        $query->orderBy($column, $direction)->orderBy($query->getModel()->getQualifiedKeyName(), $direction);

        $perPage = (int) $request->query('per_page', 15);

        return $query
            ->paginate(in_array($perPage, self::PER_PAGE, true) ? $perPage : 15)
            ->withQueryString();
    }

    /**
     * @param  list<string>  $sortable
     * @return array{string, string}
     */
    private static function sort(string $sort, array $sortable, string $default): array
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if (! in_array($column, $sortable, true)) {
            return self::sort($default, [ltrim($default, '-')], $default);
        }

        return [$column, $direction];
    }
}
