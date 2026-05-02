<?php

namespace MahmoudAbdElhalim\DbManager\Http\Controllers;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TableController extends Controller
{
    public function index(string $database)
    {
        $db     = DatabaseManager::resolve();
        $tables = $db->getTables($database);
        return view('db-manager::tables.index', compact('database', 'tables'));
    }

    public function create(string $database)
    {
        $db       = DatabaseManager::resolve();
        $charsets = $db->getCharsets();
        return view('db-manager::tables.create', compact('database', 'charsets'));
    }

    public function store(Request $request, string $database)
    {
        $allowedEngines    = ['InnoDB', 'MyISAM', 'MEMORY', 'CSV', 'ARCHIVE'];
        $allowedCharsets   = ['utf8mb4', 'utf8', 'latin1', 'ascii', 'binary', 'utf16', 'utf32', 'armscii8', 'cp1250', 'cp1251', 'cp1256', 'cp1257', 'hebrew', 'greek'];
        $allowedCollations = ['utf8mb4_unicode_ci', 'utf8mb4_general_ci', 'utf8mb4_bin', 'utf8mb4_unicode_520_ci', 'utf8_general_ci', 'utf8_unicode_ci', 'utf8_bin', 'latin1_swedish_ci', 'latin1_general_ci', 'ascii_general_ci', 'binary'];

        $request->validate([
            'name'            => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'columns'         => 'required|array|min:1',
            'columns.*.name'  => 'required|string|regex:/^[a-zA-Z0-9_]+$/',
            'columns.*.type'  => 'required|string|max:128',
            'engine'          => 'nullable|string|in:' . implode(',', $allowedEngines),
            'charset'         => 'nullable|string|in:' . implode(',', $allowedCharsets),
            'collation'       => 'nullable|string|in:' . implode(',', $allowedCollations),
        ]);

        $db  = DatabaseManager::resolve();
        $pdo = $db->getPdo();

        $colDefs     = [];
        $primaryKeys = [];

        foreach ($request->columns as $col) {
            $colName = '`' . str_replace('`', '``', $col['name']) . '`';
            $def = $colName . ' ' . $col['type'];
            if (! empty($col['length']))         $def .= "({$col['length']})";
            if (! empty($col['unsigned']))        $def .= ' UNSIGNED';
            if (! empty($col['notnull']))         $def .= ' NOT NULL';
            if (! empty($col['auto_increment']))  $def .= ' AUTO_INCREMENT';
            if (isset($col['default']) && $col['default'] !== '') $def .= ' DEFAULT ' . $pdo->quote($col['default']);
            if (! empty($col['comment']))         $def .= ' COMMENT ' . $pdo->quote($col['comment']);
            $colDefs[] = $def;
            if (! empty($col['primary'])) $primaryKeys[] = $colName;
        }

        if ($primaryKeys) {
            $colDefs[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
        }

        $engine    = $request->input('engine', 'InnoDB');
        $charset   = $request->input('charset', 'utf8mb4');
        $collation = $request->input('collation', 'utf8mb4_unicode_ci');
        $tableName = '`' . str_replace('`', '``', $database) . '`.`' . str_replace('`', '``', $request->name) . '`';
        $colsSql   = implode(",\n  ", $colDefs);
        $sql       = "CREATE TABLE {$tableName} (\n  {$colsSql}\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation}";

        try {
            $db->getPdo()->exec($sql);
            return redirect()->route('db-manager.tables.structure', [$database, $request->name])
                ->with('success', "Table '{$request->name}' created.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function structure(string $database, string $table)
    {
        $db          = DatabaseManager::resolve();
        $columns     = $db->getTableColumns($database, $table);
        $indexes     = $db->getTableIndexes($database, $table);
        $foreignKeys = $db->getForeignKeys($database, $table);
        $create      = $db->getTableCreateSql($database, $table);
        return view('db-manager::tables.structure', compact('database', 'table', 'columns', 'indexes', 'foreignKeys', 'create'));
    }

    public function browse(Request $request, string $database, string $table)
    {
        $db          = DatabaseManager::resolve();
        $columns     = $db->getTableColumns($database, $table);
        $columnNames = array_column($columns, 'Field');
        $page        = max(1, (int) $request->input('page', 1));
        $orderByRaw  = $request->input('order_by', '');
        $orderBy     = in_array($orderByRaw, $columnNames, true) ? $orderByRaw : '';
        $orderDir    = strtoupper($request->input('order_dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $result      = $db->browseTable($database, $table, $page, 25, $orderBy, $orderDir);
        return view('db-manager::tables.browse', compact('database', 'table', 'columns', 'orderBy', 'orderDir') + $result);
    }

    public function destroy(string $database, string $table)
    {
        $db = DatabaseManager::resolve();
        try {
            $db->dropTable($database, $table);
            return redirect()->route('db-manager.tables.index', $database)
                ->with('success', "Table '{$table}' dropped.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function truncate(string $database, string $table)
    {
        $db = DatabaseManager::resolve();
        try {
            $db->truncateTable($database, $table);
            return redirect()->route('db-manager.tables.browse', [$database, $table])
                ->with('success', "Table '{$table}' truncated.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addColumn(Request $request, string $database, string $table)
    {
        $request->validate(['name' => 'required|string|max:64', 'type' => 'required|string|max:255']);
        $db = DatabaseManager::resolve();
        try {
            $db->addColumn($database, $table, [
                'name'           => $request->input('name'),
                'type'           => $request->input('type'),
                'unsigned'       => $request->boolean('unsigned'),
                'notnull'        => $request->boolean('notnull'),
                'auto_increment' => $request->boolean('auto_increment'),
                'default'        => $request->input('default', ''),
                'comment'        => $request->input('comment', ''),
            ], $request->input('after', ''));
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Column '{$request->input('name')}' added.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function modifyColumn(Request $request, string $database, string $table)
    {
        $request->validate(['old_name' => 'required|string', 'name' => 'required|string|max:64', 'type' => 'required|string|max:255']);
        $db = DatabaseManager::resolve();
        try {
            $db->modifyColumn($database, $table, $request->input('old_name'), [
                'name'           => $request->input('name'),
                'type'           => $request->input('type'),
                'unsigned'       => $request->boolean('unsigned'),
                'notnull'        => $request->boolean('notnull'),
                'auto_increment' => $request->boolean('auto_increment'),
                'default'        => $request->input('default', ''),
                'comment'        => $request->input('comment', ''),
            ]);
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Column '{$request->input('name')}' modified.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dropColumn(Request $request, string $database, string $table)
    {
        $request->validate(['column_name' => 'required|string']);
        $db = DatabaseManager::resolve();
        try {
            $db->dropColumn($database, $table, $request->input('column_name'));
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Column '{$request->input('column_name')}' dropped.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addIndex(Request $request, string $database, string $table)
    {
        $request->validate(['index_name' => 'required|string|max:64', 'index_columns' => 'required|array|min:1']);
        $db = DatabaseManager::resolve();
        try {
            $db->addIndex($database, $table, $request->input('index_name'), $request->input('index_columns'), $request->input('index_type', ''));
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Index '{$request->input('index_name')}' added.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dropIndex(Request $request, string $database, string $table)
    {
        $request->validate(['index_name' => 'required|string']);
        $db = DatabaseManager::resolve();
        try {
            $db->dropIndex($database, $table, $request->input('index_name'));
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Index '{$request->input('index_name')}' dropped.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addForeignKey(Request $request, string $database, string $table)
    {
        $request->validate([
            'name'       => 'required|string|max:64',
            'column'     => 'required|string',
            'ref_table'  => 'required|string',
            'ref_column' => 'required|string',
        ]);
        $db = DatabaseManager::resolve();
        try {
            $db->addForeignKey($database, $table, [
                'name'         => $request->input('name'),
                'column'       => $request->input('column'),
                'ref_database' => $request->input('ref_database', $database),
                'ref_table'    => $request->input('ref_table'),
                'ref_column'   => $request->input('ref_column'),
                'on_delete'    => $request->input('on_delete', 'RESTRICT'),
                'on_update'    => $request->input('on_update', 'RESTRICT'),
            ]);
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Foreign key '{$request->input('name')}' added.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dropForeignKey(Request $request, string $database, string $table)
    {
        $request->validate(['fk_name' => 'required|string']);
        $db = DatabaseManager::resolve();
        try {
            $db->dropForeignKey($database, $table, $request->input('fk_name'));
            return redirect()->route('db-manager.tables.structure', [$database, $table])
                ->with('success', "Foreign key '{$request->input('fk_name')}' dropped.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
