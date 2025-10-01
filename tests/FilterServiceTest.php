<?php

namespace SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\Tests;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\FilterService;

/**
 * Modelo de prueba: Reservación
 * 
 * Modelo simple usado en tests para simular consultas
 * en la tabla 'reservations'.
 */
class Reservation extends Model
{
    protected $table = 'reservations';
    protected $guarded = [];
}

/**
 * Modelo de prueba: Producto
 * 
 * Modelo usado para verificar que el servicio funciona
 * dinámicamente con diferentes modelos y tablas.
 */
class Product extends Model
{
    protected $table = 'products';
    protected $guarded = [];
}

/**
 * Suite de tests para FilterService.
 * 
 * Verifica que todos los filtros y ordenamientos funcionan correctamente:
 * - Filtros con operadores básicos (=, !=, >, <, >=, <=)
 * - Filtro LIKE con una y múltiples columnas
 * - Filtros IN y BETWEEN
 * - Ordenamiento simple y múltiple
 * - Ordenamiento con relaciones (JOINs dinámicos)
 * - Compatibilidad con Eloquent Builder y Query Builder
 * 
 * @package SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\Tests
 */
class FilterServiceTest extends TestCase
{
    public function test_filtro_con_operador_igual()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'status', 'operator' => '=', 'value' => 1]
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where "status" = ?', $sql);
        $this->assertEquals([1], $bindings);
    }

    public function test_filtro_con_operador_mayor_que()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'price', 'operator' => '>', 'value' => 100]
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where "price" > ?', $sql);
        $this->assertEquals([100], $bindings);
    }

    public function test_filtro_like_con_una_columna()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'name', 'operator' => 'like', 'value' => 'test']
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where ("name" like ?)', $sql);
        $this->assertEquals(['%test%'], $bindings);
    }

    public function test_filtro_like_con_multiples_columnas()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'name|email', 'operator' => 'like', 'value' => 'test']
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where ("name" like ?', $sql);
        $this->assertStringContainsString('or "email" like ?)', $sql);
        $this->assertEquals(['%test%', '%test%'], $bindings);
    }

    public function test_filtro_whereIn()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'status', 'operator' => 'in', 'value' => [1, 2, 3]]
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where "status" in (?, ?, ?)', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    public function test_filtro_whereBetween()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'price', 'operator' => 'between', 'value' => [100, 500]]
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        $this->assertStringContainsString('where "price" between ? and ?', $sql);
        $this->assertEquals([100, 500], $bindings);
    }

    public function test_ordenamiento_simple()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                ['column' => 'name', 'order' => 'asc']
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        $this->assertStringContainsString('order by "name" asc', $sql);
    }

    public function test_ordenamiento_multiple()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                ['column' => 'status', 'order' => 'desc'],
                ['column' => 'name', 'order' => 'asc']
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        $this->assertStringContainsString('order by "status" desc, "name" asc', $sql);
    }

    public function test_ordenamiento_con_relacion_usa_nombre_tabla_dinamico()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                [
                    'column' => 'name',
                    'order' => 'asc',
                    'relationship' => [
                        'table' => 'users',
                        'column' => 'name'
                    ]
                ]
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Verificar que usa 'reservations' (de la tabla del modelo)
        $this->assertStringContainsString('inner join "users" on "reservations"."user_id" = "users"."id"', $sql);
        $this->assertStringContainsString('order by "users"."name" asc', $sql);
    }

    public function test_ordenamiento_con_relacion_usa_nombre_correcto_para_products()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                [
                    'column' => 'name',
                    'order' => 'desc',
                    'relationship' => [
                        'table' => 'categories',
                        'column' => 'name'
                    ]
                ]
            ]
        ]);

        // Ahora probamos con el modelo Product
        $query = Product::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Verificar que usa 'products' (de la tabla del modelo Product)
        $this->assertStringContainsString('inner join "categories" on "products"."category_id" = "categories"."id"', $sql);
        $this->assertStringContainsString('order by "categories"."name" desc', $sql);
    }

    public function test_filtros_y_ordenamientos_combinados()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'status', 'operator' => '=', 'value' => 1],
                ['column' => 'name|email', 'operator' => 'like', 'value' => 'test']
            ],
            'sorts' => [
                ['column' => 'created_at', 'order' => 'desc']
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        $this->assertStringContainsString('where "status" = ?', $sql);
        $this->assertStringContainsString('"name" like ?', $sql);
        $this->assertStringContainsString('or "email" like ?', $sql);
        $this->assertStringContainsString('order by "created_at" desc', $sql);
    }

    public function test_funciona_con_query_builder_sin_modelo()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'name', 'operator' => '=', 'value' => 'test']
            ]
        ]);

        // Query Builder sin modelo (usando DB::table)
        $query = DB::table('reservations');
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        $this->assertStringContainsString('where "name" = ?', $sql);
    }

    public function test_sin_filtros_ni_ordenamientos_devuelve_query_sin_cambios()
    {
        $request = Request::create('/', 'GET', []);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe tener el SELECT básico sin WHERE ni ORDER BY
        $this->assertStringNotContainsString('where', $sql);
        $this->assertStringNotContainsString('order by', $sql);
    }

    // Tests para validaciones de seguridad

    public function test_filtros_no_array_son_ignorados()
    {
        $request = Request::create('/', 'GET', [
            'filters' => 'invalid_string' // No es un array
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // No debe tener WHERE porque los filtros fueron ignorados
        $this->assertStringNotContainsString('where', $sql);
    }

    public function test_sorts_no_array_son_ignorados()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => 'invalid_string' // No es un array
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // No debe tener ORDER BY porque los sorts fueron ignorados
        $this->assertStringNotContainsString('order by', $sql);
    }

    public function test_filtro_sin_column_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['operator' => '=', 'value' => 1], // Falta 'column'
                ['column' => 'status', 'operator' => '=', 'value' => 1] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        // Solo debe aplicar el filtro válido
        $this->assertStringContainsString('where "status" = ?', $sql);
        $this->assertEquals([1], $bindings);
    }

    public function test_filtro_sin_operator_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'status', 'value' => 1], // Falta 'operator'
                ['column' => 'name', 'operator' => '=', 'value' => 'test'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        // Solo debe aplicar el filtro válido
        $this->assertStringContainsString('where "name" = ?', $sql);
        $this->assertEquals(['test'], $bindings);
    }

    public function test_filtro_sin_value_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                ['column' => 'status', 'operator' => '='], // Falta 'value'
                ['column' => 'name', 'operator' => '=', 'value' => 'test'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        // Solo debe aplicar el filtro válido
        $this->assertStringContainsString('where "name" = ?', $sql);
        $this->assertEquals(['test'], $bindings);
    }

    public function test_sort_sin_column_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                ['order' => 'asc'], // Falta 'column'
                ['column' => 'name', 'order' => 'desc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido
        $this->assertStringContainsString('order by "name" desc', $sql);
    }

    public function test_sort_sin_order_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                ['column' => 'status'], // Falta 'order'
                ['column' => 'name', 'order' => 'asc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido
        $this->assertStringContainsString('order by "name" asc', $sql);
    }

    public function test_sort_con_order_invalido_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                ['column' => 'status', 'order' => 'invalid'], // Order inválido
                ['column' => 'name', 'order' => 'asc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido
        $this->assertStringContainsString('order by "name" asc', $sql);
    }

    public function test_relationship_sin_table_es_ignorada()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                [
                    'column' => 'name',
                    'order' => 'asc',
                    'relationship' => [
                        'column' => 'name' // Falta 'table'
                    ]
                ],
                ['column' => 'status', 'order' => 'desc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido (sin JOIN)
        $this->assertStringContainsString('order by "status" desc', $sql);
        $this->assertStringNotContainsString('inner join', $sql);
    }

    public function test_relationship_sin_column_es_ignorada()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                [
                    'column' => 'name',
                    'order' => 'asc',
                    'relationship' => [
                        'table' => 'users' // Falta 'column'
                    ]
                ],
                ['column' => 'status', 'order' => 'desc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido (sin JOIN)
        $this->assertStringContainsString('order by "status" desc', $sql);
        $this->assertStringNotContainsString('inner join', $sql);
    }

    public function test_filtro_no_array_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'filters' => [
                'invalid_string', // No es un array
                ['column' => 'status', 'operator' => '=', 'value' => 1] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();
        $bindings = $result->getBindings();

        // Solo debe aplicar el filtro válido
        $this->assertStringContainsString('where "status" = ?', $sql);
        $this->assertEquals([1], $bindings);
    }

    public function test_sort_no_array_es_ignorado()
    {
        $request = Request::create('/', 'GET', [
            'sorts' => [
                'invalid_string', // No es un array
                ['column' => 'name', 'order' => 'asc'] // Este sí es válido
            ]
        ]);

        $query = Reservation::query();
        $result = FilterService::makeFiltersAndSorts($request, $query);

        $sql = $result->toSql();

        // Solo debe aplicar el sort válido
        $this->assertStringContainsString('order by "name" asc', $sql);
    }
}

