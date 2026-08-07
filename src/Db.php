<?php

declare(strict_types=1);

namespace BayarcashDemo;

use PDO;
use PDOException;
use RuntimeException;

/**
 * SQLite by default so a fresh clone runs with no setup. Switch in config.php:
 *
 *     'db' => ['driver' => 'mysql', 'host' => ..., 'dbname' => ...],
 *
 * The engines differ in exactly two places, both below: the auto-increment key
 * syntax, and how you say "today" in SQL.
 */
final class Db
{
    private PDO $pdo;
    private string $driver;

    private function __construct(PDO $pdo, string $driver)
    {
        $this->pdo    = $pdo;
        $this->driver = $driver;

        $this->createTablesIfMissing();
    }

    public static function fromConfig(Config $config): self
    {
        $db     = $config->db();
        $driver = $db['driver'] ?? 'sqlite';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    $db['host'] ?? 'localhost',
                    $db['dbname'] ?? ''
                );

                $pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', $options);
            } else {
                $driver = 'sqlite';
                $path   = $db['path'] ?? dirname(__DIR__) . '/storage/demo.sqlite';

                if (! is_dir(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                $pdo = new PDO('sqlite:' . $path, null, null, $options);
                $pdo->exec('PRAGMA journal_mode = WAL');
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Could not connect to the {$driver} database: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return new self($pdo, $driver);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function todayExpression(): string
    {
        return $this->driver === 'mysql' ? 'CURDATE()' : "DATE('now')";
    }

    private function createTablesIfMissing(): void
    {
        $id = $this->driver === 'mysql'
            ? 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY'
            : 'id INTEGER PRIMARY KEY AUTOINCREMENT';

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                {$id},
                record_type               VARCHAR(50),
                transaction_id            VARCHAR(50) UNIQUE,
                payment_intent_id         VARCHAR(50),
                exchange_reference_number VARCHAR(50),
                exchange_transaction_id   VARCHAR(50),
                order_number              VARCHAR(50),
                currency                  VARCHAR(10),
                amount                    DECIMAL(10, 2),
                payer_name                VARCHAR(100),
                payer_email               VARCHAR(100),
                payer_bank_name           VARCHAR(100),
                status                    VARCHAR(10),
                status_description        TEXT,
                datetime                  VARCHAR(30),
                checksum                  VARCHAR(255),
                raw_return                TEXT,
                raw_callback              TEXT,
                created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at                TIMESTAMP NULL
            )
        ");

        $this->addMissingColumns();
    }

    /**
     * Adds columns introduced after a table was first created, so an existing
     * database picks them up without a migration step.
     */
    private function addMissingColumns(): void
    {
        $existing = [];
        $stmt     = $this->pdo->query('SELECT * FROM transactions LIMIT 0');

        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $existing[] = $stmt->getColumnMeta($i)['name'];
        }

        $wanted = [
            'payment_intent_id' => 'VARCHAR(50)',
            'raw_return'        => 'TEXT',
            'raw_callback'      => 'TEXT',
        ];

        foreach ($wanted as $column => $type) {
            if (! in_array($column, $existing, true)) {
                $this->pdo->exec("ALTER TABLE transactions ADD COLUMN {$column} {$type}");
            }
        }
    }
}
