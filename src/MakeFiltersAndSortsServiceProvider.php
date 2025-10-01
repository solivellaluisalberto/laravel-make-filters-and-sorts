<?php

namespace SolivellaLuisAlberto\LaravelMakeFiltersAndSorts;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider para el paquete Laravel Make Filters And Sorts.
 * 
 * Este provider se registra automáticamente en Laravel 5.5+ mediante
 * el auto-discovery de Composer.
 * 
 * @package SolivellaLuisAlberto\LaravelMakeFiltersAndSorts
 * @author Luis Alberto Murcia Solivella
 */
class MakeFiltersAndSortsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap de servicios de la aplicación.
     * 
     * Este método se ejecuta después de que todos los service providers
     * han sido registrados. Aquí se pueden publicar archivos de configuración,
     * vistas, migraciones, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        // Aquí se pueden publicar assets, configuraciones, etc.
        // Ejemplo: $this->publishes([...], 'config');
    }

    /**
     * Registro de servicios de la aplicación.
     * 
     * Este método se ejecuta cuando el service provider es registrado.
     * Aquí se pueden vincular clases al contenedor de servicios.
     *
     * @return void
     */
    public function register(): void
    {
        // Aquí se pueden registrar bindings en el contenedor
        // Ejemplo: $this->app->singleton(FilterService::class);
    }
}