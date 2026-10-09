<?php

$sqlitePath = __DIR__.'/../database/database.sqlite';
$outputPath = __DIR__.'/../database/cbm_data_for_mysql.sql';

if (!file_exists($sqlitePath)) {
    die("database.sqlite not found!\n");
}

$pdo = new PDO("sqlite:{$sqlitePath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'telescope_%'")->fetchAll(PDO::FETCH_COLUMN);

// Exclude session / cache / jobs tables from dump
$exclude = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'];

$out = fopen($outputPath, 'w');
fwrite($out, "-- CBM Data Dump from SQLite to MySQL\n");
fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($out, "SET NAMES utf8mb4;\n\n");

$totalExported = 0;

foreach ($tables as $table) {
    if (in_array($table, $exclude)) {
        continue;
    }

    $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    if ($count == 0) {
        continue;
    }

    echo "Exporting {$table} ({$count} rows)...\n";
    fwrite($out, "-- Table: {$table} ({$count} rows)\n");
    fwrite($out, "DELETE FROM `{$table}`;\n");

    $stmt = $pdo->query("SELECT * FROM `{$table}`");
    $batch = [];
    $columns = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($columns)) {
            $columns = array_keys($row);
        }

        $values = [];
        foreach ($row as $col => $val) {
            if ($table === 'users' && $col === 'is_default_password') {
                $val = 1;
            }
            if ($val === null) {
                $values[] = 'NULL';
            } elseif (is_int($val) || is_float($val)) {
                $values[] = $val;
            } else {
                $escaped = str_replace(["\\", "'", "\0", "\n", "\r"], ["\\\\", "\\'", "\\0", "\\n", "\\r"], $val);
                $values[] = "'{$escaped}'";
            }
        }
        $batch[] = '('.implode(', ', $values).')';

        if (count($batch) >= 250) {
            $colList = '`'.implode('`, `', $columns).'`';
            fwrite($out, "INSERT INTO `{$table}` ({$colList}) VALUES\n".implode(",\n", $batch).";\n");
            $batch = [];
        }
    }

    if (!empty($batch)) {
        $colList = '`'.implode('`, `', $columns).'`';
        fwrite($out, "INSERT INTO `{$table}` ({$colList}) VALUES\n".implode(",\n", $batch).";\n");
    }

    fwrite($out, "\n");
    $totalExported += $count;
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($out);

$sizeMb = round(filesize($outputPath) / 1024 / 1024, 2);
echo "Exported {$totalExported} rows to {$outputPath} ({$sizeMb} MB)\n";

$gzPath = $outputPath . '.gz';
file_put_contents($gzPath, gzencode(file_get_contents($outputPath), 9));
$gzSizeMb = round(filesize($gzPath) / 1024 / 1024, 2);
echo "SUCCESS! Compressed to {$gzPath} ({$gzSizeMb} MB)\n";
