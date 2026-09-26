<?php

declare(strict_types=1);

namespace App\Pos;

use PDO;

/**
 * A client's POS `tbl_customers`, read-only. The balance shown everywhere is
 * the POS-maintained `tbl_customers.balance`; history comes from
 * tbl_point_transactions (see PointTransactionRepository).
 */
final class CustomerRepository
{
    public const PAGE_SIZE = 50;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * One page of active customers, optionally filtered on name / code / phone.
     *
     * @return array{rows:array<int,array<string,mixed>>,total:int,pages:int,page:int}
     */
    public function activeList(string $q = '', int $page = 1, ?int $pageSize = self::PAGE_SIZE): array
    {
        [$where, $params] = $this->activeWhere($q);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM tbl_customers c $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT c.id, c.name, c.code, c.phone1, COALESCE(c.balance, 0) AS balance
                FROM tbl_customers c $where
                ORDER BY c.name, c.id";
        if ($pageSize !== null) {
            $pages = max(1, (int) ceil($total / $pageSize));
            $page  = min(max(1, $page), $pages);
            $sql  .= ' LIMIT ' . $pageSize . ' OFFSET ' . (($page - 1) * $pageSize);
        } else {
            $pages = 1;
            $page  = 1;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** Sum of every active customer's balance — the deposit liability held. */
    public function totalBalance(): float
    {
        return (float) $this->pdo->query(
            "SELECT COALESCE(SUM(balance), 0) FROM tbl_customers WHERE active = b'1'"
        )->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, code, phone1, email, created, expired,
                    COALESCE(balance, 0) AS balance, CAST(active AS UNSIGNED) AS active
             FROM tbl_customers WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,array<string,mixed>> active customers with a code
     */
    public function findManyWithCode(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, name, code FROM tbl_customers
             WHERE id IN ($in) AND active = b'1' AND code IS NOT NULL AND code <> ''
             ORDER BY name, id"
        );
        $stmt->execute($ids);

        return $stmt->fetchAll();
    }

    /**
     * The customer a QR code points at. Codes aren't unique in the POS, so the
     * oldest active row with that code wins — the same one every time.
     *
     * @return array<string,mixed>|null
     */
    public function findActiveByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, code, COALESCE(balance, 0) AS balance
             FROM tbl_customers WHERE code = :code AND active = b'1'
             ORDER BY id LIMIT 1"
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Codes shared by more than one active customer. Their QR codes and
     * history can't tell those customers apart, so the UI flags them.
     *
     * @return array<string,int> code => how many customers share it
     */
    public function duplicateCodes(): array
    {
        $rows = $this->pdo->query(
            "SELECT code, COUNT(*) AS n FROM tbl_customers
             WHERE active = b'1' AND code IS NOT NULL AND code <> ''
             GROUP BY code HAVING n > 1"
        )->fetchAll();

        return array_column($rows, 'n', 'code');
    }

    /** @return array{0:string,1:array<string,string>} */
    private function activeWhere(string $q): array
    {
        $where  = "WHERE c.active = b'1'";
        $params = [];
        if ($q !== '') {
            // Escape LIKE wildcards so a literal % or _ in the query isn't a wildcard.
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= ' AND (c.name LIKE :q1 OR c.code LIKE :q2 OR c.phone1 LIKE :q3)';
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }

        return [$where, $params];
    }
}
