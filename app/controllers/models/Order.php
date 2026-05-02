<?php

namespace App\Models;

/**
 * Order Model - Automotive Backend
 * Handles order operations
 * Different implementation than electronics backend
 */
class Order extends Model {
    protected $table = 'orders';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$limit, $offset]);
        }
        
        return $this->getRows($sql);
    }

    public function findById($id) {
        return $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function getByUserId($userId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$userId, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$userId]);
    }

    public function getUserOrders($userId, $limit = null, $offset = 0) {
        return $this->getByUserId($userId, $limit, $offset);
    }

    public function getByStatus($status, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE status = ? ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$status, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$status]);
    }

    public function create($data) {
        $defaults = [
            'user_id' => null,
            'customer_name' => '',
            'customer_phone_number' => '',
            'customer_address' => '',
            'total_amount' => 0,
            'status' => ORDER_STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateStatus($id, $status) {
        return $this->update(
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            "id = ?",
            [$id]
        );
    }

    public function updateOrder($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteOrder($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    public function countByStatus($status) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE status = ?",
            [$status]
        );
    }

    public function countByUser($userId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );
    }

    public function getTotalByStatus($status) {
        return (float)$this->getValue(
            "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE status = ?",
            [$status]
        );
    }

    public function getStats() {
        $summary = $this->getRow(
            "SELECT 
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_orders,
                COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS completed_orders,
                COUNT(CASE WHEN status = 'Canceled' THEN 1 END) AS canceled_orders,
                COUNT(CASE WHEN status = 'Failed' THEN 1 END) AS failed_orders
            FROM {$this->table}"
        );

        return [
            'total_orders' => (int)($summary['total_orders'] ?? 0),
            'total_revenue' => (float)($summary['total_revenue'] ?? 0),
            'pending_orders' => (int)($summary['pending_orders'] ?? 0),
            'completed_orders' => (int)($summary['completed_orders'] ?? 0),
            'canceled_orders' => (int)($summary['canceled_orders'] ?? 0),
            'failed_orders' => (int)($summary['failed_orders'] ?? 0),
        ];
    }
}

?>
