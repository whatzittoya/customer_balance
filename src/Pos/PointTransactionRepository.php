<?php

declare(strict_types=1);

namespace App\Pos;

use PDO;

/**
 * A client's POS `tbl_point_transactions` — the balance history (top-ups,
 * consumption, adjustments). Rows link to a customer through
 * `customerCode = tbl_customers.code`.
 */
final class PointTransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> newest first */
    public function historyForCode(string $code): array
    {
        if ($code === '') {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, date, remark, amount, paymentMethod, paymentRemark, outlet, employeeName, station
             FROM tbl_point_transactions
             WHERE customerCode = :code
             ORDER BY date DESC, id DESC'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetchAll();
    }
}
