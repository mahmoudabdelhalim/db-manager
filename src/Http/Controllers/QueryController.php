<?php

namespace MahmoudAbdElhalim\DbManager\Http\Controllers;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class QueryController extends Controller
{
    public function index(Request $request)
    {
        $db        = DatabaseManager::resolve();
        $databases = $db->getDatabasesFiltered();
        $database  = $request->query('db', '');
        return view('db-manager::query.index', compact('databases', 'database'));
    }

    public function run(Request $request)
    {
        $request->validate(['sql' => 'required|string|max:100000']);

        $db        = DatabaseManager::resolve();
        $database  = (string) $request->input('database', '');
        $sql       = trim($request->input('sql'));
        $databases = $db->getDatabasesFiltered();

        $isSelect  = (bool) preg_match('/^\s*SELECT\b/i', $sql);
        $hasLimit  = (bool) preg_match('/\bLIMIT\b/i', $sql);

        $page       = max(1, (int) $request->input('page', 1));
        $perPage    = 50;
        $paginated  = $isSelect && ! $hasLimit;
        $runSql     = $sql;
        $total      = null;
        $totalPages = null;
        $tableName  = null;
        $primaryKeys = [];

        if ($paginated) {
            $offset = ($page - 1) * $perPage;
            $runSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";

            try {
                $stripped   = preg_replace('/\s+ORDER\s+BY\b.+$/is', '', $sql);
                $countRow   = $db->runQuery("SELECT COUNT(*) AS __n FROM ({$stripped}) AS __c", $database);
                $total      = (int) ($countRow['rows'][0]['__n'] ?? 0);
                $totalPages = (int) ceil($total / $perPage) ?: 1;
            } catch (\Exception) {
                // show prev/next without total
            }

            if ($database && ! preg_match('/\bJOIN\b/i', $sql) && ! preg_match('/FROM\s*\(/i', $sql)) {
                if (preg_match('/\bFROM\s+`?(\w+)`?/i', $sql, $m)) {
                    $tableName = $m[1];
                    try {
                        $primaryKeys = $db->getPrimaryKeys($database, $tableName);
                    } catch (\Exception) {
                        $primaryKeys = [];
                    }
                }
            }
        }

        try {
            $result = $db->runQuery($runSql, $database);
            return view('db-manager::query.index', array_merge(
                compact('databases', 'database', 'sql', 'page', 'perPage', 'paginated', 'total', 'totalPages', 'tableName', 'primaryKeys'),
                $result
            ));
        } catch (\Exception $e) {
            return view('db-manager::query.index', compact('databases', 'database', 'sql', 'page', 'perPage', 'paginated', 'total', 'totalPages', 'tableName', 'primaryKeys'))
                ->with('queryError', $e->getMessage());
        }
    }
}
