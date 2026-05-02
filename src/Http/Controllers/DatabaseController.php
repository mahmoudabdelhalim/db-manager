<?php

namespace MahmoudAbdElhalim\DbManager\Http\Controllers;

use MahmoudAbdElhalim\DbManager\Services\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DatabaseController extends Controller
{
    public function index()
    {
        $db        = DatabaseManager::resolve();
        $databases = $db->getDatabasesFiltered();
        return view('db-manager::databases.index', compact('databases'));
    }

    public function store(Request $request)
    {
        $allowedCharsets   = ['utf8mb4', 'utf8', 'latin1', 'ascii', 'binary', 'utf16', 'utf32'];
        $allowedCollations = ['utf8mb4_unicode_ci', 'utf8mb4_general_ci', 'utf8mb4_bin', 'utf8mb4_unicode_520_ci', 'utf8_general_ci', 'utf8_unicode_ci', 'utf8_bin', 'latin1_swedish_ci', 'latin1_general_ci', 'ascii_general_ci', 'binary'];

        $request->validate([
            'name'      => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
            'charset'   => 'required|string|in:' . implode(',', $allowedCharsets),
            'collation' => 'required|string|in:' . implode(',', $allowedCollations),
        ]);

        $db = DatabaseManager::resolve();
        try {
            $db->createDatabase($request->name, $request->charset, $request->collation);
            return redirect()->route('db-manager.tables.index', $request->name)
                ->with('success', "Database '{$request->name}' created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $database)
    {
        $db = DatabaseManager::resolve();
        try {
            $db->dropDatabase($database);
            return redirect()->route('db-manager.databases.index')
                ->with('success', "Database '{$database}' dropped successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(string $database)
    {
        $db  = DatabaseManager::resolve();
        $sql = $db->exportDatabase($database);

        return response($sql, 200, [
            'Content-Type'        => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$database}_" . date('Ymd_His') . ".sql\"",
        ]);
    }

    public function import(Request $request, string $database)
    {
        $file = $request->file('sql_file');

        if ($file === null) {
            return redirect()->route('db-manager.tables.index', $database)
                ->with('error', 'No file received. Please select a .sql file and try again.');
        }

        if (! $file->isValid()) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds PHP upload_max_filesize (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was sent.',
                UPLOAD_ERR_NO_TMP_DIR => 'PHP temporary upload directory is missing.',
                UPLOAD_ERR_CANT_WRITE => 'PHP could not write the upload to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
            ];
            $msg = $uploadErrors[$file->getError()] ?? 'Upload error code ' . $file->getError();
            return redirect()->route('db-manager.tables.index', $database)->with('error', $msg);
        }

        if ($file->getSize() === 0) {
            return redirect()->route('db-manager.tables.index', $database)
                ->with('error', 'The uploaded file is empty (0 bytes).');
        }

        $db  = DatabaseManager::resolve();
        $pdo = $db->getPdo();

        try {
            $pdo->exec("USE `{$database}`");
        } catch (\Exception $e) {
            return redirect()->route('db-manager.tables.index', $database)
                ->with('error', 'Cannot select database: ' . $e->getMessage());
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $executed = 0;
        $total    = 0;
        $errors   = [];

        try {
            foreach ($this->streamSqlStatements($file->getRealPath()) as $stmt) {
                if (preg_match('/^\s*(USE\b|CREATE\s+DATABASE\b)/i', $stmt)) {
                    continue;
                }
                $total++;
                try {
                    $pdo->exec($stmt);
                    $executed++;
                } catch (\Exception $e) {
                    $errors[] = substr($e->getMessage(), 0, 200);
                    if (count($errors) >= 5) break;
                }
            }
        } catch (\Throwable $e) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            return redirect()->route('db-manager.tables.index', $database)
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        if ($total === 0) {
            return redirect()->route('db-manager.tables.index', $database)
                ->with('error', 'No SQL statements found in the file.');
        }

        if ($errors) {
            $summary = "Imported {$executed} of {$total} statements with errors: " . implode(' | ', $errors);
            return redirect()->route('db-manager.tables.index', $database)->with('error', $summary);
        }

        return redirect()->route('db-manager.tables.index', $database)
            ->with('success', "Import complete — {$executed} statements executed.");
    }

    private function streamSqlStatements(string $path): \Generator
    {
        $fh = fopen($path, 'r');
        if (! $fh) return;

        $buffer     = '';
        $firstChunk = true;

        while (! feof($fh)) {
            $chunk = fread($fh, 65536);
            if ($chunk === false || $chunk === '') break;

            if ($firstChunk) {
                $chunk      = ltrim($chunk, "\xEF\xBB\xBF");
                $firstChunk = false;
            }

            $buffer .= $chunk;

            while (($pos = $this->findStatementEnd($buffer)) !== false) {
                $stmt   = trim(substr($buffer, 0, $pos));
                $buffer = ltrim(substr($buffer, $pos + 1));
                if ($stmt !== '') yield $stmt;
            }
        }

        fclose($fh);
        $stmt = trim($buffer);
        if ($stmt !== '') yield $stmt;
    }

    private function findStatementEnd(string $buffer): int|false
    {
        $len   = strlen($buffer);
        $inStr = false;
        $quote = '';

        for ($i = 0; $i < $len; $i++) {
            $ch   = $buffer[$i];
            $next = $buffer[$i + 1] ?? '';

            if ($inStr) {
                if ($ch === '\\') { $i++; continue; }
                if ($ch === $quote) $inStr = false;
                continue;
            }

            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inStr = true;
                $quote = $ch;
                continue;
            }

            if ($ch === '#' || ($ch === '-' && $next === '-')) {
                $nl = strpos($buffer, "\n", $i);
                if ($nl === false) return false;
                $i = $nl;
                continue;
            }

            if ($ch === '/' && $next === '*') {
                $end = strpos($buffer, '*/', $i + 2);
                if ($end === false) return false;
                $i = $end + 1;
                continue;
            }

            if ($ch === ';') return $i;
        }

        return false;
    }
}
