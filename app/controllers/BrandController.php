<?php

namespace App\Controllers;

use App\Models\Brand;

class BrandController extends Controller {
    public function index() {
        try {
            $brandModel = new Brand();
            $brands = $brandModel->getAllWithModelCounts();
            $this->success('Brands retrieved', $brands);
        } catch (\Exception $e) {
            $this->log('error', 'Brand index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve brands', null, 500);
        }
    }

    public function show($id) {
        try {
            $brandModel = new Brand();
            $brand = $brandModel->findById((int)$id);

            if (!$brand) {
                $this->notFound('Brand not found');
            }

            $this->success('Brand retrieved', $brand);
        } catch (\Exception $e) {
            $this->log('error', 'Brand show failed: ' . $e->getMessage());
            $this->error('Failed to retrieve brand', null, 500);
        }
    }

    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'name' => 'required|min:2',
            ]);

            $input = $this->allInput();
            $brandModel = new Brand();
            $name = trim($input['name']);

            if ($brandModel->findByName($name)) {
                $this->conflict('Brand name already exists');
            }

            $brandId = $brandModel->create([
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->created('Brand created successfully', $brandModel->findById($brandId));
        } catch (\Exception $e) {
            $this->log('error', 'Brand store failed: ' . $e->getMessage());
            $this->error('Failed to create brand', null, 500);
        }
    }

    public function update($id) {
        $this->requireAdmin();

        try {
            $brandModel = new Brand();
            $brand = $brandModel->findById((int)$id);

            if (!$brand) {
                $this->notFound('Brand not found');
            }

            $input = $this->allInput();
            $updateData = [];

            if (isset($input['name'])) {
                $name = trim($input['name']);
                $existing = $brandModel->findByName($name);
                if ($existing && (int)$existing['id'] !== (int)$id) {
                    $this->conflict('Brand name already exists');
                }
                $updateData['name'] = $name;
            }

            if (empty($updateData)) {
                $this->error('No update data provided', null, 400);
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $brandModel->updateBrand((int)$id, $updateData);

            $this->success('Brand updated successfully', $brandModel->findById((int)$id));
        } catch (\Exception $e) {
            $this->log('error', 'Brand update failed: ' . $e->getMessage());
            $this->error('Failed to update brand', null, 500);
        }
    }

    public function destroy($id) {
        $this->requireAdmin();

        try {
            $brandModel = new Brand();
            $brand = $brandModel->findById((int)$id);

            if (!$brand) {
                $this->notFound('Brand not found');
            }

            if ($brandModel->getModelCount((int)$id) > 0) {
                $this->conflict('Cannot delete a brand that still has models');
            }

            $brandModel->deleteBrand((int)$id);
            $this->success('Brand deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'Brand destroy failed: ' . $e->getMessage());
            $this->error('Failed to delete brand', null, 500);
        }
    }
}
