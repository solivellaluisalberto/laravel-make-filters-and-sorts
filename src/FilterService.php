<?php

namespace SolivellaLuisAlberto\LaravelMakeFiltersAndSorts;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio para aplicar filtros y ordenamientos dinámicos a consultas Laravel.
 * 
 * Esta clase permite transformar parámetros HTTP en consultas Eloquent
 * con filtros avanzados y ordenamientos flexibles.
 * 
 * @package SolivellaLuisAlberto\LaravelMakeFiltersAndSorts
 * @author Luis Alberto Murcia Solivella
 */
class FilterService
{
    /**
     * Aplica filtros y ordenamientos a una consulta Laravel.
     * 
     * Este método procesa los parámetros 'filters' y 'sorts' de una solicitud HTTP
     * y los aplica a la consulta proporcionada de forma dinámica.
     * 
     * Operadores de filtrado soportados:
     * - Comparación: =, !=, >, <, >=, <=
     * - Búsqueda: like (soporta múltiples columnas separadas por |)
     * - Arreglos: in
     * - Rangos: between
     * 
     * Ordenamiento:
     * - Simple: por columna directa
     * - Con relaciones: mediante JOIN automático
     * 
     * Validaciones de seguridad:
     * - Ignora parámetros malformados sin romper la aplicación
     * - Valida que cada filtro tenga column, operator y value
     * - Valida que cada sort tenga order válido (column va en raíz o en relationship)
     * - Valida relaciones con table y column requeridas
     * 
     * @param Request $request La solicitud HTTP con los parámetros filters y sorts
     * @param Builder|\Illuminate\Database\Query\Builder $query La consulta a modificar (Eloquent o Query Builder)
     * @return Builder|\Illuminate\Database\Query\Builder La consulta modificada con filtros y ordenamientos
     * 
     * @example
     * ```php
     * $query = User::query();
     * $query = FilterService::makeFiltersAndSorts($request, $query);
     * $users = $query->get();
     * ```
     */
    public static function makeFiltersAndSorts(Request $request, Builder|\Illuminate\Database\Query\Builder $query): Builder|\Illuminate\Database\Query\Builder
    {
        // Obtener los parámetros de ordenamiento y filtros del request
        $sorts = $request->input('sorts', []);
        $filters = $request->input('filters', []);

        // Asegurar que sean arrays (Laravel puede devolver strings en casos edge)
        $sorts = is_array($sorts) ? $sorts : [];
        $filters = is_array($filters) ? $filters : [];

        // Procesar todos los filtros
        foreach ($filters as $filter) {
            // Validar que el filtro sea un array y tenga las claves necesarias
            if (!is_array($filter) || !isset($filter['column']) || !isset($filter['operator']) || !isset($filter['value'])) {
                continue; // Saltar filtros inválidos
            }

            $column = $filter['column'];
            $operator = $filter['operator'];
            $value = $filter['value'];

            // Aplicar el filtro según el operador
            if (in_array($operator, ['=', '!=', '>', '<', '>=', '<='])) {
                // Operadores de comparación estándar
                $query->where($column, $operator, $value);
                
            } elseif ($operator === 'like') {
                // Operador LIKE: permite búsqueda en múltiples columnas separadas por |
                // Ejemplo: 'name|email' buscará en ambas columnas
                $columns = explode('|', $column);
                $query->where(function($query) use ($value, $columns) {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            // Primera columna: WHERE
                            $query->where($column, 'like', '%' . $value . '%');
                        } else {
                            // Columnas adicionales: OR WHERE
                            $query->orWhere($column, 'like', '%' . $value . '%');
                        }
                    }
                });
                
            } elseif ($operator === 'in') {
                // Operador IN: verifica si el valor está en un array
                // Ejemplo: status IN (1, 2, 3)
                $query->whereIn($column, $value);
                
            } elseif ($operator === 'between') {
                // Operador BETWEEN: verifica si el valor está en un rango
                // Ejemplo: price BETWEEN 100 AND 500
                $query->whereBetween($column, $value);
            }
        }

        // Procesar todos los ordenamientos
        foreach ($sorts as $sort) {
            // Validar que el sort sea un array y tenga order
            if (!is_array($sort) || !isset($sort['order'])) {
                continue; // Saltar ordenamientos inválidos
            }

            $order = $sort['order'];
            $relationship = $sort['relationship'] ?? null;

            // Validar que el order sea válido
            if (!in_array(strtolower($order), ['asc', 'desc'])) {
                continue; // Saltar ordenamientos con order inválido
            }

            if ($relationship) {
                // Validar que la relación tenga las claves necesarias
                if (!is_array($relationship) || !isset($relationship['table']) || !isset($relationship['column'])) {
                    continue; // Saltar relaciones inválidas
                }

                // Ordenamiento con relación: requiere un JOIN
                
                // Obtener dinámicamente el nombre de la tabla base
                // Esto permite que el servicio funcione con cualquier modelo
                $tableName = $query instanceof Builder 
                    ? $query->getModel()->getTable()  // Eloquent Builder: obtener desde el modelo
                    : $query->from;                    // Query Builder: obtener desde la propiedad from
                
                // Realizar el JOIN y aplicar el ordenamiento
                // Ejemplo: JOIN users ON reservations.user_id = users.id ORDER BY users.name
                $query->join($relationship['table'], $tableName . '.' . \Illuminate\Support\Str::singular($relationship['table']) . '_id', '=', $relationship['table'] . '.id')
                      ->orderBy($relationship['table'] . '.' . $relationship['column'], $order);
            } else {
                // Ordenamiento simple por columna - debe tener column en la raíz
                if (!isset($sort['column'])) {
                    continue; // Saltar si no tiene column
                }
                $query->orderBy($sort['column'], $order);
            }
        }

        return $query;
    }
}
