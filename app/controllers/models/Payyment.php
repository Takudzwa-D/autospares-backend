<?php

namespace App\Models;

/**
 * Payment Model - Automotive Backend
 * Handles payment operations
 * Different implementation than electronics backend
 */
class Payment extends Model {
    protected $table = 'payments';
    private static $columnCache = [];

    private function getColumns() {
        if (!isset(self::$columnCache[$this->table])) {
            $rows = $this->getRows("SHOW COLUMNS FROM {$this->table}");
            self::$columnCache[$this->table] = array_map(static fn($row) => $row['Field'], $rows);
        }

        return self::$columnCache[$this->table];
    }

    private function hasColumn($column) {
        return in_array($column, $this->getColumns(), true);
    }

    private function filterSupportedFields(array $data) {
        $columns = $this->getColumns();

        return array_filter(
            $data,
            static fn($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
                FROM {$this->table} p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN Users u ON o.user_id = u.id
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$limit, $offset]);
        }
        
        return $this->getRows($sql);
    }

    public function findById($id) {
        return $this->getRow(
            "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
             FROM {$this->table} p
             LEFT JOIN orders o ON p.order_id = o.id
             LEFT JOIN Users u ON o.user_id = u.id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );
    }

    public function getByOrderId($orderId) {
        return $this->getRow(
            "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
             FROM {$this->table} p
             LEFT JOIN orders o ON p.order_id = o.id
             LEFT JOIN Users u ON o.user_id = u.id
             WHERE p.order_id = ? LIMIT 1",
            [$orderId]
        );
    }

    public function findByOrderId($orderId) {
        return $this->getByOrderId($orderId);
    }

    public function getByStatus($status, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
                FROM {$this->table} p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN Users u ON o.user_id = u.id
                WHERE p.payment_status = ?
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$status, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$status]);
    }

    public function getByMethod($method, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
                FROM {$this->table} p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN Users u ON o.user_id = u.id
                WHERE p.payment_method = ?
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$method, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$method]);
    }

    public function create($data) {
        $defaults = [
            'order_id' => null,
            'payment_method' => 'Credit Card',
            'payment_status' => PAYMENT_STATUS_PENDING,
            'merchant_reference' => null,
            'paynow_reference' => null,
            'poll_url' => null,
            'browser_url' => null,
            'payment_details' => null,
            'paid_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        return $this->insert($this->filterSupportedFields($data));
    }

    public function updateStatus($id, $status) {
        $payload = [
            'payment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === PAYMENT_STATUS_COMPLETED && $this->hasColumn('paid_at')) {
            $payload['paid_at'] = date('Y-m-d H:i:s');
        }

        return $this->update(
            $this->filterSupportedFields($payload),
            "id = ?",
            [$id]
        );
    }

    public function updatePayment($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $filtered = $this->filterSupportedFields($data);
        if (empty($filtered)) {
            return 0;
        }

        return $this->update($filtered, "id = ?", [$id]);
    }

    public function deletePayment($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    public function countByStatus($status) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE payment_status = ?",
            [$status]
        );
    }

    public function findByMerchantReference($reference) {
        if ($this->hasColumn('merchant_reference')) {
            $payment = $this->getRow(
                "SELECT p.*, o.customer_name, o.total_amount, o.status AS order_status, u.Email AS customer_email
                 FROM {$this->table} p
                 LEFT JOIN orders o ON p.order_id = o.id
                 LEFT JOIN Users u ON o.user_id = u.id
                 WHERE p.merchant_reference = ? LIMIT 1",
                [$reference]
            );

            if ($payment) {
                return $payment;
            }
        }

        if (preg_match('/^AS-(\d+)-\d+$/', (string)$reference, $matches)) {
            return $this->findByOrderId((int)$matches[1]);
        }

        return null;
    }

    public function getSuccessRate($days = 30) {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $sql = "SELECT 
                COUNT(CASE WHEN payment_status = ? THEN 1 END) as successful,
                COUNT(*) as total
                FROM {$this->table}
                WHERE created_at >= ?";
        
        $row = $this->getRow($sql, [PAYMENT_STATUS_COMPLETED, $since]);
        
        if ($row && $row['total'] > 0) {
            return round(($row['successful'] / $row['total']) * 100, 2);
        }
        
        return 0;
    }

    public function getStats($days = 30) {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $summary = $this->getRow(
            "SELECT
                COUNT(*) AS total_payments,
                COUNT(CASE WHEN payment_status = 'Completed' THEN 1 END) AS completed_payments,
                COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) AS pending_payments,
                COUNT(CASE WHEN payment_status = 'Failed' THEN 1 END) AS failed_payments
            FROM {$this->table}
            WHERE created_at >= ?",
            [$since]
        );

        return [
            'days' => $days,
            'total_payments' => (int)($summary['total_payments'] ?? 0),
            'completed_payments' => (int)($summary['completed_payments'] ?? 0),
            'pending_payments' => (int)($summary['pending_payments'] ?? 0),
            'failed_payments' => (int)($summary['failed_payments'] ?? 0),
            'success_rate' => $this->getSuccessRate($days),
        ];
    }
}

?>
