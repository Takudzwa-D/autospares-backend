<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaynowGateway;

/**
 * PaymentController
 * Handles payment processing and tracking
 */
class PaymentController extends Controller {
    private function getOwnedOrderOrFail(int $orderId) {
        $orderModel = new Order();
        $order = $orderModel->findById($orderId);

        if (!$order) {
            $this->notFound('Order not found');
        }

        $orderUserId = (int)($order['user_id'] ?? 0);
        $currentUserId = (int)$this->userId();
        $currentRole = $this->user['role'] ?? $this->user['Role'] ?? null;
        if ($orderUserId !== $currentUserId && $currentRole !== USER_ROLE_ADMIN) {
            $this->forbidden('You cannot access this order payment');
        }

        return $order;
    }

    private function updatePaymentFromGateway(Payment $paymentModel, Order $orderModel, array $payment, array $gatewayData) {
        $paynowStatus = $gatewayData['status'] ?? null;
        $mappedStatus = PaynowGateway::mapStatus($paynowStatus);

        $updatePayload = [
            'payment_status' => $mappedStatus,
            'paynow_reference' => $gatewayData['paynowreference'] ?? $gatewayData['paynowReference'] ?? null,
            'poll_url' => $gatewayData['pollurl'] ?? $gatewayData['pollUrl'] ?? null,
            'browser_url' => $gatewayData['browserurl'] ?? $gatewayData['browserUrl'] ?? null,
            'payment_details' => json_encode($gatewayData),
        ];

        if ($mappedStatus === PAYMENT_STATUS_COMPLETED) {
            $updatePayload['paid_at'] = date('Y-m-d H:i:s');
        }

        $paymentModel->updatePayment((int)$payment['id'], $updatePayload);

        if ($mappedStatus === PAYMENT_STATUS_COMPLETED) {
            $orderModel->updateStatus((int)$payment['order_id'], ORDER_STATUS_COMPLETED);
        } elseif ($mappedStatus === PAYMENT_STATUS_FAILED) {
            $orderModel->updateStatus((int)$payment['order_id'], ORDER_STATUS_FAILED);
        }
    }

    /**
     * GET /api/payments/status
     */
    public function status() {
        $this->requireAuth();

        try {
            $orderId = $this->input('orderId');

            if (!$orderId) {
                $this->error('Order ID is required', null, 400);
            }

            $order = $this->getOwnedOrderOrFail((int)$orderId);

            $paymentModel = new Payment();
            $payment = $paymentModel->findByOrderId((int)$orderId);

            if (!$payment) {
                $this->notFound('Payment not found for this order');
            }

            if (($this->input('refresh') === '1' || $this->input('refresh') === 1) && !empty($payment['poll_url'])) {
                $gateway = new PaynowGateway();
                if ($gateway->isConfigured()) {
                    $gatewayData = $gateway->poll($payment['poll_url']);
                    $orderModel = new Order();
                    $this->updatePaymentFromGateway($paymentModel, $orderModel, $payment, $gatewayData);
                    $payment = $paymentModel->findById((int)$payment['id']);
                }
            }

            $this->success('Payment status retrieved', [
                'payment' => $payment,
                'orderId' => (int)$order['id'],
                'gateway' => 'PayNow',
                'autoManaged' => true,
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Payment status failed: ' . $e->getMessage());
            $this->error('Failed to retrieve payment status', null, 500);
        }
    }

    /**
     * POST /api/payments/initiate
     */
    public function initiate() {
        $this->requireAuth();

        try {
            $input = $this->allInput();

            $this->validate([
                'orderId' => 'required|integer',
            ]);

            $order = $this->getOwnedOrderOrFail((int)$input['orderId']);
            $paymentModel = new Payment();
            $existingPayment = $paymentModel->findByOrderId((int)$input['orderId']);

            if ($existingPayment && ($existingPayment['payment_status'] ?? '') === PAYMENT_STATUS_COMPLETED) {
                $this->conflict('Payment already completed for this order');
            }

            $requestedMethod = strtolower(trim((string)($input['paymentMethod'] ?? 'paynow')));
            if (!in_array($requestedMethod, ['paynow', 'mobile_money'], true)) {
                $this->error('Payments are processed through PayNow only', null, 400);
            }

            $gateway = new PaynowGateway();
            if (!$gateway->isConfigured()) {
                $this->error('PayNow is not configured on the server. Add PAYNOW_INTEGRATION_ID and PAYNOW_INTEGRATION_KEY first.', null, 503);
            }

            $merchantReference = PaynowGateway::buildMerchantReference((int)$order['id'], (int)($existingPayment['id'] ?? 1));
            $paymentData = [
                'order_id' => (int)$input['orderId'],
                'payment_method' => 'PayNow',
                'payment_status' => PAYMENT_STATUS_PENDING,
                'merchant_reference' => $merchantReference,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existingPayment) {
                $paymentModel->updatePayment((int)$existingPayment['id'], $paymentData);
                $payment = $paymentModel->findById((int)$existingPayment['id']);
            } else {
                $paymentId = $paymentModel->create($paymentData);
                $payment = $paymentModel->findById((int)$paymentId);
            }

            $merchantReference = PaynowGateway::buildMerchantReference((int)$order['id'], (int)$payment['id']);
            $paymentModel->updatePayment((int)$payment['id'], ['merchant_reference' => $merchantReference]);

            $gatewayResponse = $gateway->initiate([
                'reference' => $merchantReference,
                'amount' => (float)($order['total_amount'] ?? 0),
                'email' => $this->user['email'] ?? $this->user['Email'] ?? null,
                'phone' => $order['customer_phone_number'] ?? null,
                'name' => $order['customer_name'] ?? null,
                'returnUrl' => PAYNOW_RETURN_URL . '?orderId=' . (int)$order['id'] . '&reference=' . urlencode($merchantReference),
                'resultUrl' => PAYNOW_RESULT_URL,
                'additionalInfo' => 'AutoSpares order #' . (int)$order['id'],
                'merchantTrace' => substr('ASPAY-' . (int)$order['id'] . '-' . (int)$payment['id'], 0, 32),
            ]);

            $paymentModel->updatePayment((int)$payment['id'], [
                'browser_url' => $gatewayResponse['browserurl'] ?? null,
                'poll_url' => $gatewayResponse['pollurl'] ?? null,
                'paynow_reference' => $gatewayResponse['paynowreference'] ?? null,
                'payment_details' => json_encode($gatewayResponse),
            ]);

            $payment = $paymentModel->findById((int)$payment['id']);

            $this->created('PayNow payment initiated', [
                'payment' => $payment,
                'gateway' => 'PayNow',
                'redirectUrl' => $gatewayResponse['browserurl'] ?? null,
                'pollUrl' => $gatewayResponse['pollurl'] ?? null,
                'reference' => $merchantReference,
                'autoRedirect' => !empty($gatewayResponse['browserurl']),
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Payment initiate failed: ' . $e->getMessage());
            $this->error(APP_DEBUG ? ('Failed to initiate payment: ' . $e->getMessage()) : 'Failed to initiate payment', null, 500);
        }
    }

    /**
     * POST /api/payments/verify
     */
    public function verify() {
        $this->requireAuth();

        try {
            $input = $this->allInput();

            $this->validate([
                'orderId' => 'required|integer',
            ]);

            $this->getOwnedOrderOrFail((int)$input['orderId']);

            $paymentModel = new Payment();
            $payment = $paymentModel->findByOrderId((int)$input['orderId']);

            if (!$payment) {
                $this->notFound('Payment not found');
            }

            if (empty($payment['poll_url'])) {
                $this->error('This payment does not have a PayNow poll URL yet', null, 409);
            }

            $gateway = new PaynowGateway();
            if (!$gateway->isConfigured()) {
                $this->error('PayNow is not configured on the server', null, 503);
            }

            $gatewayData = $gateway->poll($payment['poll_url']);
            $orderModel = new Order();
            $this->updatePaymentFromGateway($paymentModel, $orderModel, $payment, $gatewayData);

            $payment = $paymentModel->findById((int)$payment['id']);
            $this->success('Payment refreshed from PayNow', [
                'payment' => $payment,
                'gatewayData' => $gatewayData,
            ]);
        } catch (\Exception $e) {
            $this->log('error', 'Payment verify failed: ' . $e->getMessage());
            $this->error(APP_DEBUG ? ('Failed to verify payment: ' . $e->getMessage()) : 'Failed to verify payment', null, 500);
        }
    }

    /**
     * GET /api/payments
     */
    public function index() {
        $this->requireAdmin();

        try {
            $page = (int)($this->input('page') ?? 1);
            $perPage = (int)($this->input('perPage') ?? 15);
            $status = $this->input('status');

            $paymentModel = new Payment();
            $offset = ($page - 1) * $perPage;

            if ($status) {
                $data = $paymentModel->getByStatus($status, $perPage, $offset);
                $total = $paymentModel->countByStatus($status);
            } else {
                $data = $paymentModel->getAll($perPage, $offset);
                $total = $paymentModel->count();
            }

            $this->paginated($data, $total, $page, $perPage, 'Payments retrieved');
        } catch (\Exception $e) {
            $this->log('error', 'Payment index failed: ' . $e->getMessage());
            $this->error('Failed to retrieve payments', null, 500);
        }
    }

    /**
     * GET /api/payments/stats
     */
    public function stats() {
        $this->requireAdmin();

        try {
            $days = (int)($this->input('days') ?? 30);

            $paymentModel = new Payment();
            $stats = $paymentModel->getStats($days);

            $this->success('Payment statistics', $stats);
        } catch (\Exception $e) {
            $this->log('error', 'Payment stats failed: ' . $e->getMessage());
            $this->error('Failed to retrieve statistics', null, 500);
        }
    }

    /**
     * POST /api/payments/webhook
     */
    public function webhook() {
        try {
            $input = $this->request->getForm();
            if (empty($input)) {
                $input = $this->request->getJsonBody() ?? [];
            }
            if (empty($input)) {
                $input = $this->request->getQuery();
            }
            $reference = $input['reference'] ?? $this->input('reference');
            if (empty($reference)) {
                $this->error('Missing PayNow reference', null, 400);
            }

            $gateway = new PaynowGateway();
            if (!$gateway->verifyHash($input)) {
                $this->error('Invalid PayNow hash', null, 400);
            }

            $paymentModel = new Payment();
            $payment = $paymentModel->findByMerchantReference($reference);

            if (!$payment) {
                $this->notFound('Payment not found');
            }

            $orderModel = new Order();
            $this->updatePaymentFromGateway($paymentModel, $orderModel, $payment, $input);

            $this->log('info', 'PayNow webhook processed for reference: ' . $reference);
            $this->success('PayNow webhook processed');
        } catch (\Exception $e) {
            $this->log('error', 'Payment webhook failed: ' . $e->getMessage());
            $this->error(APP_DEBUG ? ('Webhook processing failed: ' . $e->getMessage()) : 'Webhook processing failed', null, 500);
        }
    }

    /**
     * GET|POST /api/payments/result
     */
    public function result() {
        return $this->webhook();
    }
}
