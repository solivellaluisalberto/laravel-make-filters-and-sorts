
# solivellaluisalberto/laravelmakefiltersandsorts - Filtros y Ordenamientos para Laravel

**solivellaluisalberto/laravelmakefiltersandsorts** es un paquete Laravel diseñado para simplificar la aplicación de filtros y ordenamientos en consultas Eloquent, basados en los parámetros de una solicitud HTTP.

## Características

- **Filtros dinámicos**: Soporta operadores como `=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `in` y `between`.
- **Búsqueda por varias columnas** usando `like` con un separador de `|`.
- **Ordenamientos flexibles** en columnas simples o relaciones Eloquent.

## Instalación

1. **Instalar a través de Composer**:

   Para instalar este paquete en tu proyecto Laravel, simplemente ejecuta:

   ```bash
   composer require tu-usuario/mipaquete
   ```

2. **Publicar el Service Provider**:

   Si utilizas Laravel 5.5 o superior, el Service Provider se registrará automáticamente gracias al autoloading de Composer.

   Si no, deberás registrar el Service Provider manualmente en el archivo `config/app.php`:

   ```php
   'providers' => [
       // Otros providers...
       SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\MakeFiltersAndSortsServiceProvider::class,
   ],
   ```

## Uso

### Aplicar filtros y ordenamientos

El paquete incluye una función estática `sanitizeFiltersAndSorts` que toma una instancia de `Illuminate\Http\Request` y una consulta Eloquent, y aplica los filtros y ordenamientos especificados en los parámetros `filters` y `sorts` de la solicitud.

#### Ejemplo de uso en un controlador

```php
use Illuminate\Http\Request;
use TuNombre\MiPaquete\FilterService;

class ExampleController extends Controller
{
    public function index(Request $request)
    {
        $query = MyModel::query();

        // Aplicar filtros y ordenamientos usando la función del paquete
        $query = FilterService::sanitizeFiltersAndSorts($request, $query);

        return $query->get();
    }
}
```

### Estructura de los Parámetros de Filtros

Los filtros deben pasarse como un array en el parámetro `filters` del request. Cada filtro debe tener los siguientes campos:

- `column`: La columna a filtrar.
- `operator`: El operador a usar para filtrar (`=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `in`, `between`).
- `value`: El valor a comparar con la columna especificada.

#### Ejemplo de solicitud con filtros:

```json
{
    "filters": [
        { "column": "name", "operator": "like", "value": "John" },
        { "column": "age", "operator": ">=", "value": 30 }
    ]
}
```

### Estructura de los Parámetros de Ordenamiento

El parámetro `sorts` debe ser un array con la siguiente estructura:

- `column`: La columna por la cual ordenar.
- `order`: El tipo de orden (`asc` o `desc`).
- `relationship` (opcional): Si quieres ordenar por una relación, puedes incluir este campo con la siguiente estructura:

    - `table`: La tabla de la relación.
    - `column`: La columna de la tabla relacionada por la cual ordenar.

#### Ejemplo de solicitud con ordenamientos:

```json
{
    "sorts": [
        { "column": "created_at", "order": "desc" },
        { "relationship": { "table": "users", "column": "email" }, "order": "asc" }
    ]
}
```

## Licencia

Este paquete está bajo la licencia MIT. Consulta el archivo LICENSE para más información.
