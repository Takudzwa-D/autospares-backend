<?php

namespace App\Models;

class Brand extends Model {
    protected $table = 'brands';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$limit, $offset]);
        }
        return $this->getRows($sql);
    }

    public function findById($id) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
    }

    public function findByName($name) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE name = ? LIMIT 1", [$name]);
    }

    public function getAllWithModelCounts() {
        return $this->getRows(
            "SELECT b.*, COUNT(m.id) AS model_count
             FROM {$this->table} b
             LEFT JOIN models m ON m.brand_id = b.id
             GROUP BY b.id
             ORDER BY b.name ASC"
        );
    }

    public function getModelCount($brandId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM models WHERE brand_id = ?",
            [$brandId]
        );
    }

    public function create($data) {
        $defaults = ['name' => '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateBrand($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteBrand($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }
}

?>
