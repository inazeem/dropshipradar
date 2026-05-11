<?php
// Migrate data from SQLite to MySQL

require 'vendor/autoload.php';

$sqlitePath = database_path('database.sqlite');

// Connect to SQLite
try {
    $sqlite = new PDO('sqlite:' . $sqlitePath);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to SQLite\n";
} catch (Exception $e) {
    echo "✗ SQLite connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Get tables from SQLite
$tables = $sqlite->query("
    SELECT name FROM sqlite_master 
    WHERE type='table' AND name NOT LIKE 'sqlite_%'
    ORDER BY name
")->fetchAll(PDO::FETCH_COLUMN);

echo "Tables to migrate: " . implode(', ', $tables) . "\n\n";

// Get MySQL connection
$mysql = new PDO(
    'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
    env('DB_USERNAME'),
    env('DB_PASSWORD')
);
$mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo "✓ Connected to MySQL\n\n";

foreach ($tables as $table) {
    // Skip migrations table
    if ($table === 'migrations' || $table === 'password_resets') {
        continue;
    }

    try {
        // Disable foreign keys
        $mysql->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // Clear the table
        $mysql->exec("TRUNCATE TABLE $table");
        
        // Get all data from SQLite
        $rows = $sqlite->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "  Table '$table': 0 rows\n";
            continue;
        }

        // Get column names
        $columns = array_keys($rows[0]);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $columnList = implode(',', $columns);

        // Prepare insert statement
        $stmt = $mysql->prepare("INSERT INTO $table ($columnList) VALUES ($placeholders)");

        // Insert rows
        foreach ($rows as $row) {
            $values = array_values($row);
            $stmt->execute($values);
        }

        echo "  ✓ Table '$table': " . count($rows) . " rows migrated\n";

        // Re-enable foreign keys
        $mysql->exec('SET FOREIGN_KEY_CHECKS=1');

    } catch (Exception $e) {
        echo "  ✗ Error migrating '$table': " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Migration complete!\n";
