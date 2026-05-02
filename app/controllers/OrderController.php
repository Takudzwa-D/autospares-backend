<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

/**
 * OrderController
 * Handles order creation and management
 */
class OrderController extends Controller {
    /**
     * GET /api/orders
     */
    public function index() {
        $this->requireAdmin();

        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? 15);

            $orderModel = new Order();
            $offset = ($page - 1) * $perPage;

            $data = $orderModel->getAll($perPage, $offset);
            $total = $orderModel->count();

            $this->paginated($data, $total, $page, $perPage, 'Orders retrieved');
        } catch (\Exception $e) {
            $this->log('error', 'Order index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve orders', null, 500);
        }
    }

    /**
     * GET /api/orders/my
     */
    public function myOrders() {
        $this->requireAuth();

        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? 15);

            $orderModel = new Order();
            $offset = ($page - 1) * $perPage;

            $data = $orderModel->getUserOrders($this->userId(), $perPage, $offset);
            $total = $orderModel->countByUser($this->userId());

            $this->paginated($data, $total, $page, $perPage, 'Your orders');
        } catch (\Exception $e) {
            $this->log('error', 'My orders failed: ' . $e->getMessage());
            $this->error('Failed to retrieve orders', null, 500);
        }
    }

    /**
     * GET /api/orders/:id
     */
    public function show($id) {
        $this->requireAuth();

        try {
            $id = (int)$id;
            $orderModel = new Order();
            $order = $orderModel->findById($id);

            if (!$order) {
                $this->notFound('Order not found');
            }

            // Check authorization
            $orderUserId = (int)($order['user_id'] ?? 0);
            $currentRole = $this->user['role'] ?? $this->user['Role'] ?? null;
            if ($orderUserId !== (int)$this->userId() && $currentRole !== 'Admin') {
                $this->forbidden('You cannot view this order');
            }

            // Include order items
            $orderItemModel = new OrderItem();
            $order['items'] = $orderItemModel->getByOrderId($id);

            $this->success('Order retrieved', $order);
        } catch (\Exception $e) {
            $this->log('error', 'Order show failed: ' . $e->getMessage());
            $this->error('Failed to retrieve order', null, 500);
        }
    }

    /**
     * POST /api/orders
     */
    public function store() {
        $this->requireAuth();

        try {
            $input = $this->allInput();

            if (empty($input['items']) || !is_array($input['items'])) {
                $this->error('Order must contain at least one item', null, 400);
            }

            $productModel = new Product();
            $orderModel = new Order();
            $orderItemModel = new OrderItem();

            // Validate products and calculate total
            $totalAmount = 0;
            $orderItems = [];

            foreach ($input['items'] as $item) {
                if (empty($item['productId']) || empty($item['quantity'])) {
                    $this->error('Invalid item data', null, 400);
                }

                $productId = (int)$item['productId'];
                $quantity = (int)$item['quantity'];

                $product = $productModel->findById($productId);
                if (!$product) {
                    $this->error('Product not found: ' . $productId, null, 404);
                }

                // Check stock
                $available = (int)($product['stock_quantity'] ?? 0);
                if ($quantity > $available) {
                    $productName = $product['name'] ?? ('Product #' . $productId);
                    $this->error('Insufficient stock for: ' . $productName . '. Available: ' . $available, null, 409);
                }

                $unitPrice = (float)($product['price'] ?? 0);
                $itemTotal = $unitPrice * $quantity;
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'productId' => $productId,
                    'quantity' => $quantity,
                    'unitPrice' => $unitPrice,
                    'product' => $product,
                ];
            }

            // Start transaction
            try {
                $orderModel->beginTransaction();

                // Create order
                $orderId = $orderModel->create([
                    'user_id' => $this->userId(),
                    'customer_name' => $input['customerName'] ?? trim(($this->user['FirstName'] ?? '') . ' ' . ($this->user['LastName'] ?? '')),
                    'customer_phone_number' => $input['customerPhone'] ?? ($this->user['PhoneNumber'] ?? ''),
                    'customer_address' => $input['customerAddress'] ?? ($this->user['Address'] ?? ''),
                    'total_amount' => $totalAmount,
                    'status' => ORDER_STATUS_PENDING,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                if (!$orderId) {
                    throw new \Exception('Failed to create order');
                }

                // Create order items and decrease stock
                foreach ($orderItems as $item) {
                    $orderItemModel->create([
                        'order_id' => $orderId,
                        'product_id' => $item['productId'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unitPrice'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Decrease stock
                    $productModel->updateStock($item['productId'], -$item['quantity']);
                }

                $orderModel->commit();

                $order = $orderModel->findById($orderId);
                $this->created('Order created successfully', $order);
            } catch (\Exception $e) {
                $orderModel->rollback();
                $this->log('error', 'Order transaction failed: ' . $e->getMessage());
                $this->error('Failed to create order', null, 500);
            }
        } catch (\Exception $e) {
            $this->log('error', 'Order store failed: ' . $e->getMessage());
            $this->error('Failed to create order', null, 500);
        }
    }

    /**
     * PUT /api/orders/:id/status
     */
    public function updateStatus($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $input = $this->allInput();

            if (empty($input['status'])) {
                $this->error('Status is required', null, 400);
            }

            if (!in_array($input['status'], VALID_ORDER_STATUSES, true)) {
                $this->error('Invalid order status', null, 400);
            }

            $orderModel = new Order();
            if (!$orderModel->findById($id)) {
                $this->notFound('Order not found');
            }

            $orderModel->updateStatus($id, $input['status']);
            $order = $orderModel->findById($id);

            $this->success('Order status updated', $order);
        } catch (\Exception $e) {
            $this->log('error', 'Order status update failed: ' . $e->getMessage());
            $this->error('Failed to update order status', null, 500);
        }
    }

    /**
     * DELETE /api/orders/:id
     */
    public function destroy($id) {
        $this->requireAdmin();

        try {
            $id = (int)$id;
            $orderModel = new Order();
            $order = $orderModel->findById($id);

            if (!$order) {
                $this->notFound('Order not found');
            }

            $orderItemModel = new OrderItem();
            $productModel = new Product();
            $items = $orderItemModel->getByOrderId($id);

            $orderModel->beginTransaction();

            foreach ($items as $item) {
                $productModel->updateStock((int)$item['product_id'], (int)$item['quantity']);
            }

            $orderModel->deleteOrder($id);
            $orderModel->commit();

            $this->success('Order deleted successfully');
        } catch (\Exception $e) {
            if (isset($orderModel)) {
                $orderModel->rollback();
            }
            $this->log('error', 'Order delete failed: ' . $e->getMessage());
            $this->error('Failed to delete order', null, 500);
        }
    }

    /**
     * GET /api/orders/stats
     */
    public function stats() {
        $this->requireAdmin();

        try {
            $orderModel = new Order();
            $stats = $orderModel->getStats();

            $this->success('Order statistics', $stats);
        } catch (\Exception $e) {
            $this->log('error', 'Order stats failed: ' . $e->getMessage());
            $this->error('Failed to retrieve statistics', null, 500);
        }
    }

}
