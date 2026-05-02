<?php

namespace App\Controllers;

use App\Models\Product;

/**
 * ProductController
 * Handles product CRUD operations
 */
class ProductController extends Controller {
    /**
     * GET /api/products
     */
    public function index() {
        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? $this->input('limit') ?? 15);
            $categoryId = $this->input('categoryId');
            $subCategoryId = $this->input('subCategoryId');
            $brandId = $this->input('brandId');
            $modelId = $this->input('modelId');

            $productModel = new Product();
            
            $offset = ($page - 1) * $perPage;
            if (!empty($modelId)) {
                $data = $productModel->getByModelId((int)$modelId, $perPage, $offset);
                $total = $productModel->countByModelId((int)$modelId);
            } elseif (!empty($brandId)) {
                $data = $productModel->getByBrandId((int)$brandId, $perPage, $offset);
                $total = $productModel->countByBrandId((int)$brandId);
            } elseif (!empty($subCategoryId)) {
                $data = $productModel->getBySubCategoryId((int)$subCategoryId, $perPage, $offset);
                $total = $productModel->countBySubCategoryId((int)$subCategoryId);
            } elseif (!empty($categoryId)) {
                $data = $productModel->getByCategoryId((int)$categoryId, $perPage, $offset);
                $total = $productModel->countByCategoryId((int)$categoryId);
            } else {
                $data = $productModel->getAll($perPage, $offset);
                $total = $productModel->count();
            }

            $this->paginated($data, $total, $page, $perPage, 'Products retrieved');
        } catch (\Exception $e) {
            $this->log('error', 'Product index failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Failed to retrieve products: ' . $e->getMessage()) : 'Failed to retrieve products';
            $this->error($message, null, 500);
        }
    }

    /**
     * GET /api/products/:id
     */
    public function show($id) {
        try {
            $id = (int)$id;
            $productModel = new Product();
            $product = $productModel->findById($id);

            if (!$product) {
                $this->notFound('Product not found');
            }

            $this->success('Product retrieved', $product);
        } catch (\Exception $e) {
            $this->log('error', 'Product show failed: ' . $e->getMessage());
            $this->error('Failed to retrieve product', null, 500);
        }
    }

    /**
     * GET /api/products/search
     */
    public function search() {
        try {
            $query = $this->input('q');
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? $this->input('limit') ?? 15);

            if (!$query || strlen($query) < 2) {
                $this->error('Search query too short (minimum 2 characters)', null, 400);
            }

            $productModel = new Product();
            $offset = ($page - 1) * $perPage;
            $data = $productModel->search($query, $perPage, $offset);
            $total = $productModel->countSearch($query);

            $this->paginated($data, $total, $page, $perPage, 'Search results');
        } catch (\Exception $e) {
            $this->log('error', 'Product search failed: ' . $e->getMessage());
            $message = APP_DEBUG ? ('Search failed: ' . $e->getMessage()) : 'Search failed';
            $this->error($message, null, 500);
        }
    }

    /**
     * POST /api/products
     */
    public function store() {
        $this->requireAdmin();

        try {
            $this->validate([
                'name' => 'required|min:3',
                'price' => 'required|numeric',
                'stockQuantity' => 'required|integer',
                'subCategoryId' => 'required|integer',
                'modelId' => 'required|integer',
            ]);

            $input = $this->allInput();
            $productModel = new Product();

            $productId = $productModel->create([
                'sub_category_id' => (int)$input['subCategoryId'],
                'model_id' => (int)$input['modelId'],
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'price' => (float)$input['price'],
                'stock_quantity' => (int)$input['stockQuantity'],
                'img' => $input['image'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$productId) {
                $this->error('Failed to create product', null, 500);
            }

            $product = $productModel->findById($productId);
            $this->created('Product created successfully', $product);
        } catch (\Exception $e) {
            $this->log('error', 'Product store failed: ' . $e->getMessage());
            $this->error('Failed to create product', null, 500);
        }
    }

    /**
     * PUT /api/products/:id
     */
    public function update($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $input = $this->allInput();
            $productModel = new Product();

            if (!$productModel->findById($id)) {
                $this->notFound('Product not found');
            }

            $updateData = [];
            if (isset($input['name'])) $updateData['name'] = $input['name'];
            if (isset($input['description'])) $updateData['description'] = $input['description'];
            if (isset($input['price'])) $updateData['price'] = (float)$input['price'];
            if (isset($input['stockQuantity'])) $updateData['stock_quantity'] = (int)$input['stockQuantity'];
            if (isset($input['subCategoryId'])) $updateData['sub_category_id'] = (int)$input['subCategoryId'];
            if (isset($input['modelId'])) $updateData['model_id'] = (int)$input['modelId'];
            if (isset($input['image'])) $updateData['img'] = $input['image'];

            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $productModel->updateProduct($id, $updateData);
            $product = $productModel->findById($id);

            $this->success('Product updated successfully', $product);
        } catch (\Exception $e) {
            $this->log('error', 'Product update failed: ' . $e->getMessage());
            $this->error('Failed to update product', null, 500);
        }
    }

    /**
     * DELETE /api/products/:id
     */
    public function destroy($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $productModel = new Product();

            if (!$productModel->findById($id)) {
                $this->notFound('Product not found');
            }

            $productModel->deleteProduct($id);
            $this->success('Product deleted successfully');
        } catch (\Exception $e) {
            $this->log('error', 'Product destroy failed: ' . $e->getMessage());
            $this->error('Failed to delete product', null, 500);
        }
    }

    /**
     * POST /api/products/:id/image
     */
    public function uploadImage($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $productModel = new Product();
            $product = $productModel->findById($id);

            if (!$product) {
                $this->notFound('Product not found');
            }

            if (!$this->request->hasFile('image')) {
                $this->error('Image file is required', null, 400);
            }

            $file = $this->request->file('image');
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                $this->error('Invalid uploaded file', null, 400);
            }

            if (($file['size'] ?? 0) > UPLOAD_MAX_SIZE) {
                $this->error('Image exceeds maximum upload size', null, 400);
            }

            $mimeType = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
            if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES, true)) {
                $this->error('Unsupported image type', null, 400);
            }

            $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            if (!in_array($extension, UPLOAD_ALLOWED_EXTENSIONS, true)) {
                $this->error('Unsupported image extension', null, 400);
            }

            $uploadDir = UPLOAD_DIR . '/products';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'product-' . $id . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $this->error('Failed to store uploaded image', null, 500);
            }

            $publicPath = '/uploads/products/' . $filename;
            $productModel->updateProduct($id, ['img' => $publicPath]);

            $this->success('Product image uploaded successfully', $productModel->findById($id));
        } catch (\Exception $e) {
            $this->log('error', 'Product image upload failed: ' . $e->getMessage());
            $this->error('Failed to upload product image', null, 500);
        }
    }
}
