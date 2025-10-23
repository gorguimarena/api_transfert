<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryHelper
{
    /**
     * Applique des filtres dynamiques à une requête Eloquent
     *
     * @param Builder $query
     * @param Request $request
     * @param array $filters
     * @return Builder
     */
    public static function applyFilters(Builder $query, Request $request, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'] ?? '=';
            $requestKey = $filter['request_key'] ?? $field;
            $relation = $filter['relation'] ?? null;
            $type = $filter['type'] ?? 'exact';

            if ($request->has($requestKey) && !empty($request->$requestKey)) {
                $value = $request->$requestKey;

                if ($relation) {
                    // Filtre avec relation
                    $query->whereHas($relation, function ($q) use ($field, $operator, $value, $type) {
                        self::applyFilterCondition($q, $field, $operator, $value, $type);
                    });
                } else {
                    // Filtre direct
                    self::applyFilterCondition($query, $field, $operator, $value, $type);
                }
            }
        }

        return $query;
    }

    /**
     * Applique le tri à une requête
     *
     * @param Builder $query
     * @param Request $request
     * @param array $sortMapping
     * @param string $defaultSort
     * @param string $defaultOrder
     * @return Builder
     */
    public static function applySorting(Builder $query, Request $request, array $sortMapping = [], string $defaultSort = 'created_at', string $defaultOrder = 'desc'): Builder
    {
        $sortField = $request->get('sort', $defaultSort);
        $sortOrder = $request->get('order', $defaultOrder);

        $actualSortField = $sortMapping[$sortField] ?? $sortField;

        return $query->orderBy($actualSortField, $sortOrder);
    }

    /**
     * Applique une condition de filtre
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param string $type
     */
    private static function applyFilterCondition(Builder $query, string $field, string $operator, $value, string $type): void
    {
        switch ($type) {
            case 'like':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'in':
                $query->whereIn($field, is_array($value) ? $value : [$value]);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
            case 'date':
                $query->whereDate($field, $operator, $value);
                break;
            default:
                $query->where($field, $operator, $value);
                break;
        }
    }
}