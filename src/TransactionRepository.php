<?php

declare(strict_types=1);

namespace BayarcashDemo;

use PDO;

/**
 * Written by both callback.php and return.php, through this one guarded path
 * so neither can overwrite what the other settled.
 */
final class TransactionRepository
{
    private const FIELDS = [
        'record_type',
        'transaction_id',
        'payment_intent_id',
        'exchange_reference_number',
        'exchange_transaction_id',
        'order_number',
        'currency',
        'amount',
        'payer_name',
        'payer_email',
        'payer_bank_name',
        'status',
        'status_description',
        'datetime',
        'checksum',
    ];

    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * Call only after the checksum has been verified.
     *
     * Callback delivery is at-least-once and can arrive out of order, so this
     * has to be safe to run twice: the same transaction_id updates one row,
     * and a settled payment is never re-opened by a later callback.
     */
    public function recordCallback(array $callback, string $source = 'callback'): string
    {
        $transactionId = trim((string) ($callback['transaction_id'] ?? ''));

        if ($transactionId === '') {
            return 'ignored (no transaction_id)';
        }

        $existing  = $this->find($transactionId);
        $rawColumn = $source === 'return' ? 'raw_return' : 'raw_callback';
        $raw       = json_encode($callback, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // A settled payment does not change, but the payload is still worth
        // keeping -- it is how you see what each source actually delivered.
        if ($existing !== null && PaymentStatus::isTerminal($existing['status'] ?? null)) {
            $this->storeRaw($transactionId, $rawColumn, $raw);

            return 'ignored (already ' . PaymentStatus::label($existing['status']) . ')';
        }

        // A pre_transaction carries no payer or amount, so keep what we know
        // rather than blanking the row.
        $row = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $callback) && $callback[$field] !== '') {
                $row[$field] = (string) $callback[$field];
            } elseif ($existing !== null && isset($existing[$field])) {
                $row[$field] = (string) $existing[$field];
            } else {
                $row[$field] = '';
            }
        }

        $row['transaction_id'] = $transactionId;

        // Keep the payload each source delivered, verbatim. Comparing them is
        // the clearest way to see that the return URL carries fewer fields.
        $row[$rawColumn] = $raw;

        if ($existing !== null) {
            $this->update($row);

            return 'updated to ' . PaymentStatus::label($row['status']);
        }

        $this->insert($row);

        return 'recorded as ' . PaymentStatus::label($row['status']);
    }

    public function find(string $transactionId): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT * FROM transactions WHERE transaction_id = :transaction_id'
        );
        $stmt->execute(['transaction_id' => $transactionId]);

        return $stmt->fetch() ?: null;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT * FROM transactions WHERE order_number = :order_number ORDER BY id DESC'
        );
        $stmt->execute(['order_number' => $orderNumber]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(int $limit = 100): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT * FROM transactions ORDER BY id DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** What a requery cron would sweep. */
    public function pendingToday(): array
    {
        $today = $this->db->todayExpression();

        $stmt = $this->db->pdo()->prepare("
            SELECT * FROM transactions
            WHERE status IN (:new_status, :pending_status)
              AND DATE(created_at) = {$today}
        ");
        $stmt->execute([
            'new_status'     => (string) PaymentStatus::NEW,
            'pending_status' => (string) PaymentStatus::PENDING,
        ]);

        return $stmt->fetchAll();
    }

    /** Stores a payload without touching the payment's status. */
    private function storeRaw(string $transactionId, string $column, string $raw): void
    {
        $stmt = $this->db->pdo()->prepare(
            "UPDATE transactions SET {$column} = :raw WHERE transaction_id = :transaction_id"
        );
        $stmt->execute(['raw' => $raw, 'transaction_id' => $transactionId]);
    }

    private function insert(array $row): void
    {
        // Written here rather than left to CURRENT_TIMESTAMP, which is UTC on
        // SQLite and server-local on MySQL -- the same row would differ by hours.
        $row['created_at'] = date('Y-m-d H:i:s');

        $columns      = array_keys($row);
        $placeholders = ':' . implode(', :', $columns);

        $stmt = $this->db->pdo()->prepare(sprintf(
            'INSERT INTO transactions (%s) VALUES (%s)',
            implode(', ', $columns),
            $placeholders
        ));
        $stmt->execute($row);
    }

    private function update(array $row): void
    {
        $assignments = [];
        foreach (array_keys($row) as $field) {
            if ($field !== 'transaction_id') {
                $assignments[] = "{$field} = :{$field}";
            }
        }

        $row['updated_at'] = date('Y-m-d H:i:s');
        $assignments[]     = 'updated_at = :updated_at';

        $stmt = $this->db->pdo()->prepare(sprintf(
            'UPDATE transactions SET %s WHERE transaction_id = :transaction_id',
            implode(', ', $assignments)
        ));
        $stmt->execute($row);
    }
}
