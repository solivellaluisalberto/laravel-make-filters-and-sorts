<?php

namespace SolivellaLuisAlberto\LaravelMakeFiltersAndSorts;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class FilterService
{
    public static function makeFiltersAndSorts(Request $request, Builder|\Illuminate\Database\Query\Builder $query): Builder|\Illuminate\Database\Query\Builder
    {
        $sorts = $request->input('sorts', []);
        $filters = $request->input('filters', []);

        foreach ($filters as $filter) {
            $column = $filter['column'];
            $operator = $filter['operator'];
            $value = $filter['value'];

            // Dependiendo del operador, construir la consulta
            if (in_array($operator, ['=', '!=', '>', '<', '>=', '<='])) {
                $query->where($column, $operator, $value);
            } elseif ($operator === 'like') {
                $columns = explode('|', $column);
                $query->where(function($query) use ($value, $columns) {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $query->where($column, 'like', '%' . $value . '%');
                        } else {
                            $query->orWhere($column, 'like', '%' . $value . '%');
                        }
                    }
                });
            } elseif ($operator === 'in') {
                $query->whereIn($column, $value);
            } elseif ($operator === 'between') {
                $query->whereBetween($column, $value);
            }
        }

        foreach ($sorts as $sort) {
            $column = $sort['column'];
            $order = $sort['order'];
            $relationship = $sort['relationship'] ?? null;

            if ($relationship) {
                $query->join($relationship['table'], 'reservations.' . \Illuminate\Support\Str::singular($relationship['table']) . '_id', '=', $relationship['table'] . '.id')
                      ->orderBy($relationship['table'] . '.' . $relationship['column'], $order);
            } else {
                $query->orderBy($column, $order);
            }
        }

        return $query;
    }
}
