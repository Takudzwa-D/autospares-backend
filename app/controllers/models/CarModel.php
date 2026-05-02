<?php

namespace App\Models;

class CarModel extends Model {
    protected $table = 'models';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$limit, $offset]);
        }
        return $this->getRows($sql);
    }

    public function getByBrandId($brandId, $limit = null, $offset = 0) {
        $sql = "SELECT m.*, b.name AS brand_name
                FROM {$this->table} m
                LEFT JOIN brands b ON b.id = m.brand_id
                WHERE m.brand_id = ?
                ORDER BY m.name ASC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$brandId, $limit, $offset]);
        }
        return $this->getRows($sql, [$brandId]);
    }

    public function findById($id) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
    }

    public function findDetailedById($id) {
        return $this->getRow(
            "SELECT m.*, b.name AS brand_name
             FROM {$this->table} m
             LEFT JOIN brands b ON b.id = m.brand_id
             WHERE m.id = ? LIMIT 1",
            [$id]
        );
    }

    public function getAllWithBrandNames() {
        return $this->getRows(
            "SELECT m.*, b.name AS brand_name
             FROM {$this->table} m
             LEFT JOIN brands b ON b.id = m.brand_id
             ORDER BY b.name ASC, m.name ASC"
        );
    }

    public function findByName($name) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE name = ? LIMIT 1", [$name]);
    }

    public function create($data) {
        $defaults = ['brand_id' => null, 'name' => '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateModel($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteModel($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    public function getProductCount($modelId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM products WHERE model_id = ?",
            [$modelId]
        );
    }
}

?>
