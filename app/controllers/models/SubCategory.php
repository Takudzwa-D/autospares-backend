<?php

namespace App\Models;

class SubCategory extends Model {
    protected $table = 'sub_categories';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$limit, $offset]);
        }
        return $this->getRows($sql);
    }

    public function getByCategoryId($categoryId, $limit = null, $offset = 0) {
        $sql = "SELECT sc.*, c.name AS category_name
                FROM {$this->table} sc
                LEFT JOIN Categories c ON c.id = sc.category_id
                WHERE sc.category_id = ?
                ORDER BY sc.name ASC";
        
        if ($limit) {
            return $this->getRows($sql . " LIMIT ? OFFSET ?", [$categoryId, $limit, $offset]);
        }
        return $this->getRows($sql, [$categoryId]);
    }

    public function findById($id) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1", [$id]);
    }

    public function findDetailedById($id) {
        return $this->getRow(
            "SELECT sc.*, c.name AS category_name
             FROM {$this->table} sc
             LEFT JOIN Categories c ON c.id = sc.category_id
             WHERE sc.id = ? LIMIT 1",
            [$id]
        );
    }

    public function getAllDetailed() {
        return $this->getRows(
            "SELECT sc.*, c.name AS category_name
             FROM {$this->table} sc
             LEFT JOIN Categories c ON c.id = sc.category_id
             ORDER BY c.name ASC, sc.name ASC"
        );
    }

    public function findByName($name) {
        return $this->getRow("SELECT * FROM {$this->table} WHERE name = ? LIMIT 1", [$name]);
    }

    public function create($data) {
        $defaults = ['category_id' => null, 'name' => '', 'description' => '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateSubCategory($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteSubCategory($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    public function getProductCount($subCategoryId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM products WHERE sub_category_id = ?",
            [$subCategoryId]
        );
    }
}

?>
