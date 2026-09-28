<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Writes a plain-SQL dump of every table (schema + rows) through $write, one chunk
 * at a time so large upload blobs never sit in memory all at once. Binary columns are
 * emitted as hex literals (byte-exact); everything else goes through PDO::quote(), since
 * a hex literal would be read as a number by INT/DATETIME columns. Restore with the
 * mysql client: `mysql -h ... -u ... -p railway < backup.sql`.
 */
final class DatabaseBackup
{
    private const ROWS_PER_INSERT = 50;

    /** @param callable(string): void $write */
    public function dump(callable $write): void
    {
        $pdo = Database::pdo();
        $write("-- O'quv markazi database backup, " . date('Y-m-d H:i:s') . "\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM)[1];
            $write("DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");
            $this->dumpRows($pdo, (string) $table, $write);
        }

        $write("SET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function dumpRows(PDO $pdo, string $table, callable $write): void
    {
        $binaryColumns = $this->binaryColumns($pdo, $table);

        // Unbuffered, so rows stream from MySQL instead of loading the whole table.
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        try {
            $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
            $batch = [];
            $columns = null;
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                $columns ??= '`' . implode('`, `', array_keys($row)) . '`';
                $values = [];
                foreach ($row as $column => $value) {
                    $values[] = $this->literal($pdo, $value, isset($binaryColumns[$column]));
                }
                $batch[] = '(' . implode(', ', $values) . ')';
                if (count($batch) === self::ROWS_PER_INSERT) {
                    $write("INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch !== []) {
                $write("INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            $stmt->closeCursor();
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        $write("\n");
    }

    /** @return array<string, true> names of the table's BLOB/BINARY columns */
    private function binaryColumns(PDO $pdo, string $table): array
    {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND DATA_TYPE IN ('tinyblob', 'blob', 'mediumblob', 'longblob', 'binary', 'varbinary')"
        );
        $stmt->execute([$table]);

        return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }

    private function literal(PDO $pdo, mixed $value, bool $binary): string
    {
        if ($value === null) {
            return 'NULL';
        }
        $value = (string) $value;
        if ($binary) {
            return $value === '' ? "''" : '0x' . bin2hex($value);
        }

        return $pdo->quote($value);
    }
}
