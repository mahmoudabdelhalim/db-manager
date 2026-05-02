<?php

namespace MahmoudAbdElhalim\DbManager\Http\Controllers;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PDOException;

class ConnectionController extends Controller
{
    public function showLogin()
    {
        $mode = config('db-manager.connection_mode', 'laravel');

        // Non-custom modes auto-connect — redirect to home
        if ($mode !== 'custom') {
            return redirect()->route('db-manager.home');
        }

        if (session('db_manager_connection')) {
            return redirect()->route('db-manager.home');
        }

        return view('db-manager::connection.login');
    }

    public function doConnect(Request $request)
    {
        $request->validate([
            'host'     => 'required|string',
            'port'     => 'required|integer|min:1|max:65535',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $config = [
            'host'     => $request->host,
            'port'     => (int) $request->port,
            'username' => $request->username,
            'password' => $request->password ?? '',
            'database' => '',
        ];

        try {
            new DatabaseManager($config);
            session(['db_manager_connection' => $config]);
            return redirect()->route('db-manager.home')->with('success', 'Connected successfully.');
        } catch (PDOException) {
            return back()->withInput()->with('error', 'Connection failed. Check host, port, username and password.');
        }
    }

    public function disconnect()
    {
        session()->forget('db_manager_connection');
        return redirect()->route('db-manager.connect')->with('success', 'Disconnected successfully.');
    }

    public function home()
    {
        $db        = DatabaseManager::resolve();
        $version   = $db->getServerVersion();
        $databases = $db->getDatabasesFiltered();
        return view('db-manager::connection.home', compact('version', 'databases'));
    }
}
