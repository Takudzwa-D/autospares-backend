<?php

namespace App\Models;

class Category extends Model {
    protected $table = 'Categories';

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

    public function getAllWithCounts() {
        return $this->getRows(
            "SELECT c.*, COUNT(p.id) AS product_count
             FROM {$this->table} c
             LEFT JOIN sub_categories sc ON sc.category_id = c.id
             LEFT JOIN products p ON p.sub_category_id = sc.id
             GROUP BY c.id
             ORDER BY c.name ASC"
        );
    }

    public function getProductCount($categoryId) {
        return (int)$this->getValue(
            "SELECT COUNT(p.id)
             FROM {$this->table} c
             LEFT JOIN sub_categories sc ON sc.category_id = c.id
             LEFT JOIN products p ON p.sub_category_id = sc.id
             WHERE c.id = ?",
            [$categoryId]
        );
    }

    public function search($query) {
        $searchTerm = "%$query%";
        return $this->getRows(
            "SELECT * FROM {$this->table}
             WHERE name LIKE ? OR description LIKE ?
             ORDER BY name ASC",
            [$searchTerm, $searchTerm]
        );
    }

    public function create($data) {
        $defaults = ['name' => '', 'description' => '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $data = array_merge($defaults, $data);

        // Normalize mismatched key casing from controllers.
        if (isset($data['Name'])) $data['name'] = $data['Name'];
        if (isset($data['Description'])) $data['description'] = $data['Description'];
        if (isset($data['CreatedAt'])) $data['created_at'] = $data['CreatedAt'];
        if (isset($data['UpdatedAt'])) $data['updated_at'] = $data['UpdatedAt'];

        unset($data['Name'], $data['Description'], $data['Icon'], $data['CreatedAt'], $data['UpdatedAt']);

        return $this->insert($data);
    }

    public function updateCategory($id, $data) {
        if (isset($data['Name'])) $data['name'] = $data['Name'];
        if (isset($data['Description'])) $data['description'] = $data['Description'];
        if (isset($data['UpdatedAt'])) $data['updated_at'] = $data['UpdatedAt'];

        unset($data['Name'], $data['Description'], $data['Icon'], $data['UpdatedAt']);
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($data, "id = ?", [$id]);
    }

    public function deleteCategory($id) {
        return $this->delete("id = ?", [$id]);
    }

    public function count($whereClause = '1=1', $whereValues = []) {
        return (int)$this->getValue("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $whereValues);
    }
}

?>
