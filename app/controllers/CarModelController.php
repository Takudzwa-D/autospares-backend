<?php

namespace App\Controllers;

use App\Models\Brand;
use App\Models\CarModel;

class CarModelController extends Controller {
    public function index() {
        try {
            $brandId = $this->input('brandId');
            $model = new CarModel();

            if (!empty($brandId)) {
                $models = $model->getByBrandId((int)$brandId);
            } else {
                $models = $model->getAllWithBrandNames();
            }

            $this->success('Vehicle models retrieved', $models);
        } catch (\Exception $e) {
            $this->log('error', 'Car model index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve vehicle models', null, 500);
        }
    }

    public function show($id) {
        try {
            $model = new CarModel();
            $item = $model->findDetailedById((int)$id);

            if (!$item) {
                $this->notFound('Vehicle model not found');
            }

            $this->success('Vehicle model retrieved', $item);
        } catch (\Exception $e) {
            $this->log('error', 'Car model show failed: ' . $e->getMessage());
            $this->error('Failed to retrieve vehicle model', null, 500);
        }
    }

    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'brandId' => 'required|integer',
                'name' => 'required|min:2',
            ]);

            $input = $this->allInput();
            $brandModel = new Brand();
            if (!$brandModel->findById((int)$input['brandId'])) {
                $this->notFound('Brand not found');
            }

            $model = new CarModel();
            $name = trim($input['name']);
            if ($model->findByName($name)) {
                $this->conflict('Vehicle model name already exists');
            }

            $id = $model->create([
                'brand_id' => (int)$input['brandId'],
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->created('Vehicle model created successfully', $model->findDetailedById($id));
        } catch (\Exception $e) {
            $this->log('error', 'Car model store failed: ' . $e->getMessage());
            $this->error('Failed to create vehicle model', null, 500);
        }
    }

    public function update($id) {
        $this->requireAdmin();

        try {
            $model = new CarModel();
            if (!$model->findById((int)$id)) {
                $this->notFound('Vehicle model not found');
            }

            $input = $this->allInput();
            $updateData = [];

            if (isset($input['brandId'])) {
                $brandModel = new Brand();
                if (!$brandModel->findById((int)$input['brandId'])) {
                    $this->notFound('Brand not found');
                }
                $updateData['brand_id'] = (int)$input['brandId'];
            }

            if (isset($input['name'])) {
                $name = trim($input['name']);
                $existing = $model->findByName($name);
                if ($existing && (int)$existing['id'] !== (int)$id) {
                    $this->conflict('Vehicle model name already exists');
                }
                $updateData['name'] = $name;
            }

            if (empty($updateData)) {
                $this->error('No update data provided', null, 400);
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $model->updateModel((int)$id, $updateData);

            $this->success('Vehicle model updated successfully', $model->findDetailedById((int)$id));
        } catch (\Exception $e) {
            $this->log('error', 'Car model update failed: ' . $e->getMessage());
            $this->error('Failed to update vehicle model', null, 500);
        }
    }

    public function destroy($id) {
        $this->requireAdmin();

        try {
            $model = new CarModel();
            if (!$model->findById((int)$id)) {
                $this->notFound('Vehicle model not found');
            }

            if ($model->getProductCount((int)$id) > 0) {
                $this->conflict('Cannot delete a vehicle model that still has products');
            }

            $model->deleteModel((int)$id);
            $this->success('Vehicle model deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'Car model destroy failed: ' . $e->getMessage());
            $this->error('Failed to delete vehicle model', null, 500);
        }
    }
}
