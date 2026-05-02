<?php

namespace App\Controllers;

use App\Models\Category;

/**
 * CategoryController
 * Handles category management
 */
class CategoryController extends Controller {
    /**
     * GET /api/categories
     */
    public function index() {
        try {
            $categoryModel = new Category();
            $categories = $categoryModel->getAllWithCounts();

            $this->success('Categories retrieved', $categories);
        } catch (\Exception $e) {
            $this->log('error', 'Category index failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Failed to retrieve categories: ' . $e->getMessage()) : 'Failed to retrieve categories';
            $this->error($message, null, 500);
        }
    }

    /**
     * GET /api/categories/:id
     */
    public function show($id) {
        try {
            $id = (int)$id;
            $categoryModel = new Category();
            $category = $categoryModel->findById($id);

            if (!$category) {
                $this->notFound('Category not found');
            }

            // Include product count
            $productCount = $categoryModel->getProductCount($id);
            $category['productCount'] = $productCount;

            $this->success('Category retrieved', $category);
        } catch (\Exception $e) {
            $this->log('error', 'Category show failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Failed to retrieve category: ' . $e->getMessage()) : 'Failed to retrieve category';
            $this->error($message, null, 500);
        }
    }

    /**
     * GET /api/categories/search
     */
    public function search() {
        try {
            $query = $this->input('q');

            if (!$query || strlen($query) < 2) {
                $this->error('Search query too short', null, 400);
            }

            $categoryModel = new Category();
            $categories = $categoryModel->search($query);

            $this->success('Search results', $categories);
        } catch (\Exception $e) {
            $this->log('error', 'Category search failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Search failed: ' . $e->getMessage()) : 'Search failed';
            $this->error($message, null, 500);
        }
    }

    /**
     * POST /api/categories
     */
    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'name' => 'required|min:2',
            ]);

            $input = $this->allInput();
            $categoryModel = new Category();

            // Check name uniqueness
            if ($categoryModel->findByName($input['name'])) {
                $this->conflict('Category name already exists');
            }

            $categoryId = $categoryModel->create([
                'Name' => trim($input['name']),
                'Description' => $input['description'] ?? null,
                'Icon' => $input['icon'] ?? null,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'UpdatedAt' => date('Y-m-d H:i:s'),
            ]);

            if (!$categoryId) {
                $this->error('Failed to create category', null, 500);
            }

            $category = $categoryModel->findById($categoryId);
            $this->created('Category created successfully', $category);
        } catch (\Exception $e) {
            $this->log('error', 'Category store failed: ' . $e->getMessage());
            $this->error('Failed to create category', null, 500);
        }
    }

    /**
     * PUT /api/categories/:id
     */
    public function update($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $input = $this->allInput();
            $categoryModel = new Category();

            if (!$categoryModel->findById($id)) {
                $this->notFound('Category not found');
            }

            $updateData = [];
            if (isset($input['name'])) {
                // Check uniqueness excluding current category
                $existing = $categoryModel->findByName($input['name']);
                if ($existing && (int)$existing['id'] !== $id) {
                    $this->conflict('Category name already exists');
                }
                $updateData['Name'] = $input['name'];
            }
            if (isset($input['description'])) $updateData['Description'] = $input['description'];
            if (isset($input['icon'])) $updateData['Icon'] = $input['icon'];

            $updateData['UpdatedAt'] = date('Y-m-d H:i:s');

            $categoryModel->updateCategory($id, $updateData);
            $category = $categoryModel->findById($id);

            $this->success('Category updated successfully', $category);
        } catch (\Exception $e) {
            $this->log('error', 'Category update failed: ' . $e->getMessage());
            $this->error('Failed to update category', null, 500);
        }
    }

    /**
     * DELETE /api/categories/:id
     */
    public function destroy($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $categoryModel = new Category();

            if (!$categoryModel->findById($id)) {
                $this->notFound('Category not found');
            }

            // Check if category has products
            $productCount = $categoryModel->getProductCount($id);
            if ($productCount > 0) {
                $this->conflict('Cannot delete category with products. Delete/move products first.');
            }

            $categoryModel->deleteCategory($id);
            $this->success('Category deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'Category destroy failed: ' . $e->getMessage());
            $this->error('Failed to delete category', null, 500);
        }
    }
}
