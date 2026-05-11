<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use PDO;

#[Signature('app:migrate-sqlite-to-mysql')]
#[Description('Migrate all data from SQLite to MySQL database')]
class MigrateSqliteToMysql extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqlitePath = database_path('database.sqlite');

        // Connect to SQLite
        try {
            $sqlite = new PDO('sqlite:' . $sqlitePath);
            $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->info('✓ Connected to SQLite');
        } catch (\Exception $e) {
            $this->error('✗ SQLite connection failed: ' . $e->getMessage());
            return 1;
        }

        // Get MySQL connection
        try {
            $mysql = new PDO(
                'mysql:host=' . config('database.connections.mysql.host') . ';dbname=' . config('database.connections.mysql.database'),
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password')
            );
            $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->info('✓ Connected to MySQL');
        } catch (\Exception $e) {
            $this->error('✗ MySQL connection failed: ' . $e->getMessage());
            return 1;
        }

        // Get tables from SQLite
        $tables = $sqlite->query("
            SELECT name FROM sqlite_master 
            WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ")->fetchAll(PDO::FETCH_COLUMN);

        $this->line("\nTables to migrate: " . implode(', ', $tables) . "\n");

        foreach ($tables as $table) {
            // Skip migrations and password resets
            if (in_array($table, ['migrations', 'password_resets'])) {
                continue;
            }

            try {
                // Disable foreign keys
                $mysql->exec('SET FOREIGN_KEY_CHECKS=0');
                
                // Clear the table
                $mysql->exec("TRUNCATE TABLE `$table`");
                
                // Get all data from SQLite
                $rows = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($rows)) {
                    $this->line("  Table '$table': 0 rows");
                    continue;
                }

                // Get column names
                $columns = array_keys($rows[0]);
                $placeholders = implode(',', array_fill(0, count($columns), '?'));
                $columnList = implode(',', array_map(fn($c) => "`$c`", $columns));

                // Prepare insert statement
                $stmt = $mysql->prepare("INSERT INTO `$table` ($columnList) VALUES ($placeholders)");

                // Insert rows
                foreach ($rows as $row) {
                    $values = array_values($row);
                    $stmt->execute($values);
                }

                $this->line("  ✓ Table '$table': " . count($rows) . " rows migrated");

                // Re-enable foreign keys
                $mysql->exec('SET FOREIGN_KEY_CHECKS=1');

            } catch (\Exception $e) {
                $this->error("  ✗ Error migrating '$table': " . $e->getMessage());
            }
        }

        $this->info("\n✓ Migration complete!");
        return 0;
    }
}
