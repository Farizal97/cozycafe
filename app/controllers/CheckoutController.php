<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/CheckoutService.php';

final class CheckoutController
{
    private readonly CheckoutService $checkout;

    public function __construct(PDO $pdo)
    {
        $this->checkout = new CheckoutService($pdo);
    }

    public function paymentMethods(): array
    {
        return $this->checkout->paymentMethods();
    }

    public function review(array $input): array
    {
        return $this->checkout->review($input);
    }

    public function createOrder(array $input): array
    {
        return $this->checkout->createOrder($input);
    }

    public function saveDraft(array $input): void
    {
        $draft = [];
        foreach (['customer_name', 'phone', 'order_type', 'payment_method_id', 'voucher_code', 'booking_code'] as $key) {
            if (isset($input[$key]) && is_string($input[$key])) {
                $draft[$key] = substr($input[$key], 0, 160);
            }
        }
        $_SESSION['checkout_draft'] = $draft;
    }

    public function consumeDraft(): array
    {
        $draft = $_SESSION['checkout_draft'] ?? [];
        unset($_SESSION['checkout_draft']);

        return is_array($draft) ? $draft : [];
    }

    public function orderCreated(string $orderCode): ?array
    {
        if (preg_match('/\AORD-[0-9]{8}-[A-F0-9]{18}\z/', $orderCode) !== 1) {
            return null;
        }

        return $this->checkout->orderCreated($orderCode);
    }

    public function consumeCartClearFlag(string $orderCode): bool
    {
        return $this->checkout->consumeCartClearFlag($orderCode);
    }
}
