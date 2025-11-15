<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

class DatabaseMigrator
{
    /**
     * Create application tables if they do not exist.
     */
    public function migrate(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON');

        $statements = [
            <<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                login TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS vehicles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                brand TEXT NOT NULL,
                license_plate TEXT NOT NULL UNIQUE,
                max_weight REAL NOT NULL,
                max_volume REAL NOT NULL
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                weight REAL NOT NULL,
                length REAL NOT NULL,
                width REAL NOT NULL,
                height REAL NOT NULL
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS deliveries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                courier_id INTEGER,
                vehicle_id INTEGER,
                created_by INTEGER NOT NULL,
                delivery_date TEXT NOT NULL,
                time_start TEXT NOT NULL,
                time_end TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (courier_id) REFERENCES users(id),
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS delivery_points (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                delivery_id INTEGER NOT NULL,
                sequence INTEGER NOT NULL,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                UNIQUE (delivery_id, sequence),
                FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE
            )
            SQL,
            <<<SQL
            CREATE TABLE IF NOT EXISTS delivery_point_products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                delivery_point_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                FOREIGN KEY (delivery_point_id) REFERENCES delivery_points(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )
            SQL,
        ];

        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }
    }
}
