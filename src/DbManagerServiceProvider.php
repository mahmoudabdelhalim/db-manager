<?php

namespace MahmoudAbdElhalim\DbManager;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class DbManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/db-manager.php',
            'db-manager'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'db-manager');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->registerViewComposers();
        $this->registerPublishes();
    }

    protected function registerViewComposers(): void
    {
        View::composer('db-manager::*', function ($view) {
            try {
                $db = DatabaseManager::resolve();
                if ($db) {
                    $view->with('sidebarDatabases', $db->getDatabasesFiltered());
                }
            } catch (\Throwable) {
                $view->with('sidebarDatabases', []);
            }

            // Pass sidebar tables when a database route segment exists
            $routeDb = request()->route('database') ?? request()->query('db', '');
            if ($routeDb) {
                $view->with('currentDatabase', $routeDb);
                try {
                    $db = DatabaseManager::resolve();
                    if ($db) {
                        $view->with('sidebarTables', $db->getTables($routeDb));
                    }
                } catch (\Throwable) {
                    $view->with('sidebarTables', []);
                }
            }
        });
    }

    protected function registerPublishes(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/db-manager.php' => config_path('db-manager.php'),
        ], 'db-manager-config');

        // Views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/db-manager'),
        ], 'db-manager-views');

        // Customisable service provider stub
        $this->publishes([
            __DIR__ . '/../stubs/DbManagerServiceProvider.stub' => app_path('Providers/DbManagerServiceProvider.php'),
        ], 'db-manager-provider');
    }
}
