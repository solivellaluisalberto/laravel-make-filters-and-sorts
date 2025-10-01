# 🔍 Laravel Make Filters And Sorts

[![PHP Version](https://img.shields.io/badge/PHP-8.0%20%7C%208.1%20%7C%208.2%20%7C%208.3-blue)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20%7C%209.x%20%7C%2010.x%20%7C%2011.x%20%7C%2012.x-red)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](tests/)

**Laravel Make Filters And Sorts** es un paquete Laravel potente y flexible que simplifica la aplicación de **filtros avanzados** y **ordenamientos dinámicos** en consultas Eloquent, basándose en parámetros de solicitudes HTTP.

Ideal para construir APIs REST con filtrado y ordenamiento complejos sin escribir código repetitivo.

---

## ✨ Características

- 🎯 **Filtros dinámicos potentes**: Soporta múltiples operadores de comparación
- 🔍 **Búsqueda multicolumna**: Busca en varias columnas simultáneamente con `like`
- 📊 **Ordenamiento flexible**: Simple o con relaciones mediante JOINs automáticos
- 🚀 **Nombre de tabla dinámico**: Funciona con cualquier modelo automáticamente
- ⚡ **Alto rendimiento**: Genera consultas SQL optimizadas
- 🧪 **Totalmente testeado**: Incluye suite completa de tests
- 📦 **Zero config**: Funciona inmediatamente después de la instalación
- 🔄 **Compatible con futuras versiones**: Diseñado para ser compatible con Laravel 8-12+

### Operadores soportados

| Tipo | Operadores | Descripción |
|------|-----------|-------------|
| **Comparación** | `=`, `!=`, `>`, `<`, `>=`, `<=` | Operadores estándar de comparación |
| **Búsqueda** | `like` | Búsqueda parcial (soporta múltiples columnas con `\|`) |
| **Arrays** | `in` | Verifica si el valor está en un array |
| **Rangos** | `between` | Verifica si el valor está en un rango |

---

## 📦 Instalación

### Requisitos

- **PHP**: 8.1, 8.2 o 8.3+
- **Laravel**: 8.x, 9.x, 10.x, 11.x, 12.x

> 💡 **Nota**: Este paquete usa características fundamentales de Laravel que son muy estables y ha sido diseñado para ser compatible con todas las versiones actuales y futuras de Laravel.

### Instalación vía Composer

```bash
composer require solivellaluisalberto/laravelmakefiltersandsorts
```

### Auto-Discovery

El paquete utiliza **auto-discovery** de Laravel, por lo que el Service Provider se registrará automáticamente.

<details>
<summary>👉 <b>Registro manual</b> (solo si no usas auto-discovery)</summary>

Si tu versión de Laravel no soporta auto-discovery, registra el Service Provider manualmente en `config/app.php`:

```php
'providers' => [
    // ...
    SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\MakeFiltersAndSortsServiceProvider::class,
],
```

</details>

---

## 🚀 Inicio Rápido

```php
use Illuminate\Http\Request;
use App\Models\User;
use SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\FilterService;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Iniciar la consulta
        $query = User::query();

        // Aplicar filtros y ordenamientos dinámicamente
        $query = FilterService::makeFiltersAndSorts($request, $query);

        // Obtener resultados paginados
        return $query->paginate(15);
    }
}
```

### Ejemplo de petición HTTP

```http
GET /api/users?filters[0][column]=status&filters[0][operator]==&filters[0][value]=active
                &filters[1][column]=age&filters[1][operator]=>= &filters[1][value]=18
                &sorts[0][column]=created_at&sorts[0][order]=desc
```

---

## 📖 Documentación Completa

### 🔍 Filtros

Los filtros se envían como un array en el parámetro `filters` de la solicitud. Cada filtro tiene tres campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `column` | string | Nombre de la columna (o columnas separadas por `\|` para LIKE) |
| `operator` | string | Operador de comparación |
| `value` | mixed | Valor a comparar (string, number, array según el operador) |

#### Ejemplos de Filtros

<details>
<summary><b>Filtros de Comparación</b></summary>

```json
{
    "filters": [
        { "column": "age", "operator": ">=", "value": 18 },
        { "column": "status", "operator": "=", "value": "active" },
        { "column": "price", "operator": "<", "value": 100 }
    ]
}
```

**SQL generado:**
```sql
WHERE age >= 18 AND status = 'active' AND price < 100
```

</details>

<details>
<summary><b>Filtro LIKE (búsqueda simple)</b></summary>

```json
{
    "filters": [
        { "column": "name", "operator": "like", "value": "John" }
    ]
}
```

**SQL generado:**
```sql
WHERE name LIKE '%John%'
```

</details>

<details>
<summary><b>Filtro LIKE (múltiples columnas)</b></summary>

```json
{
    "filters": [
        { "column": "name|email|phone", "operator": "like", "value": "search" }
    ]
}
```

**SQL generado:**
```sql
WHERE (name LIKE '%search%' OR email LIKE '%search%' OR phone LIKE '%search%')
```

</details>

<details>
<summary><b>Filtro IN (array de valores)</b></summary>

```json
{
    "filters": [
        { "column": "status", "operator": "in", "value": [1, 2, 3] }
    ]
}
```

**SQL generado:**
```sql
WHERE status IN (1, 2, 3)
```

</details>

<details>
<summary><b>Filtro BETWEEN (rango)</b></summary>

```json
{
    "filters": [
        { "column": "created_at", "operator": "between", "value": ["2024-01-01", "2024-12-31"] }
    ]
}
```

**SQL generado:**
```sql
WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31'
```

</details>

### 📊 Ordenamientos

Los ordenamientos se envían como un array en el parámetro `sorts` de la solicitud:

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `column` | string | Nombre de la columna a ordenar | ✅ |
| `order` | string | Dirección del orden: `asc` o `desc` | ✅ |
| `relationship` | object | Configuración para ordenar por relación | ❌ |

#### Ejemplos de Ordenamientos

<details>
<summary><b>Ordenamiento Simple</b></summary>

```json
{
    "sorts": [
        { "column": "created_at", "order": "desc" }
    ]
}
```

**SQL generado:**
```sql
ORDER BY created_at DESC
```

</details>

<details>
<summary><b>Ordenamiento Múltiple</b></summary>

```json
{
    "sorts": [
        { "column": "status", "order": "asc" },
        { "column": "created_at", "order": "desc" },
        { "column": "name", "order": "asc" }
    ]
}
```

**SQL generado:**
```sql
ORDER BY status ASC, created_at DESC, name ASC
```

</details>

<details>
<summary><b>Ordenamiento con Relaciones (JOIN automático)</b></summary>

```json
{
    "sorts": [
        {
            "column": "name",
            "order": "asc",
            "relationship": {
                "table": "users",
                "column": "name"
            }
        }
    ]
}
```

**SQL generado (ejemplo con modelo Post):**
```sql
INNER JOIN users ON posts.user_id = users.id
ORDER BY users.name ASC
```

> 💡 **Nota**: El nombre de la tabla base (`posts`) se detecta automáticamente del modelo.

</details>

---

## 💡 Ejemplos de Uso Real

### API REST completa con filtros y paginación

```php
use App\Models\Product;
use Illuminate\Http\Request;
use SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\FilterService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        
        // Aplicar filtros y ordenamientos
        $query = FilterService::makeFiltersAndSorts($request, $query);
        
        // Paginación
        return response()->json($query->paginate(20));
    }
}
```

**Llamada a la API:**
```bash
curl -X GET "https://api.example.com/products?\
filters[0][column]=category&filters[0][operator]==&filters[0][value]=electronics&\
filters[1][column]=price&filters[1][operator]=between&filters[1][value][]=100&filters[1][value][]=500&\
filters[2][column]=name|description&filters[2][operator]=like&filters[2][value]=laptop&\
sorts[0][column]=price&sorts[0][order]=asc"
```

### Búsqueda en panel de administración

```php
public function search(Request $request)
{
    $query = User::query();
    
    // Aplicar filtros dinámicos desde formularios
    $query = FilterService::makeFiltersAndSorts($request, $query);
    
    return view('admin.users', [
        'users' => $query->with('profile')->paginate(50)
    ]);
}
```

### Reportes con filtros complejos

```php
public function salesReport(Request $request)
{
    $query = Order::query();
    
    // Aplicar filtros de fecha, estado, cliente, etc.
    $query = FilterService::makeFiltersAndSorts($request, $query);
    
    return $query->with(['customer', 'items'])
                 ->selectRaw('DATE(created_at) as date, SUM(total) as daily_total')
                 ->groupBy('date')
                 ->get();
}
```

---

## 🧪 Testing

El paquete incluye una **suite completa de tests** para garantizar su correcto funcionamiento.

### Ejecutar los tests

```bash
# Instalar dependencias de desarrollo
composer install

# Ejecutar tests
composer test

# Tests con detalles
./vendor/bin/phpunit --testdox
```

### Cobertura de Tests

✅ **13 tests** | **26 assertions** | **0 errores**

- ✅ Filtros con operadores básicos (`=`, `!=`, `>`, `<`, `>=`, `<=`)
- ✅ Filtro `like` con una sola columna
- ✅ Filtro `like` con múltiples columnas (usando `|`)
- ✅ Filtro `in` para valores en un array
- ✅ Filtro `between` para rangos de valores
- ✅ Ordenamiento simple por una columna
- ✅ Ordenamiento múltiple por varias columnas
- ✅ **Ordenamiento con relaciones** (JOIN dinámico)
- ✅ **Nombre de tabla dinámico** (funciona con cualquier modelo)
- ✅ Compatibilidad con **Eloquent Builder** y **Query Builder**
- ✅ Combinación de filtros y ordenamientos
- ✅ Consultas sin filtros ni ordenamientos

---

## 🔄 Compatibilidad con Versiones de Laravel

### ¿Por qué es compatible con Laravel 8 hasta 12 (y versiones futuras)?

Este paquete utiliza **únicamente características fundamentales** de Laravel que han permanecido estables a lo largo de múltiples versiones:

| Característica | Descripción | Estado |
|----------------|-------------|--------|
| `Request::input()` | Obtener parámetros HTTP | ✅ Estable desde Laravel 5.x |
| Query Builder (`where`, `orderBy`, `join`) | Métodos de consulta básicos | ✅ API estable y sin cambios mayores |
| Eloquent Builder | Constructor de consultas Eloquent | ✅ Comportamiento consistente |
| Service Providers | Patrón de registro de servicios | ✅ Estándar de Laravel |
| Type checking (`instanceof`) | PHP nativo | ✅ No depende de Laravel |

### Versiones Compatibles:

| Laravel | PHP Mínimo | Estado |
|---------|-----------|--------|
| **12.x** | 8.2+ | ✅ **Compatible** (Lanzado Feb 2025) |
| **11.x** | 8.2+ | ✅ Compatible |
| **10.x** | 8.1+ | ✅ Compatible |
| **9.x** | 8.0+ | ✅ Compatible |
| **8.x** | 8.0+ | ✅ Compatible |

### Novedades de Laravel 12 (Feb 2025):

Laravel 12 introduce mejoras en:
- 🎨 Nuevos kits de inicio para React, Vue y Livewire
- 🔐 Soporte para WorkOS AuthKit
- ⚡ Mejoras de rendimiento y optimizaciones
- 🛡️ Actualizaciones de seguridad

**Tu paquete es 100% compatible** con Laravel 12 sin necesidad de cambios.

Si encuentras algún problema de compatibilidad con cualquier versión de Laravel, por favor [abre un issue](../../issues).

---

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/amazing-feature`)
3. Commit tus cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

---

## 📝 Licencia

Este paquete está bajo la **licencia MIT**. Consulta el archivo [LICENSE](LICENSE) para más información.

---

## 👨‍💻 Autor

**Luis Alberto Murcia Solivella**

- 🌐 Website: [https://fasesdesarrollo.es](https://fasesdesarrollo.es)
- 📧 Email: solivella.luisalberto@gmail.com

---

## ⭐ ¿Te ha sido útil?

Si este paquete te ha ayudado en tu proyecto, considera darle una ⭐ en GitHub. ¡Gracias!
