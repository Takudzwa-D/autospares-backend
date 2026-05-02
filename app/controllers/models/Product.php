<?php

namespace App\Models;

/**
 * Product Model - Automotive Backend
 * Handles product operations
 * Products link to sub-categories and car models
 * Different implementation than electronics backend
 */
class Product extends Model {
    protected $table = 'products';

    public function getAll($limit = null, $offset = 0) {
        $sql = "SELECT p.*, 
                sc.name as sub_category_name, 
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$limit, $offset]);
        }
        
        return $this->getRows($sql);
    }

    public function findById($id) {
        return $this->getRow(
            "SELECT p.*, 
                sc.name as sub_category_name, 
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE p.id = ? LIMIT 1",
            [$id]
        );
    }

    public function getBySubCategoryId($subCategoryId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, 
                sc.name as sub_category_name, 
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE p.sub_category_id = ?
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$subCategoryId, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$subCategoryId]);
    }

    public function getByModelId($modelId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, 
                sc.name as sub_category_name, 
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE p.model_id = ?
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$modelId, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$modelId]);
    }

    public function getByCategoryId($categoryId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*,
                sc.name as sub_category_name,
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE c.id = ?
                ORDER BY p.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$categoryId, $limit, $offset]);
        }

        return $this->getRows($sql, [$categoryId]);
    }

    public function getByBrandId($brandId, $limit = null, $offset = 0) {
        $sql = "SELECT p.*,
                sc.name as sub_category_name,
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE b.id = ?
                ORDER BY p.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$brandId, $limit, $offset]);
        }

        return $this->getRows($sql, [$brandId]);
    }

    public function search($query, $limit = null, $offset = 0) {
        $sql = "SELECT p.*, 
                sc.name as sub_category_name, 
                c.name as category_name,
                m.name as model_name,
                b.name as brand_name
                FROM {$this->table} p
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                LEFT JOIN Categories c ON sc.category_id = c.id
                LEFT JOIN models m ON p.model_id = m.id
                LEFT JOIN brands b ON m.brand_id = b.id
                WHERE p.name LIKE ? OR p.description LIKE ?
                ORDER BY p.created_at DESC";
        
        $searchTerm = "%$query%";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->getRows($sql, [$searchTerm, $searchTerm, $limit, $offset]);
        }
        
        return $this->getRows($sql, [$searchTerm, $searchTerm]);
    }

    public function create($data) {
        $defaults = [
            'sub_category_id' => null,
            'model_id' => null,
            'name' => '',
            'description' => '',
            'price' => 0,
            'stock_quantity' => 0,
            'img' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $data);
        return $this->insert($data);
    }

    public function updateProduct($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteProduct($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function updateStock($id, $quantity) {
        return $this->query(
            "UPDATE {$this->table} SET stock_quantity = stock_quantity + ? WHERE id = ?",
            [$quantity, $id]
        )->rowCount();
    }

    public function getStock($id) {
        return (int)$this->getValue(
            "SELECT stock_quantity FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }

    public function countByCategoryId($categoryId) {
        return (int)$this->getValue(
            "SELECT COUNT(*)
             FROM {$this->table} p
             LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
             LEFT JOIN Categories c ON sc.category_id = c.id
             WHERE c.id = ?",
            [$categoryId]
        );
    }

    public function countBySubCategoryId($subCategoryId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE sub_category_id = ?",
            [$subCategoryId]
        );
    }

    public function countByModelId($modelId) {
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE model_id = ?",
            [$modelId]
        );
    }

    public function countByBrandId($brandId) {
        return (int)$this->getValue(
            "SELECT COUNT(*)
             FROM {$this->table} p
             LEFT JOIN models m ON p.model_id = m.id
             LEFT JOIN brands b ON m.brand_id = b.id
             WHERE b.id = ?",
            [$brandId]
        );
    }

    public function countSearch($query) {
        $searchTerm = "%$query%";
        return (int)$this->getValue(
            "SELECT COUNT(*) FROM {$this->table} WHERE name LIKE ? OR description LIKE ?",
            [$searchTerm, $searchTerm]
        );
    }
}

?>
