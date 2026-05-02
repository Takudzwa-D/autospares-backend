<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\SubCategory;

class SubCategoryController extends Controller {
    public function index() {
        try {
            $categoryId = $this->input('categoryId');
            $subCategoryModel = new SubCategory();

            if (!empty($categoryId)) {
                $items = $subCategoryModel->getByCategoryId((int)$categoryId);
            } else {
                $items = $subCategoryModel->getAllDetailed();
            }

            $this->success('Sub-categories retrieved', $items);
        } catch (\Exception $e) {
            $this->log('error', 'Sub-category index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve sub-categories', null, 500);
        }
    }

    public function show($id) {
        try {
            $subCategoryModel = new SubCategory();
            $item = $subCategoryModel->findDetailedById((int)$id);

            if (!$item) {
                $this->notFound('Sub-category not found');
            }

            $this->success('Sub-category retrieved', $item);
        } catch (\Exception $e) {
            $this->log('error', 'Sub-category show failed: ' . $e->getMessage());
            $this->error('Failed to retrieve sub-category', null, 500);
        }
    }

    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'categoryId' => 'required|integer',
                'name' => 'required|min:2',
            ]);

            $input = $this->allInput();
            $categoryModel = new Category();
            if (!$categoryModel->findById((int)$input['categoryId'])) {
                $this->notFound('Category not found');
            }

            $subCategoryModel = new SubCategory();
            $name = trim($input['name']);
            if ($subCategoryModel->findByName($name)) {
                $this->conflict('Sub-category name already exists');
            }

            $id = $subCategoryModel->create([
                'category_id' => (int)$input['categoryId'],
                'name' => $name,
                'description' => $input['description'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->created('Sub-category created successfully', $subCategoryModel->findDetailedById($id));
        } catch (\Exception $e) {
            $this->log('error', 'Sub-category store failed: ' . $e->getMessage());
            $this->error('Failed to create sub-category', null, 500);
        }
    }

    public function update($id) {
        $this->requireAdmin();

        try {
            $subCategoryModel = new SubCategory();
            if (!$subCategoryModel->findById((int)$id)) {
                $this->notFound('Sub-category not found');
            }

            $input = $this->allInput();
            $updateData = [];

            if (isset($input['categoryId'])) {
                $categoryModel = new Category();
                if (!$categoryModel->findById((int)$input['categoryId'])) {
                    $this->notFound('Category not found');
                }
                $updateData['category_id'] = (int)$input['categoryId'];
            }

            if (isset($input['name'])) {
                $name = trim($input['name']);
                $existing = $subCategoryModel->findByName($name);
                if ($existing && (int)$existing['id'] !== (int)$id) {
                    $this->conflict('Sub-category name already exists');
                }
                $updateData['name'] = $name;
            }

            if (array_key_exists('description', $input)) {
                $updateData['description'] = $input['description'];
            }

            if (empty($updateData)) {
                $this->error('No update data provided', null, 400);
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $subCategoryModel->updateSubCategory((int)$id, $updateData);

            $this->success('Sub-category updated successfully', $subCategoryModel->findDetailedById((int)$id));
        } catch (\Exception $e) {
            $this->log('error', 'Sub-category update failed: ' . $e->getMessage());
            $this->error('Failed to update sub-category', null, 500);
        }
    }

    public function destroy($id) {
        $this->requireAdmin();

        try {
            $subCategoryModel = new SubCategory();
            if (!$subCategoryModel->findById((int)$id)) {
                $this->notFound('Sub-category not found');
            }

            if ($subCategoryModel->getProductCount((int)$id) > 0) {
                $this->conflict('Cannot delete a sub-category that still has products');
            }

            $subCategoryModel->deleteSubCategory((int)$id);
            $this->success('Sub-category deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'Sub-category destroy failed: ' . $e->getMessage());
            $this->error('Failed to delete sub-category', null, 500);
        }
    }
}
