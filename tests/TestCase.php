<?php

namespace SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clase base para todos los tests del paquete.
 * 
 * Extiende de Orchestra\Testbench para proporcionar un entorno
 * de Laravel completo para testing de paquetes.
 * 
 * @package SolivellaLuisAlberto\LaravelMakeFiltersAndSorts\Tests
 */
class TestCase extends Orchestra
{
    /**
     * Configuración inicial antes de cada test.
     * 
     * Se ejecuta automáticamente antes de cada método de test
     * para preparar el entorno de testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar la base de datos de prueba
        $this->setUpDatabase();
    }

    /**
     * Configura las tablas de base de datos necesarias para los tests.
     * 
     * Crea tablas en memoria (SQLite) para simular un entorno real
     * sin afectar ninguna base de datos real.
     *
     * @return void
     */
    protected function setUpDatabase(): void
    {
        // Tabla de reservaciones para tests de filtrado y ordenamiento
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('status');
            $table->decimal('price', 8, 2);
            $table->date('date');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        // Tabla de usuarios para tests de relaciones
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        // Tabla de productos para verificar comportamiento dinámico
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->integer('stock');
            $table->timestamps();
        });
    }

    /**
     * Configura el entorno de testing de Laravel.
     * 
     * Define la configuración de base de datos y otras opciones
     * necesarias para el entorno de testing.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Configurar SQLite en memoria para tests rápidos
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',  // Base de datos en memoria
            'prefix' => '',
        ]);
    }
}

