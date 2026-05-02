<?php

namespace MahmoudAbdElhalim\DbManager\Http\Controllers;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RowController extends Controller
{
    public function create(string $database, string $table)
    {
        $db      = DatabaseManager::resolve();
        $columns = $db->getTableColumns($database, $table);
        return view('db-manager::rows.create', compact('database', 'table', 'columns'));
    }

    public function store(Request $request, string $database, string $table)
    {
        $db      = DatabaseManager::resolve();
        $columns = $db->getTableColumns($database, $table);

        $request->validate($this->buildValidationRules($columns, isUpdate: false));

        $data = [];
        foreach ($columns as $col) {
            if (str_contains(strtolower($col['Extra']), 'auto_increment')) continue;
            $name        = $col['Field'];
            $val         = $request->input($name);
            if ($val === '' && $col['Null'] === 'YES') $val = null;
            $data[$name] = $val;
        }

        try {
            $db->insertRow($database, $table, $data);
            return redirect()->route('db-manager.tables.browse', [$database, $table])
                ->with('success', 'Row inserted successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    public function edit(Request $request, string $database, string $table)
    {
        $db          = DatabaseManager::resolve();
        $columns     = $db->getTableColumns($database, $table);
        $columnNames = array_column($columns, 'Field');
        $where       = array_intersect_key($request->query(), array_flip($columnNames));
        $row         = $db->getRow($database, $table, $where);

        if (! $row) {
            return redirect()->route('db-manager.tables.browse', [$database, $table])
                ->with('error', 'Row not found.');
        }

        return view('db-manager::rows.edit', compact('database', 'table', 'columns', 'row', 'where'));
    }

    public function update(Request $request, string $database, string $table)
    {
        $db      = DatabaseManager::resolve();
        $columns = $db->getTableColumns($database, $table);
        $where   = $request->input('_where', []);

        $request->validate($this->buildValidationRules($columns, isUpdate: true));

        $data = [];
        foreach ($columns as $col) {
            $name = $col['Field'];
            if (! $request->has($name)) continue;
            $val         = $request->input($name);
            if ($val === '' && $col['Null'] === 'YES') $val = null;
            $data[$name] = $val;
        }

        try {
            $db->updateRow($database, $table, $data, $where);
            return redirect()->route('db-manager.tables.browse', [$database, $table])
                ->with('success', 'Row updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, string $database, string $table)
    {
        $db    = DatabaseManager::resolve();
        $where = $request->input('_where', []);

        try {
            $db->deleteRow($database, $table, $where);
            return back()->with('success', 'Row deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    private function buildValidationRules(array $columns, bool $isUpdate): array
    {
        $rules = [];
        foreach ($columns as $col) {
            if (str_contains(strtolower($col['Extra']), 'auto_increment')) continue;

            $name     = $col['Field'];
            $type     = strtolower($col['Type']);
            $nullable = $col['Null'] === 'YES';
            $required = ! $nullable && $col['Default'] === null;

            $colRules = [];
            if ($isUpdate || ! $required) {
                $colRules[] = 'nullable';
            } else {
                $colRules[] = 'required';
            }

            if (preg_match('/^(int|bigint|smallint|tinyint|mediumint)/', $type)) {
                $colRules[] = 'integer';
            } elseif (preg_match('/^(decimal|float|double|numeric)/', $type)) {
                $colRules[] = 'numeric';
            } elseif (preg_match('/^(date|datetime|timestamp)/', $type)) {
                $colRules[] = 'date';
            } elseif (preg_match('/^varchar\((\d+)\)/', $type, $m)) {
                $colRules[] = 'string';
                $colRules[] = 'max:' . $m[1];
            } elseif (preg_match('/^char\((\d+)\)/', $type, $m)) {
                $colRules[] = 'string';
                $colRules[] = 'max:' . $m[1];
            }

            $rules[$name] = implode('|', $colRules);
        }
        return $rules;
    }
}
