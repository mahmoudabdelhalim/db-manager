<?php

use MahmoudAbdElhalim\DbManager\Http\Controllers\ConnectionController;
use MahmoudAbdElhalim\DbManager\Http\Controllers\DatabaseController;
use MahmoudAbdElhalim\DbManager\Http\Controllers\TableController;
use MahmoudAbdElhalim\DbManager\Http\Controllers\RowController;
use MahmoudAbdElhalim\DbManager\Http\Controllers\QueryController;
use MahmoudAbdElhalim\DbManager\Http\Middleware\DbManagerEnabled;
use MahmoudAbdElhalim\DbManager\Http\Middleware\DbManagerConnected;
use Illuminate\Support\Facades\Route;

Route::prefix(config('db-manager.prefix', 'db-manager'))
    ->name('db-manager.')
    ->middleware([DbManagerEnabled::class, ...config('db-manager.middleware', ['web', 'auth'])])
    ->group(function () {

        // Custom-mode login/logout (only meaningful when connection_mode = 'custom')
        Route::get('/connect',    [ConnectionController::class, 'showLogin'])->name('connect');
        Route::post('/connect',   [ConnectionController::class, 'doConnect'])->name('do-connect')->middleware('throttle:10,1');
        Route::post('/disconnect',[ConnectionController::class, 'disconnect'])->name('disconnect');

        // All routes below require an active DB connection
        Route::middleware(DbManagerConnected::class)->group(function () {

            Route::get('/', [ConnectionController::class, 'home'])->name('home');

            // Databases
            Route::get('/databases',                    [DatabaseController::class, 'index'])->name('databases.index');
            Route::post('/databases',                   [DatabaseController::class, 'store'])->name('databases.store');
            Route::delete('/databases/{database}',      [DatabaseController::class, 'destroy'])->name('databases.destroy');
            Route::get('/databases/{database}/export',  [DatabaseController::class, 'export'])->name('databases.export');
            Route::post('/databases/{database}/import', [DatabaseController::class, 'import'])->name('databases.import');

            // Tables
            Route::get('/databases/{database}/tables',                    [TableController::class, 'index'])->name('tables.index');
            Route::get('/databases/{database}/tables/create',             [TableController::class, 'create'])->name('tables.create');
            Route::post('/databases/{database}/tables',                   [TableController::class, 'store'])->name('tables.store');
            Route::get('/databases/{database}/tables/{table}/structure',  [TableController::class, 'structure'])->name('tables.structure');
            Route::get('/databases/{database}/tables/{table}/browse',     [TableController::class, 'browse'])->name('tables.browse');
            Route::delete('/databases/{database}/tables/{table}',         [TableController::class, 'destroy'])->name('tables.destroy');
            Route::post('/databases/{database}/tables/{table}/truncate',  [TableController::class, 'truncate'])->name('tables.truncate');

            // Columns
            Route::post('/databases/{database}/tables/{table}/columns',       [TableController::class, 'addColumn'])->name('columns.store');
            Route::post('/databases/{database}/tables/{table}/columns/edit',  [TableController::class, 'modifyColumn'])->name('columns.update');
            Route::post('/databases/{database}/tables/{table}/columns/drop',  [TableController::class, 'dropColumn'])->name('columns.destroy');

            // Indexes
            Route::post('/databases/{database}/tables/{table}/indexes',       [TableController::class, 'addIndex'])->name('indexes.store');
            Route::post('/databases/{database}/tables/{table}/indexes/drop',  [TableController::class, 'dropIndex'])->name('indexes.destroy');

            // Foreign keys
            Route::post('/databases/{database}/tables/{table}/fk',       [TableController::class, 'addForeignKey'])->name('fk.store');
            Route::post('/databases/{database}/tables/{table}/fk/drop',  [TableController::class, 'dropForeignKey'])->name('fk.destroy');

            // Rows
            Route::get('/databases/{database}/tables/{table}/rows/create', [RowController::class, 'create'])->name('rows.create');
            Route::post('/databases/{database}/tables/{table}/rows',       [RowController::class, 'store'])->name('rows.store');
            Route::get('/databases/{database}/tables/{table}/rows/edit',   [RowController::class, 'edit'])->name('rows.edit');
            Route::put('/databases/{database}/tables/{table}/rows',        [RowController::class, 'update'])->name('rows.update');
            Route::delete('/databases/{database}/tables/{table}/rows',     [RowController::class, 'destroy'])->name('rows.destroy');

            // SQL Editor
            Route::get('/query',  [QueryController::class, 'index'])->name('query.index');
            Route::post('/query', [QueryController::class, 'run'])->name('query.run');
        });
    });
