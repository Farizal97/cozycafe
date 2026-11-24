<?php

declare(strict_types=1);

final class CheckoutService
{
    private const MAX_CART_ITEMS = 50;
    private const MAX_ORDER_AMOUNT_CENTS = 999999999999;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paymentMethods(): array
    {
        return $this->pdo->query(
            'SELECT id, name, method_type, deadline_minutes, bank_name,
                    account_number, account_holder, instructions
             FROM payment_methods
             WHERE is_enabled = 1
             ORDER BY id ASC'
        )->fetchAll();
    }

    public function review(array $input): array
    {
        $checkout = $this->validatedCheckoutInput($input);
        $products = $this->loadProducts($checkout['cart'], false);
        $paymentMethod = $this->paymentMethod((int) $checkout['payment_method_id'], false);
        $booking = $this->booking(
            $checkout['booking_code'],
            $checkout['order_type'],
            $checkout['phone'],
            false
        );
        $quote = $this->quote(
            $checkout['cart'],
            $products,
            $checkout['voucher_code'],
            $checkout['phone'],
            false
        );

        return [
            'checkout' => $checkout,
            'items' => $quote['items'],
            'subtotal_cents' => $quote['subtotal_cents'],
            'discount_cents' => $quote['discount_cents'],
            'discount_label' => $quote['discount_label'],
            'voucher_code' => $checkout['voucher_code'],
            'tax_cents' => $quote['tax_cents'],
            'service_charge_cents' => $quote['service_charge_cents'],
            'total_cents' => $quote['total_cents'],
            'payment_method' => $paymentMethod,
            'booking' => $booking,
        ];
    }

    public function createOrder(array $input): array
    {
        $checkout = $this->validatedCheckoutInput($input);
        if (($input['confirm_order'] ?? null) !== '1') {
            throw new DomainException('Konfirmasi pesanan terlebih dahulu.');
        }

        $this->pdo->beginTransaction();
        try {
            $customerId = $this->upsertCustomer($checkout['customer_name'], $checkout['phone']);
            $this->lockCustomer($customerId);
            $this->assertNoActiveOrder($customerId, $checkout['order_type']);

            $products = $this->loadProducts($checkout['cart'], true);
            $paymentMethod = $this->paymentMethod((int) $checkout['payment_method_id'], true);
            $booking = $this->booking(
                $checkout['booking_code'],
                $checkout['order_type'],
                $checkout['phone'],
                true
            );
            $quote = $this->quote(
                $checkout['cart'],
                $products,
                $checkout['voucher_code'],
                $checkout['phone'],
                true
            );

            $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(9)));
            $deadline = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))
                ->modify('+' . (int) $paymentMethod['deadline_minutes'] . ' minutes')
                ->format('Y-m-d H:i:s');
            $order = $this->pdo->prepare(
                'INSERT INTO orders (
                    order_code, customer_id, order_type, booking_id, table_id,
                    subtotal, discount_amount, tax_amount, service_charge,
                    total_amount, payment_method_id, payment_status, order_status,
                    payment_deadline
                ) VALUES (
                    :order_code, :customer_id, :order_type, :booking_id, :table_id,
                    :subtotal, :discount_amount, :tax_amount, :service_charge,
                    :total_amount, :payment_method_id, \'unpaid\', \'pending\',
                    :payment_deadline
                )'
            );
            $order->execute([
                'order_code' => $orderCode,
                'customer_id' => $customerId,
                'order_type' => $checkout['order_type'],
                'booking_id' => $booking === null ? null : (int) $booking['id'],
                'table_id' => $booking === null ? null : (int) $booking['table_id'],
                'subtotal' => $this->centsToDecimal($quote['subtotal_cents']),
                'discount_amount' => $this->centsToDecimal($quote['discount_cents']),
                'tax_amount' => $this->centsToDecimal($quote['tax_cents']),
                'service_charge' => $this->centsToDecimal($quote['service_charge_cents']),
                'total_amount' => $this->centsToDecimal($quote['total_cents']),
                'payment_method_id' => (int) $paymentMethod['id'],
                'payment_deadline' => $deadline,
            ]);
            $orderId = (int) $this->pdo->lastInsertId();

            $orderItem = $this->pdo->prepare(
                'INSERT INTO order_items (
                    order_id, product_id, product_name_snapshot, price_snapshot, quantity, subtotal
                ) VALUES (
                    :order_id, :product_id, :product_name_snapshot, :price_snapshot, :quantity, :subtotal
                )'
            );
            $markHistorical = $this->pdo->prepare(
                'UPDATE products SET has_order_history = 1 WHERE id = :id'
            );
            foreach ($quote['items'] as $item) {
                $orderItem->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name_snapshot' => $item['name'],
                    'price_snapshot' => $this->centsToDecimal($item['price_cents']),
                    'quantity' => $item['quantity'],
                    'subtotal' => $this->centsToDecimal($item['subtotal_cents']),
                ]);
                $markHistorical->execute(['id' => $item['product_id']]);
            }

            $payment = $this->pdo->prepare(
                'INSERT INTO payments (order_id, payment_method_id, amount, status)
                 VALUES (:order_id, :payment_method_id, :amount, \'unpaid\')'
            );
            $payment->execute([
                'order_id' => $orderId,
                'payment_method_id' => (int) $paymentMethod['id'],
                'amount' => $this->centsToDecimal($quote['total_cents']),
            ]);

            if ($quote['voucher'] !== null) {
                $usage = $this->pdo->prepare(
                    'INSERT INTO voucher_usages (voucher_id, order_id, customer_id, phone_snapshot)
                     VALUES (:voucher_id, :order_id, :customer_id, :phone_snapshot)'
                );
                $usage->execute([
                    'voucher_id' => (int) $quote['voucher']['id'],
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'phone_snapshot' => $checkout['phone'],
                ]);
            }

            $history = $this->pdo->prepare(
                'INSERT INTO order_status_histories (order_id, old_status, new_status)
                 VALUES (:order_id, NULL, \'pending\')'
            );
            $history->execute(['order_id' => $orderId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        unset($_SESSION['checkout_draft']);
        $_SESSION['cart_clear_order_code'] = $orderCode;

        return ['order_code' => $orderCode];
    }

    public function orderCreated(string $orderCode): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.id, o.order_code, o.order_type, o.booking_id, t.table_number,
                    o.subtotal, o.discount_amount,
                    o.tax_amount, o.service_charge, o.total_amount, o.payment_status,
                    o.order_status, o.payment_deadline,
                    pm.name AS payment_method_name, pm.method_type,
                    pm.bank_name, pm.account_number, pm.account_holder, pm.instructions
             FROM orders o
             INNER JOIN payment_methods pm ON pm.id = o.payment_method_id
             LEFT JOIN tables t ON t.id = o.table_id
             WHERE o.order_code = :order_code
             LIMIT 1'
        );
        $statement->execute(['order_code' => $orderCode]);
        $order = $statement->fetch();
        if ($order === false) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT product_name_snapshot AS name, price_snapshot, quantity, subtotal
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $items->execute(['order_id' => $order['id']]);
        $order['items'] = $items->fetchAll();
        foreach (['subtotal', 'discount_amount', 'tax_amount', 'service_charge', 'total_amount'] as $field) {
            $order[$field . '_cents'] = $this->moneyToCents((string) $order[$field], 'Nilai pesanan');
        }
        foreach ($order['items'] as &$item) {
            $item['price_cents'] = $this->moneyToCents((string) $item['price_snapshot'], 'Harga pesanan');
            $item['subtotal_cents'] = $this->moneyToCents((string) $item['subtotal'], 'Subtotal item');
        }
        unset($item);

        return $order;
    }

    public function consumeCartClearFlag(string $orderCode): bool
    {
        if (($_SESSION['cart_clear_order_code'] ?? null) !== $orderCode) {
            return false;
        }

        unset($_SESSION['cart_clear_order_code']);
        return true;
    }

    private function validatedCheckoutInput(array $input): array
    {
        $name = $input['customer_name'] ?? '';
        $name = is_string($name) ? trim($name) : '';
        if (preg_match('/\A.{2,160}\z/us', $name) !== 1 || preg_match('/[\p{Cc}\p{Cf}]/u', $name) === 1) {
            throw new DomainException('Nama pelanggan wajib diisi (2–160 karakter).');
        }

        $phone = $input['phone'] ?? '';
        $phone = is_string($phone) ? trim($phone) : '';
        $phone = str_replace([' ', '-', '(', ')', '.'], '', $phone);
        if (preg_match('/\A\+?[0-9]{8,15}\z/', $phone) !== 1) {
            throw new DomainException('Nomor telepon harus berisi 8–15 digit.');
        }

        $orderType = $input['order_type'] ?? '';
        if (!is_string($orderType) || !in_array($orderType, ['dine_in', 'takeaway'], true)) {
            throw new DomainException('Pilih jenis pesanan yang valid.');
        }

        $paymentMethodId = filter_var($input['payment_method_id'] ?? null, FILTER_VALIDATE_INT);
        if ($paymentMethodId === false || $paymentMethodId < 1) {
            throw new DomainException('Pilih metode pembayaran yang valid.');
        }

        $voucherCode = $input['voucher_code'] ?? '';
        $voucherCode = is_string($voucherCode) ? strtoupper(trim($voucherCode)) : '';
        if (preg_match('/\A[\x20-\x7E]{0,64}\z/', $voucherCode) !== 1) {
            throw new DomainException('Kode voucher tidak valid.');
        }

        $bookingCode = $input['booking_code'] ?? '';
        $bookingCode = is_string($bookingCode) ? strtoupper(trim($bookingCode)) : '';
        if (preg_match('/\A[\x20-\x7E]{0,32}\z/', $bookingCode) !== 1) {
            throw new DomainException('Kode reservasi tidak valid.');
        }
        if ($bookingCode !== '' && $orderType !== 'dine_in') {
            throw new DomainException('Kode reservasi hanya berlaku untuk pesanan Dine-in.');
        }

        return [
            'customer_name' => $name,
            'phone' => $phone,
            'order_type' => $orderType,
            'payment_method_id' => (int) $paymentMethodId,
            'voucher_code' => $voucherCode,
            'booking_code' => $bookingCode,
            'cart' => $this->validatedCart($input['cart_json'] ?? null),
        ];
    }

    private function validatedCart(mixed $cartJson): array
    {
        if (!is_string($cartJson) || strlen($cartJson) > 16000) {
            throw new DomainException('Keranjang tidak valid. Muat ulang halaman dan coba kembali.');
        }

        try {
            $decoded = json_decode($cartJson, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new DomainException('Data keranjang tidak dapat dibaca. Muat ulang halaman dan coba kembali.');
        }

        if (!is_array($decoded) || !array_is_list($decoded)
            || count($decoded) < 1 || count($decoded) > self::MAX_CART_ITEMS) {
            throw new DomainException('Keranjang harus berisi 1–50 produk.');
        }

        $cart = [];
        $seenProducts = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                throw new DomainException('Data produk di keranjang tidak valid.');
            }
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
                throw new DomainException('Jumlah atau produk di keranjang tidak valid.');
            }
            if (isset($seenProducts[$productId])) {
                throw new DomainException('Produk duplikat ditemukan di keranjang. Muat ulang keranjang.');
            }
            $seenProducts[$productId] = true;
            $cart[] = ['product_id' => (int) $productId, 'quantity' => (int) $quantity];
        }

        return $cart;
    }

    private function loadProducts(array $cart, bool $lock): array
    {
        $placeholders = [];
        $parameters = [];
        foreach ($cart as $index => $item) {
            $key = 'product_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $item['product_id'];
        }

        $statement = $this->pdo->prepare(
            'SELECT p.id, p.name, p.price, p.stock, p.min_order_quantity,
                    p.max_order_quantity, p.availability_status,
                    p.is_active, c.is_active AS category_is_active
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             WHERE p.id IN (' . implode(', ', $placeholders) . ')
             ORDER BY p.id ASC' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute($parameters);
        $rows = [];
        foreach ($statement->fetchAll() as $row) {
            $rows[(int) $row['id']] = $row;
        }

        if (count($rows) !== count($cart)) {
            throw new DomainException('Satu atau beberapa produk di keranjang sudah tidak tersedia.');
        }

        foreach ($cart as $item) {
            $product = $rows[$item['product_id']];
            if ((int) $product['is_active'] !== 1
                || (int) $product['category_is_active'] !== 1
                || $product['availability_status'] !== 'available') {
                throw new DomainException('Produk ' . $product['name'] . ' tidak tersedia untuk dipesan.');
            }
            if ($item['quantity'] < (int) $product['min_order_quantity']
                || $item['quantity'] > (int) $product['max_order_quantity']) {
                throw new DomainException(
                    'Jumlah produk ' . $product['name'] . ' harus antara '
                    . (int) $product['min_order_quantity'] . ' dan '
                    . (int) $product['max_order_quantity'] . '.'
                );
            }
            if ($item['quantity'] > (int) $product['stock']) {
                throw new DomainException('Stok produk ' . $product['name'] . ' tidak mencukupi.');
            }
        }

        return $rows;
    }

    private function paymentMethod(int $paymentMethodId, bool $lock): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, method_type, deadline_minutes, bank_name,
                    account_number, account_holder, instructions
             FROM payment_methods
             WHERE id = :id AND is_enabled = 1
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $paymentMethodId]);
        $method = $statement->fetch();
        if ($method === false) {
            throw new DomainException('Metode pembayaran tidak tersedia. Pilih metode lain.');
        }

        return $method;
    }

    private function booking(string $bookingCode, string $orderType, string $phone, bool $lock): ?array
    {
        if ($bookingCode === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT b.id, b.status, b.booking_date, bt.table_id, t.table_number
             FROM bookings b
             INNER JOIN customers c ON c.id = b.customer_id
             LEFT JOIN booking_tables bt ON bt.booking_id = b.id
             LEFT JOIN tables t ON t.id = bt.table_id
             WHERE b.booking_code = :booking_code AND c.phone = :phone
             ORDER BY bt.id ASC
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['booking_code' => $bookingCode, 'phone' => $phone]);
        $booking = $statement->fetch();
        if ($booking === false
            || !in_array($booking['status'], ['confirmed', 'checked_in'], true)
            || $booking['booking_date'] !== date('Y-m-d')) {
            throw new DomainException('Reservasi tidak ditemukan, belum dikonfirmasi, atau bukan untuk hari ini.');
        }
        if ($booking['table_id'] === null) {
            throw new DomainException('Meja untuk reservasi ini belum ditetapkan. Hubungi admin CozyCafe.');
        }
        if ($orderType !== 'dine_in') {
            throw new DomainException('Reservasi hanya dapat digunakan untuk pesanan Dine-in.');
        }

        return $booking;
    }

    private function quote(
        array $cart,
        array $products,
        string $voucherCode,
        string $phone,
        bool $lockVoucher
    ): array {
        $items = [];
        $subtotalCents = 0;
        foreach ($cart as $cartItem) {
            $product = $products[$cartItem['product_id']];
            $priceCents = $this->moneyToCents((string) $product['price'], 'Harga produk');
            if ($priceCents > intdiv(self::MAX_ORDER_AMOUNT_CENTS, $cartItem['quantity'])) {
                throw new DomainException('Nilai pesanan melebihi batas transaksi.');
            }
            $itemSubtotal = $priceCents * $cartItem['quantity'];
            $subtotalCents = $this->checkedAdd($subtotalCents, $itemSubtotal);
            $items[] = [
                'product_id' => (int) $product['id'],
                'name' => $product['name'],
                'price_cents' => $priceCents,
                'quantity' => $cartItem['quantity'],
                'subtotal_cents' => $itemSubtotal,
            ];
        }

        $voucher = null;
        $discountCents = 0;
        $discountLabel = null;
        if ($voucherCode !== '') {
            $voucher = $this->voucher($voucherCode, $lockVoucher);
            $this->assertVoucherCanBeUsed($voucher, $subtotalCents, $phone);
            $discountCents = $this->discountAmount(
                $subtotalCents,
                $voucher['discount_type'],
                (string) $voucher['discount_value']
            );
            $discountLabel = 'Voucher ' . $voucher['code'];
        } else {
            [$discountCents, $discountLabel] = $this->bestPromotion($subtotalCents);
        }

        $discountCents = min($discountCents, $subtotalCents);
        $taxableCents = $subtotalCents - $discountCents;
        $settings = $this->pricingSettings();
        $taxCents = $settings['tax_enabled']
            ? $this->percentageAmount($taxableCents, $settings['tax_basis_points'])
            : 0;
        $serviceChargeCents = $settings['service_charge_enabled']
            ? $this->percentageAmount($taxableCents, $settings['service_basis_points'])
            : 0;
        $totalCents = $this->checkedAdd(
            $this->checkedAdd($taxableCents, $taxCents),
            $serviceChargeCents
        );

        return [
            'items' => $items,
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'discount_label' => $discountLabel,
            'voucher' => $voucher,
            'tax_cents' => $taxCents,
            'service_charge_cents' => $serviceChargeCents,
            'total_cents' => $totalCents,
        ];
    }

    private function voucher(string $code, bool $lock): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, code, discount_type, discount_value, minimum_purchase,
                    total_usage_limit, per_phone_limit
             FROM vouchers
             WHERE code = :code AND is_active = 1
               AND start_date <= NOW() AND end_date >= NOW()
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['code' => $code]);
        $voucher = $statement->fetch();
        if ($voucher === false) {
            throw new DomainException('Kode voucher tidak ditemukan atau sudah tidak berlaku.');
        }

        return $voucher;
    }

    private function assertVoucherCanBeUsed(array $voucher, int $subtotalCents, string $phone): void
    {
        $minimumCents = $this->moneyToCents((string) $voucher['minimum_purchase'], 'Minimum pembelian voucher');
        if ($subtotalCents < $minimumCents) {
            throw new DomainException(
                'Minimum pembelian untuk voucher ini adalah '
                . $this->formatCents($minimumCents) . '.'
            );
        }

        if ($voucher['total_usage_limit'] !== null) {
            $total = $this->pdo->prepare(
                'SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = :voucher_id'
            );
            $total->execute(['voucher_id' => $voucher['id']]);
            if ((int) $total->fetchColumn() >= (int) $voucher['total_usage_limit']) {
                throw new DomainException('Batas penggunaan voucher ini sudah tercapai.');
            }
        }

        if ($voucher['per_phone_limit'] !== null) {
            $perPhone = $this->pdo->prepare(
                'SELECT COUNT(*) FROM voucher_usages
                 WHERE voucher_id = :voucher_id AND phone_snapshot = :phone'
            );
            $perPhone->execute([
                'voucher_id' => $voucher['id'],
                'phone' => $phone,
            ]);
            if ((int) $perPhone->fetchColumn() >= (int) $voucher['per_phone_limit']) {
                throw new DomainException('Batas penggunaan voucher untuk nomor telepon ini sudah tercapai.');
            }
        }
    }

    private function bestPromotion(int $subtotalCents): array
    {
        $promotions = $this->pdo->query(
            'SELECT name, discount_type, discount_value, minimum_purchase
             FROM promotions
             WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()
             ORDER BY id ASC'
        )->fetchAll();

        $bestDiscount = 0;
        $bestName = null;
        foreach ($promotions as $promotion) {
            $minimumCents = $this->moneyToCents(
                (string) $promotion['minimum_purchase'],
                'Minimum pembelian promosi'
            );
            if ($subtotalCents < $minimumCents) {
                continue;
            }

            $discount = $this->discountAmount(
                $subtotalCents,
                $promotion['discount_type'],
                (string) $promotion['discount_value']
            );
            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $bestName = $promotion['name'];
            }
        }

        return [$bestDiscount, $bestName === null ? null : 'Promosi ' . $bestName];
    }

    private function pricingSettings(): array
    {
        $statement = $this->pdo->query(
            "SELECT setting_key, setting_value
             FROM cafe_settings
             WHERE setting_key IN (
                'tax_percentage', 'service_charge_percentage',
                'tax_enabled', 'service_charge_enabled'
             )"
        );
        $settings = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        if (!isset($settings['tax_percentage'], $settings['service_charge_percentage'])) {
            throw new RuntimeException('Pengaturan pajak dan biaya layanan belum lengkap.');
        }

        return [
            'tax_basis_points' => $this->percentageBasisPoints($settings['tax_percentage'], 'Pajak'),
            'service_basis_points' => $this->percentageBasisPoints(
                $settings['service_charge_percentage'],
                'Biaya layanan'
            ),
            'tax_enabled' => $this->settingEnabled($settings['tax_enabled'] ?? '1', 'Pajak'),
            'service_charge_enabled' => $this->settingEnabled(
                $settings['service_charge_enabled'] ?? '1',
                'Biaya layanan'
            ),
        ];
    }

    private function percentageBasisPoints(string $value, string $label): int
    {
        if (preg_match('/\A(?:100(?:\.0{1,2})?|[0-9]{1,2}(?:\.[0-9]{1,2})?)\z/', $value) !== 1) {
            throw new RuntimeException('Persentase ' . $label . ' pada pengaturan tidak valid.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function settingEnabled(string $value, string $label): bool
    {
        if (!in_array($value, ['0', '1'], true)) {
            throw new RuntimeException('Status pengaturan ' . $label . ' tidak valid.');
        }

        return $value === '1';
    }

    private function discountAmount(int $baseCents, string $type, string $value): int
    {
        if ($type === 'percentage') {
            return $this->percentageAmount($baseCents, $this->percentageBasisPoints($value, 'diskon'));
        }
        if ($type === 'fixed') {
            return min($baseCents, $this->moneyToCents($value, 'Diskon nominal'));
        }

        throw new RuntimeException('Jenis diskon pada database tidak valid.');
    }

    private function percentageAmount(int $baseCents, int $basisPoints): int
    {
        return intdiv(($baseCents * $basisPoints) + 5000, 10000);
    }

    private function moneyToCents(string $value, string $label): int
    {
        if (preg_match('/\A([0-9]{1,10})(?:\.([0-9]{1,2}))?\z/', $value, $parts) !== 1) {
            throw new RuntimeException($label . ' pada database tidak valid.');
        }
        $whole = (int) $parts[1];
        $fraction = (int) str_pad($parts[2] ?? '', 2, '0');
        $cents = ($whole * 100) + $fraction;
        if ($cents > self::MAX_ORDER_AMOUNT_CENTS) {
            throw new RuntimeException($label . ' pada database melebihi batas transaksi.');
        }

        return $cents;
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function formatCents(int $cents): string
    {
        $formatted = 'Rp' . number_format(intdiv($cents, 100), 0, ',', '.');
        $fraction = $cents % 100;

        return $fraction === 0 ? $formatted : $formatted . ',' . str_pad((string) $fraction, 2, '0', STR_PAD_LEFT);
    }

    private function checkedAdd(int $left, int $right): int
    {
        if ($right > self::MAX_ORDER_AMOUNT_CENTS - $left) {
            throw new DomainException('Nilai pesanan melebihi batas transaksi.');
        }

        return $left + $right;
    }

    private function upsertCustomer(string $name, string $phone): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO customers (name, phone)
             VALUES (:name, :phone)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), name = VALUES(name)'
        );
        $statement->execute(['name' => $name, 'phone' => $phone]);

        return (int) $this->pdo->lastInsertId();
    }

    private function lockCustomer(int $customerId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM customers WHERE id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $customerId]);
        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('Data pelanggan tidak dapat dikunci untuk checkout.');
        }
    }

    private function assertNoActiveOrder(int $customerId, string $orderType): void
    {
        $statement = $this->pdo->prepare(
            "SELECT id
             FROM orders
             WHERE customer_id = :customer_id
               AND order_type = :order_type
               AND order_status NOT IN ('completed', 'cancelled', 'expired')
             LIMIT 1
             FOR UPDATE"
        );
        $statement->execute([
            'customer_id' => $customerId,
            'order_type' => $orderType,
        ]);
        if ($statement->fetchColumn() !== false) {
            $typeLabel = $orderType === 'dine_in' ? 'Dine-in' : 'Takeaway';
            throw new DomainException('Nomor telepon ini masih memiliki pesanan ' . $typeLabel . ' aktif.');
        }
    }
}
