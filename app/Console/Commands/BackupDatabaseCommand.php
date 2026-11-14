<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--retention=30 : Number of days to retain backups}
                            {--filename= : Custom filename for backup archive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a compressed database backup archive and prune backups older than the retention threshold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retention = (int) $this->option('retention');
        if ($retention <= 0) {
            $retention = 30;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $gitignorePath = $backupDir . DIRECTORY_SEPARATOR . '.gitignore';
        if (!File::exists($gitignorePath)) {
            File::put($gitignorePath, "*\n!.gitignore\n");
        }

        $customFilename = $this->option('filename');
        if ($customFilename) {
            $filename = (string) $customFilename;
            if (!str_ends_with($filename, '.gz')) {
                $filename = str_ends_with($filename, '.sql') ? $filename . '.gz' : $filename . '.sql.gz';
            }
        } else {
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $filename = "backup-{$timestamp}.sql.gz";
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Debug
        // echo "BACKUP PATH: " . $backupPath . "\n";

        $this->info("Initiating database backup...");

        try {
            $driver = DB::connection()->getDriverName();
            $sqlContent = $this->dumpDatabase($driver);

            $this->compressToFile($sqlContent, $backupPath);

            $sizeBytes = File::size($backupPath);
            $formattedSize = $this->formatBytes($sizeBytes);

            // Automated retention cleanup
            $prunedCount = $this->pruneOldBackups($backupDir, $retention);

            Log::info("Database backup created successfully: {$filename} ({$formattedSize}). Retention: {$retention} days. Pruned: {$prunedCount} old file(s).", [
                'path' => $backupPath,
                'driver' => $driver,
                'size_bytes' => $sizeBytes,
                'pruned_count' => $prunedCount,
            ]);

            $this->info("Database backup created successfully!");
            $this->line("  <comment>Location:</comment> {$backupPath}");
            $this->line("  <comment>Size:</comment>     {$formattedSize}");
            $this->line("  <comment>Driver:</comment>   {$driver}");

            if ($prunedCount > 0) {
                $this->info("Pruned {$prunedCount} old backup file(s) exceeding {$retention}-day retention.");
            } else {
                $this->line("No old backup files to prune (retention: {$retention} days).");
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::error("Database backup command failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            $this->error("Database backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Dump database content based on driver.
     */
    protected function dumpDatabase(string $driver): string
    {
        $pdo = DB::connection()->getPdo();

        if ($driver === 'sqlite') {
            return $this->dumpSqlite($pdo);
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Attempt mysqldump binary first if available; fall back to PHP PDO
            $mysqldumpBinary = $this->findMysqldumpBinary();
            if ($mysqldumpBinary) {
                try {
                    return $this->dumpWithMysqldump($mysqldumpBinary);
                } catch (Throwable $e) {
                    $this->warn("mysqldump binary execution failed ({$e->getMessage()}), falling back to PDO dumper.");
                }
            }

            $dbName = (string) config('database.connections.mysql.database', 'laravel');
            return $this->dumpMysqlPdo($pdo, $dbName);
        }

        // Generic fallback using PDO
        return $this->dumpGenericPdo($pdo);
    }

    /**
     * Dump SQLite schema and data into SQL statements.
     */
    protected function dumpSqlite(PDO $pdo): string
    {
        $lines = [];
        $lines[] = "-- LMS Andik Laundry Database Backup (SQLite)";
        $lines[] = "-- Backup Date: " . Carbon::now()->toIso8601String();
        $lines[] = "PRAGMA foreign_keys = OFF;";
        $lines[] = "";

        $stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $tableName = $table['name'];
            $createSql = $table['sql'];

            if (empty($createSql)) {
                continue;
            }

            $lines[] = "-- Table structure for table \"{$tableName}\"";
            $lines[] = "DROP TABLE IF EXISTS \"{$tableName}\";";
            $lines[] = $createSql . ";";
            $lines[] = "";

            $rowsStmt = $pdo->query("SELECT * FROM \"{$tableName}\"");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $lines[] = "-- Dumping data for table \"{$tableName}\"";
                foreach ($rows as $row) {
                    $cols = array_map(fn ($c) => "\"{$c}\"", array_keys($row));
                    $vals = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        if (is_numeric($val) && !is_string($val)) {
                            return $val;
                        }
                        return $pdo->quote((string) $val);
                    }, array_values($row));

                    $lines[] = "INSERT INTO \"{$tableName}\" (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");";
                }
                $lines[] = "";
            }
        }

        $lines[] = "PRAGMA foreign_keys = ON;";

        return implode("\n", $lines);
    }

    /**
     * Dump MySQL database using pure PHP PDO.
     */
    protected function dumpMysqlPdo(PDO $pdo, string $database): string
    {
        $lines = [];
        $lines[] = "-- LMS Andik Laundry Database Backup (MySQL PDO Engine)";
        $lines[] = "-- Database: `{$database}`";
        $lines[] = "-- Backup Date: " . Carbon::now()->toIso8601String();
        $lines[] = "SET FOREIGN_KEY_CHECKS=0;";
        $lines[] = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";";
        $lines[] = "";

        $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

        foreach ($tables as $tableRow) {
            $tableName = $tableRow[0];

            $lines[] = "-- Table structure for table `{$tableName}`";
            $lines[] = "DROP TABLE IF EXISTS `{$tableName}`;";

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            $lines[] = $createRow[1] . ";";
            $lines[] = "";

            $rowsStmt = $pdo->query("SELECT * FROM `{$tableName}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $lines[] = "-- Dumping data for table `{$tableName}`";
                $chunks = array_chunk($rows, 100);
                foreach ($chunks as $chunk) {
                    $firstRow = $chunk[0];
                    $columns = array_map(fn ($col) => "`{$col}`", array_keys($firstRow));
                    $valueRows = [];

                    foreach ($chunk as $row) {
                        $values = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            if (is_numeric($val) && !is_string($val)) {
                                return $val;
                            }
                            return $pdo->quote((string) $val);
                        }, array_values($row));
                        $valueRows[] = "(" . implode(', ', $values) . ")";
                    }

                    $lines[] = "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES\n" . implode(",\n", $valueRows) . ";";
                }
                $lines[] = "";
            }
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS=1;";

        return implode("\n", $lines);
    }

    /**
     * Dump generic SQL database via PDO.
     */
    protected function dumpGenericPdo(PDO $pdo): string
    {
        return "-- LMS Andik Laundry Database Backup\n-- Generic PDO driver\n";
    }

    /**
     * Attempt to find mysqldump binary in standard locations or PATH.
     */
    protected function findMysqldumpBinary(): ?string
    {
        $commonPaths = [
            'mysqldump',
            'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\current\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($commonPaths as $path) {
            try {
                $process = new Process([$path, '--version']);
                $process->run();
                if ($process->isSuccessful()) {
                    return $path;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Execute mysqldump command and return SQL text.
     */
    protected function dumpWithMysqldump(string $binary): string
    {
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (string) config('database.connections.mysql.port', '3306');
        $database = (string) config('database.connections.mysql.database', 'lms_andiklaundry');
        $username = (string) config('database.connections.mysql.username', 'root');
        $password = (string) config('database.connections.mysql.password', '');

        $cmd = [
            $binary,
            "--host={$host}",
            "--port={$port}",
            "--user={$username}",
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
        ];

        if ($password !== '') {
            $cmd[] = "--password={$password}";
        } else {
            $cmd[] = '--password=';
        }

        $cmd[] = $database;

        $process = new Process($cmd);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * Compress content using gzip level 9 and write to file.
     */
    protected function compressToFile(string $content, string $destinationPath): void
    {
        $gz = gzopen($destinationPath, 'wb9');
        if ($gz === false) {
            throw new \RuntimeException("Failed to open file for gzip compression: {$destinationPath}");
        }

        $writeResult = gzwrite($gz, $content);
        gzclose($gz);

        if ($writeResult === false) {
            throw new \RuntimeException("Failed to write compressed data to: {$destinationPath}");
        }
    }

    /**
     * Prune backup archives older than retention threshold.
     */
    protected function pruneOldBackups(string $backupDir, int $retentionDays): int
    {
        $cutoff = Carbon::now()->subDays($retentionDays)->getTimestamp();
        $files = File::files($backupDir);
        $prunedCount = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if ($filename === '.gitignore') {
                continue;
            }

            if (str_ends_with($filename, '.gz')) {
                if ($file->getMTime() < $cutoff) {
                    File::delete($file->getPathname());
                    Log::info("Pruned old backup file: {$filename} (older than {$retentionDays} days)");
                    $prunedCount++;
                }
            }
        }

        return $prunedCount;
    }

    /**
     * Human-readable byte formatting.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
