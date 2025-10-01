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
- 🛡️ **Validaciones robustas**: Ignora parámetros malformados sin romper la aplicación
- 🔒 **Prevención de ambigüedad**: Evita errores SQL automáticamente en consultas con JOINs
- 🧪 **Totalmente testeado**: Incluye suite completa de tests con casos edge
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

### Ejemplo de solicitud con filtros:

```json
{
    "filters": [
        { "column": "name", "operator": "like", "value": "John" },
        { "column": "age", "operator": ">=", "value": 30 }
    ]
}
```

### Ejemplo de solicitud con ordenamientos:

```json
{
    "sorts": [
        { "column": "created_at", "order": "desc" },
        { "order": "asc", "relationship": { "table": "users", "column": "email" } }
    ]
}
```

---

## 🛡️ Validaciones de Seguridad

El paquete incluye **validaciones robustas** que garantizan que la aplicación nunca se rompa, incluso con parámetros malformados:

### ✅ **Validaciones Automáticas:**

- **Parámetros principales**: Si `filters` o `sorts` no son arrays, se convierten automáticamente a arrays vacíos
- **Filtros individuales**: Cada filtro debe tener `column`, `operator` y `value` - los inválidos se ignoran silenciosamente
- **Ordenamientos individuales**: Cada sort debe tener `order` válido (`asc`/`desc`). Si tiene `relationship`, la `column` va dentro de `relationship`; si no, debe tener `column` en la raíz
- **Relaciones**: Si se especifica `relationship`, debe tener `table` y `column` - las inválidas se ignoran
- **Prevención de ambigüedad**: Todas las columnas se prefijan automáticamente con el nombre de la tabla base para evitar errores SQL

### 🔒 **Comportamiento Seguro:**

```php
// ✅ Esto funciona perfectamente - ignora elementos inválidos
$request = Request::create('/', 'GET', [
    'filters' => [
        ['column' => 'name', 'operator' => '=', 'value' => 'John'], // ✅ Válido
        ['operator' => '=', 'value' => 'test'],                      // ❌ Ignorado (falta column)
        'invalid_string',                                            // ❌ Ignorado (no es array)
    ],
    'sorts' => [
        ['column' => 'created_at', 'order' => 'desc'],              // ✅ Válido (sort simple)
        ['order' => 'asc', 'relationship' => ['table' => 'users', 'column' => 'name']], // ✅ Válido (sort con relación)
        ['column' => 'name', 'order' => 'invalid'],                 // ❌ Ignorado (order inválido)
        ['order' => 'desc'],                                         // ❌ Ignorado (falta column para sort simple)
    ]
]);

// Solo se aplican los elementos válidos - la aplicación nunca se rompe
$query = FilterService::makeFiltersAndSorts($request, $query);
```

---

## 🔒 Prevención de Ambigüedad de Columnas

### ⚠️ **Problema Resuelto:**

Cuando haces JOINs entre tablas que tienen columnas con el mismo nombre (ej: `products.name` y `categories.name`), SQL puede generar errores de "ambiguous column name".

**Ejemplo del problema:**
```sql
-- ❌ Esto genera error: "ambiguous column name: name"
SELECT * FROM products 
INNER JOIN categories ON products.category_id = categories.id 
WHERE (name LIKE '%a%' OR description LIKE '%a%')
ORDER BY categories.name ASC
```

### ✅ **Solución Automática:**

El paquete **prefija automáticamente** todas las columnas con el nombre de la tabla base para evitar ambigüedad:

```sql
-- ✅ SQL generado correctamente
SELECT * FROM products 
INNER JOIN categories ON products.category_id = categories.id 
WHERE (products.name LIKE '%a%' OR products.description LIKE '%a%')
ORDER BY categories.name ASC
```

### 🎯 **Casos de Uso Cubiertos:**

- ✅ **Filtros con JOINs**: `name|description` se convierte en `products.name|products.description`
- ✅ **Operadores básicos**: `status = 1` se convierte en `products.status = 1`
- ✅ **Filtros IN/BETWEEN**: Se prefijan automáticamente con la tabla base
- ✅ **Ordenamientos con relaciones**: Funcionan correctamente sin conflictos

### 📝 **Ejemplo Práctico:**

```php
// ✅ Esto funciona perfectamente sin errores de ambigüedad
$request = Request::create('/', 'GET', [
    'filters' => [
        ['column' => 'name|description', 'operator' => 'like', 'value' => 'laptop']
    ],
    'sorts' => [
        [
            'order' => 'asc',
            'relationship' => ['table' => 'categories', 'column' => 'name']
        ]
    ]
]);

// Genera SQL sin ambigüedad automáticamente
$query = FilterService::makeFiltersAndSorts($request, Product::query());
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

**Ejemplo de solicitud:**
```json
{
    "filters": [
        { "column": "category", "operator": "=", "value": "electronics" },
        { "column": "price", "operator": "between", "value": [100, 500] },
        { "column": "name|description", "operator": "like", "value": "laptop" }
    ],
    "sorts": [
        { "column": "price", "order": "asc" }
    ]
}
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

✅ **26 tests** | **48 assertions** | **0 errores**

#### 🧪 **Tests de Funcionalidad:**
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

#### 🛡️ **Tests de Validación de Seguridad:**
- ✅ Parámetros no-array son ignorados silenciosamente
- ✅ Filtros con claves faltantes son ignorados
- ✅ Ordenamientos con claves faltantes son ignorados
- ✅ Ordenamientos con `order` inválido son ignorados
- ✅ Relaciones con claves faltantes son ignoradas
- ✅ Elementos individuales no-array son ignorados

#### 🔒 **Tests de Prevención de Ambigüedad:**
- ✅ Filtros con JOINs evitan ambigüedad de columnas automáticamente
- ✅ Verificación de que se usan nombres de tabla prefijados
- ✅ Confirmación de que NO se generan columnas ambiguas sin prefijo

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
