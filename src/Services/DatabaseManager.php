<?php

namespace MahmoudAbdElhalim\DbManager\Services;

use PDO;
use PDOException;

class DatabaseManager
{
    private PDO $pdo;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        if (! empty($config['database'])) {
            $dsn .= ";dbname={$config['database']}";
        }
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
    }

    // ── Factories ─────────────────────────────────────────────────────────────

    /**
     * Resolve a DatabaseManager instance based on the configured connection_mode.
     */
    public static function resolve(): ?static
    {
        $mode = config('db-manager.connection_mode', 'laravel');

        return match ($mode) {
            'laravel', 'all' => static::fromLaravel(config('db-manager.connection')),
            'custom'          => static::fromSession(),
            default           => static::fromLaravel(),
        };
    }

    /**
     * Create an instance from the Laravel database configuration.
     */
    public static function fromLaravel(?string $connectionName = null): static
    {
        $name   = $connectionName ?? config('database.default');
        $cfg    = config("database.connections.{$name}", []);

        return new static([
            'host'     => $cfg['host']     ?? '127.0.0.1',
            'port'     => $cfg['port']     ?? 3306,
            'username' => $cfg['username'] ?? 'root',
            'password' => $cfg['password'] ?? '',
            'database' => $cfg['database'] ?? '',
        ]);
    }

    /**
     * Create an instance from credentials stored in the session (custom mode).
     */
    public static function fromSession(): ?static
    {
        $config = session('db_manager_connection');
        if (! $config) {
            return null;
        }
        try {
            return new static($config);
        } catch (PDOException) {
            return null;
        }
    }

    // ── Database listing ──────────────────────────────────────────────────────

    /**
     * Return databases filtered according to connection_mode:
     *   - 'laravel' → only the app's own database
     *   - 'all'/'custom' → all databases the MySQL user can see
     */
    public function getDatabasesFiltered(): array
    {
        $all  = $this->getDatabases();
        $mode = config('db-manager.connection_mode', 'laravel');

        if ($mode === 'laravel') {
            $own = $this->config['database'] ?? '';
            return $own ? array_values(array_filter($all, fn($db) => $db === $own)) : $all;
        }

        return $all;
    }

    // ── Core operations ───────────────────────────────────────────────────────

    public function getConfig(): array  { return $this->config; }
    public function getPdo(): PDO       { return $this->pdo; }

    public function getServerVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getDatabases(): array
    {
        $stmt = $this->pdo->query("SHOW DATABASES");
        return array_column($stmt->fetchAll(), 'Database');
    }

    public function createDatabase(string $name, string $charset = 'utf8mb4', string $collation = 'utf8mb4_unicode_ci'): void
    {
        $name = $this->quoteIdentifier($name);
        $this->pdo->exec("CREATE DATABASE {$name} CHARACTER SET {$charset} COLLATE {$collation}");
    }

    public function dropDatabase(string $name): void
    {
        $name = $this->quoteIdentifier($name);
        $this->pdo->exec("DROP DATABASE {$name}");
    }

    public function getTables(string $database): array
    {
        $db   = $this->quoteIdentifier($database);
        $stmt = $this->pdo->query("SHOW TABLE STATUS FROM {$db}");
        return $stmt->fetchAll();
    }

    public function getTableColumns(string $database, string $table): array
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $stmt = $this->pdo->query("SHOW FULL COLUMNS FROM {$tbl} IN {$db}");
        return $stmt->fetchAll();
    }

    public function getTableIndexes(string $database, string $table): array
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $stmt = $this->pdo->query("SHOW INDEX FROM {$tbl} IN {$db}");
        return $stmt->fetchAll();
    }

    public function getTableCreateSql(string $database, string $table): string
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $stmt = $this->pdo->query("SHOW CREATE TABLE {$db}.{$tbl}");
        $row  = $stmt->fetch();
        return $row['Create Table'] ?? '';
    }

    public function browseTable(string $database, string $table, int $page = 1, int $perPage = 25, string $orderBy = '', string $orderDir = 'ASC'): array
    {
        $db     = $this->quoteIdentifier($database);
        $tbl    = $this->quoteIdentifier($table);
        $offset = ($page - 1) * $perPage;

        $order = '';
        if ($orderBy) {
            $dir   = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $order = " ORDER BY " . $this->quoteIdentifier($orderBy) . " {$dir}";
        }

        $total = (int) $this->pdo->query("SELECT COUNT(*) AS cnt FROM {$db}.{$tbl}")->fetch()['cnt'];
        $rows  = $this->pdo->query("SELECT * FROM {$db}.{$tbl}{$order} LIMIT {$perPage} OFFSET {$offset}")->fetchAll();

        return compact('rows', 'total', 'page', 'perPage');
    }

    public function insertRow(string $database, string $table, array $data): void
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $cols = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($data)));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO {$db}.{$tbl} ({$cols}) VALUES ({$phs})");
        $stmt->execute(array_values($data));
    }

    public function updateRow(string $database, string $table, array $data, array $where): void
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $set  = implode(', ', array_map(fn($k) => $this->quoteIdentifier($k) . ' = ?', array_keys($data)));
        $cond = implode(' AND ', array_map(fn($k) => $this->quoteIdentifier($k) . ' = ?', array_keys($where)));
        $stmt = $this->pdo->prepare("UPDATE {$db}.{$tbl} SET {$set} WHERE {$cond} LIMIT 1");
        $stmt->execute([...array_values($data), ...array_values($where)]);
    }

    public function deleteRow(string $database, string $table, array $where): void
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $cond = implode(' AND ', array_map(fn($k) => $this->quoteIdentifier($k) . ' = ?', array_keys($where)));
        $stmt = $this->pdo->prepare("DELETE FROM {$db}.{$tbl} WHERE {$cond} LIMIT 1");
        $stmt->execute(array_values($where));
    }

    public function getRow(string $database, string $table, array $where): ?array
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $cond = implode(' AND ', array_map(fn($k) => $this->quoteIdentifier($k) . ' = ?', array_keys($where)));
        $stmt = $this->pdo->prepare("SELECT * FROM {$db}.{$tbl} WHERE {$cond} LIMIT 1");
        $stmt->execute(array_values($where));
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function dropTable(string $database, string $table): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        $this->pdo->exec("DROP TABLE {$db}.{$tbl}");
    }

    public function truncateTable(string $database, string $table): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        $this->pdo->exec("TRUNCATE TABLE {$db}.{$tbl}");
    }

    public function runQuery(string $sql, ?string $database = ''): array
    {
        if ($database !== null && $database !== '') {
            $this->pdo->exec("USE " . $this->quoteIdentifier($database));
        }
        $stmt    = $this->pdo->query($sql);
        $rows    = [];
        $columns = [];
        $affected = 0;
        if ($stmt) {
            $rows     = $stmt->fetchAll() ?: [];
            $columns  = $rows ? array_keys($rows[0]) : [];
            $affected = $stmt->rowCount();
        }
        return compact('rows', 'columns', 'affected');
    }

    public function exportDatabase(string $database): string
    {
        $db     = $this->quoteIdentifier($database);
        $tables = $this->getTables($database);
        $sql    = "-- GC-Manager Export\n-- Database: {$database}\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql   .= "CREATE DATABASE IF NOT EXISTS {$db};\nUSE {$db};\n\n";

        foreach ($tables as $table) {
            $name      = $table['Name'];
            $createSql = $this->getTableCreateSql($database, $name);
            $sql      .= "-- Table: {$name}\n";
            $sql      .= "DROP TABLE IF EXISTS " . $this->quoteIdentifier($name) . ";\n";
            $sql      .= $createSql . ";\n\n";

            $tbl  = $this->quoteIdentifier($name);
            $rows = $this->pdo->query("SELECT * FROM {$db}.{$tbl}")->fetchAll();
            if ($rows) {
                $cols = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($rows[0])));
                foreach ($rows as $row) {
                    $vals = implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $this->pdo->quote((string) $v), $row));
                    $sql .= "INSERT INTO {$tbl} ({$cols}) VALUES ({$vals});\n";
                }
                $sql .= "\n";
            }
        }

        return $sql;
    }

    public function getCharsets(): array
    {
        return $this->pdo->query("SHOW CHARACTER SET")->fetchAll();
    }

    public function getCollations(string $charset = ''): array
    {
        if ($charset) {
            $stmt = $this->pdo->prepare("SHOW COLLATION WHERE Charset = ?");
            $stmt->execute([$charset]);
            return $stmt->fetchAll();
        }
        return $this->pdo->query("SHOW COLLATION")->fetchAll();
    }

    public function addColumn(string $database, string $table, array $col, string $after = ''): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        $def = $this->buildColumnDef($col);
        $pos = $after ? ' AFTER ' . $this->quoteIdentifier($after) : '';
        $this->pdo->exec("ALTER TABLE {$db}.{$tbl} ADD COLUMN {$def}{$pos}");
    }

    public function modifyColumn(string $database, string $table, string $oldName, array $col): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        $old = $this->quoteIdentifier($oldName);
        $def = $this->buildColumnDef($col);
        $this->pdo->exec("ALTER TABLE {$db}.{$tbl} CHANGE COLUMN {$old} {$def}");
    }

    public function dropColumn(string $database, string $table, string $column): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        $col = $this->quoteIdentifier($column);
        $this->pdo->exec("ALTER TABLE {$db}.{$tbl} DROP COLUMN {$col}");
    }

    public function addIndex(string $database, string $table, string $indexName, array $columns, string $type = ''): void
    {
        $db      = $this->quoteIdentifier($database);
        $tbl     = $this->quoteIdentifier($table);
        $cols    = implode(', ', array_map([$this, 'quoteIdentifier'], $columns));
        $allowed = ['UNIQUE', 'FULLTEXT', 'SPATIAL'];
        $typeStr = in_array(strtoupper($type), $allowed) ? strtoupper($type) . ' ' : '';

        if (strtoupper($indexName) === 'PRIMARY') {
            $this->pdo->exec("ALTER TABLE {$db}.{$tbl} ADD PRIMARY KEY ({$cols})");
        } else {
            $name = $this->quoteIdentifier($indexName);
            $this->pdo->exec("ALTER TABLE {$db}.{$tbl} ADD {$typeStr}INDEX {$name} ({$cols})");
        }
    }

    public function dropIndex(string $database, string $table, string $indexName): void
    {
        $db  = $this->quoteIdentifier($database);
        $tbl = $this->quoteIdentifier($table);
        if (strtoupper($indexName) === 'PRIMARY') {
            $this->pdo->exec("ALTER TABLE {$db}.{$tbl} DROP PRIMARY KEY");
        } else {
            $name = $this->quoteIdentifier($indexName);
            $this->pdo->exec("ALTER TABLE {$db}.{$tbl} DROP INDEX {$name}");
        }
    }

    public function getForeignKeys(string $database, string $table): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_SCHEMA, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE, rc.DELETE_RULE
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
             JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
               ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
              AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
             WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION"
        );
        $stmt->execute([$database, $table]);
        return $stmt->fetchAll();
    }

    public function addForeignKey(string $database, string $table, array $fk): void
    {
        $db     = $this->quoteIdentifier($database);
        $tbl    = $this->quoteIdentifier($table);
        $name   = $this->quoteIdentifier($fk['name']);
        $col    = $this->quoteIdentifier($fk['column']);
        $refDb  = $this->quoteIdentifier($fk['ref_database'] ?: $database);
        $refTbl = $this->quoteIdentifier($fk['ref_table']);
        $refCol = $this->quoteIdentifier($fk['ref_column']);
        $rules  = ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'];
        $onDel  = in_array(strtoupper($fk['on_delete'] ?? ''), $rules) ? strtoupper($fk['on_delete']) : 'RESTRICT';
        $onUpd  = in_array(strtoupper($fk['on_update'] ?? ''), $rules) ? strtoupper($fk['on_update']) : 'RESTRICT';
        $this->pdo->exec("ALTER TABLE {$db}.{$tbl} ADD CONSTRAINT {$name} FOREIGN KEY ({$col}) REFERENCES {$refDb}.{$refTbl} ({$refCol}) ON DELETE {$onDel} ON UPDATE {$onUpd}");
    }

    public function dropForeignKey(string $database, string $table, string $fkName): void
    {
        $db   = $this->quoteIdentifier($database);
        $tbl  = $this->quoteIdentifier($table);
        $name = $this->quoteIdentifier($fkName);
        $this->pdo->exec("ALTER TABLE {$db}.{$tbl} DROP FOREIGN KEY {$name}");
    }

    public function getPrimaryKeys(string $database, string $table): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute([$database, $table]);
        return array_column($stmt->fetchAll(), 'COLUMN_NAME');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildColumnDef(array $col): string
    {
        $def = $this->quoteIdentifier($col['name']) . ' ' . $col['type'];
        if (! empty($col['unsigned']))       $def .= ' UNSIGNED';
        $def .= ! empty($col['notnull'])    ? ' NOT NULL' : ' NULL';
        if (! empty($col['auto_increment'])) $def .= ' AUTO_INCREMENT';
        if (isset($col['default']) && $col['default'] !== '') {
            $def .= " DEFAULT '" . str_replace("'", "''", $col['default']) . "'";
        }
        if (! empty($col['comment'])) {
            $def .= " COMMENT '" . str_replace("'", "''", $col['comment']) . "'";
        }
        return $def;
    }

    private function quoteIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
