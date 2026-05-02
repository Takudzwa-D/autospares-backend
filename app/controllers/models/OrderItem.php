<?php

namespace App\Models;

class OrderItem extends Model {
    protected $table = 'order_items';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$limit, $offset]);
        }
        return $this->getRows($sql);
    }

    public function findById($id) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
    }

    public function getByOrderId($orderId) {
        return $this->getRows(
            "SELECT oi.*, p.name as product_name, p.img as product_image
            FROM {$this->table} oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.created_at DESC",
            [$orderId]
        );
    }

    public function findByOrderId($orderId) {
        return $this->getByOrderId($orderId);
    }

    public function getByProductId($productId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ? ORDER BY created_at DESC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$productId, $limit, $offset]);
        }
        return $this->getRows($sql, [$productId]);
    }

    public function create($data) {
        $defaults = ['order_id' => null, 'product_id' => null, 'quantity' => 0, 'price' => 0, 'created_at' => date('Y-m-d H:i:s')];
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateItem($id, $data) {
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteItem($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function deleteByOrderId($orderId) {
        return $this->delete("order_id = ?", [$orderId]);
    }

    public function getOrderTotal($orderId) {
        return (float)($this->getValue(
            "SELECT COALESCE(SUM(quantity * price), 0) FROM {$this->table} WHERE order_id = ?",
            [$orderId]
        ) ?? 0);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }
}

?>
