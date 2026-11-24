window.CozyCafe = {
    formatRupiah: (amount) => 'Rp' + Number(amount).toLocaleString('id-ID')
};

(() => {
    const storageKey = 'cozycafe_cart';

    function readCart() {
        const rawCart = window.localStorage.getItem(storageKey);
        const cart = rawCart ? JSON.parse(rawCart) : [];
        if (!Array.isArray(cart)) {
            throw new TypeError('Format keranjang tidak valid.');
        }
        return cart;
    }

    function parseMoneyCents(value) {
        const match = /^([0-9]{1,10})(?:\.([0-9]{1,2}))?$/.exec(String(value));
        if (!match) {
            throw new TypeError('Harga produk tidak valid.');
        }
        const cents = (Number(match[1]) * 100) + Number((match[2] || '').padEnd(2, '0'));
        if (!Number.isSafeInteger(cents)) {
            throw new TypeError('Harga produk melebihi batas.');
        }
        return cents;
    }

    function formatCents(cents) {
        const whole = Math.floor(cents / 100).toLocaleString('id-ID');
        const fraction = cents % 100;
        return 'Rp' + whole + (fraction ? ',' + String(fraction).padStart(2, '0') : '');
    }

    function updateCartCount() {
        const badges = document.querySelectorAll('[data-cart-count]');
        if (badges.length === 0) {
            return;
        }

        const count = readCart().reduce((total, item) => {
            if (!item || typeof item !== 'object') {
                return total;
            }
            const quantity = Number(item.quantity);
            return total + (Number.isSafeInteger(quantity) && quantity > 0 ? quantity : 0);
        }, 0);

        badges.forEach((badge) => {
            badge.textContent = String(count > 99 ? '99+' : count);
            badge.hidden = count < 1;
        });
    }

    function showCartToast(message) {
        const toast = document.getElementById('cartToast');
        if (!toast) {
            return;
        }
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(showCartToast.timeout);
        showCartToast.timeout = window.setTimeout(() => toast.classList.remove('visible'), 2600);
    }

    function writeCart(cart) {
        window.localStorage.setItem(storageKey, JSON.stringify(cart));
        window.dispatchEvent(new CustomEvent('cozycafe:cart-updated'));
    }

    function validCartItem(item) {
        if (!item || typeof item !== 'object'
            || !Number.isSafeInteger(Number(item.product_id))
            || Number(item.product_id) < 1
            || typeof item.name !== 'string'
            || !Number.isSafeInteger(Number(item.quantity))
            || Number(item.quantity) < 1) {
            return false;
        }

        try {
            const priceCents = parseMoneyCents(item.price);
            if (!Number.isSafeInteger(priceCents * Number(item.quantity))) {
                return false;
            }
        } catch {
            return false;
        }

        return true;
    }

    function appendText(parent, tagName, className, text) {
        const element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        element.textContent = text;
        parent.appendChild(element);
        return element;
    }

    function renderCart() {
        const root = document.querySelector('[data-cart-app]');
        if (!root) {
            return;
        }

        const list = root.querySelector('[data-cart-items]');
        const emptyState = root.querySelector('[data-cart-empty]');
        const subtotalDisplay = root.querySelector('[data-cart-subtotal]');
        const totalDisplay = root.querySelector('[data-cart-total]');
        const checkoutLink = root.querySelector('[data-cart-checkout]');
        const cart = readCart();
        const invalidItems = cart.filter((item) => !validCartItem(item));
        list.replaceChildren();

        if (invalidItems.length > 0) {
            appendText(
                list,
                'p',
                'alert alert-warning',
                'Sebagian data keranjang tidak valid. Hapus item yang bermasalah atau kosongkan keranjang.'
            );
        }

        let subtotalCents = 0;
        const items = cart.filter(validCartItem);
        items.forEach((item) => {
            const productId = Number(item.product_id);
            const quantity = Number(item.quantity);
            const minQuantity = Math.max(1, Number(item.min_quantity) || 1);
            const maxQuantity = Math.max(minQuantity, Number(item.max_quantity) || 99);
            const priceCents = parseMoneyCents(item.price);
            const lineTotalCents = priceCents * quantity;
            if (!Number.isSafeInteger(subtotalCents + lineTotalCents)) {
                throw new TypeError('Total keranjang melebihi batas yang didukung.');
            }
            subtotalCents += lineTotalCents;

            const row = document.createElement('article');
            row.className = 'cart-item';
            const details = document.createElement('div');
            details.className = 'cart-item-details';
            appendText(details, 'strong', 'cart-item-name', item.name);
            appendText(details, 'span', 'cart-item-price', formatCents(priceCents) + ' per item');
            row.appendChild(details);

            const controls = document.createElement('div');
            controls.className = 'cart-item-controls';
            const quantityLabel = document.createElement('label');
            quantityLabel.className = 'visually-hidden';
            quantityLabel.htmlFor = 'cart-quantity-' + productId;
            quantityLabel.textContent = 'Jumlah ' + item.name;
            controls.appendChild(quantityLabel);
            const quantityInput = document.createElement('input');
            quantityInput.className = 'form-control cart-quantity-input';
            quantityInput.type = 'number';
            quantityInput.min = String(minQuantity);
            quantityInput.max = String(maxQuantity);
            quantityInput.step = '1';
            quantityInput.value = String(quantity);
            quantityInput.id = 'cart-quantity-' + productId;
            quantityInput.dataset.cartQuantity = String(productId);
            controls.appendChild(quantityInput);

            const lineTotal = document.createElement('strong');
            lineTotal.className = 'cart-item-total';
            lineTotal.textContent = formatCents(priceCents * quantity);
            controls.appendChild(lineTotal);

            const removeButton = document.createElement('button');
            removeButton.className = 'btn btn-sm btn-outline-danger';
            removeButton.type = 'button';
            removeButton.dataset.cartRemove = String(productId);
            removeButton.setAttribute('aria-label', 'Hapus ' + item.name);
            removeButton.textContent = 'Hapus';
            controls.appendChild(removeButton);
            row.appendChild(controls);
            list.appendChild(row);
        });

        const isEmpty = items.length === 0;
        emptyState.hidden = !isEmpty;
        list.hidden = isEmpty && invalidItems.length === 0;
        subtotalDisplay.textContent = formatCents(subtotalCents);
        totalDisplay.textContent = formatCents(subtotalCents);
        checkoutLink.classList.toggle('disabled', isEmpty);
        checkoutLink.setAttribute('aria-disabled', String(isEmpty));
        checkoutLink.tabIndex = isEmpty ? -1 : 0;
    }

    try {
        updateCartCount();
        renderCart();
    } catch (error) {
        console.error('Keranjang lokal tidak dapat dibaca.', error);
        showCartToast('Keranjang tidak dapat dibaca. Hapus data keranjang di browser lalu coba lagi.');
    }

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey || event.key === null) {
            try {
                updateCartCount();
                renderCart();
            } catch (error) {
                console.error('Keranjang lokal tidak dapat diperbarui.', error);
            }
        }
    });
    window.addEventListener('cozycafe:cart-updated', () => {
        try {
            updateCartCount();
            renderCart();
        } catch (error) {
            console.error('Keranjang lokal tidak dapat diperbarui.', error);
        }
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }
        const removeButton = event.target.closest('[data-cart-remove]');
        if (removeButton) {
            try {
                const productId = Number(removeButton.dataset.cartRemove);
                writeCart(readCart().filter((item) => Number(item.product_id) !== productId));
            } catch (error) {
                console.error('Item keranjang tidak dapat dihapus.', error);
                showCartToast('Item tidak dapat dihapus dari keranjang.');
            }
            return;
        }

        const clearButton = event.target.closest('[data-cart-clear]');
        if (clearButton) {
            try {
                writeCart([]);
            } catch (error) {
                console.error('Keranjang tidak dapat dikosongkan.', error);
                showCartToast('Keranjang tidak dapat dikosongkan.');
            }
            return;
        }

        const button = event.target.closest('[data-add-to-cart]');
        if (!button || button.disabled) {
            return;
        }

        try {
            const product = {
                product_id: Number(button.dataset.productId),
                name: button.dataset.productName,
                price: button.dataset.productPrice,
                quantity: Number(button.dataset.minQuantity),
                min_quantity: Number(button.dataset.minQuantity),
                max_quantity: Number(button.dataset.maxQuantity)
            };
            const cart = readCart();
            if (!Number.isSafeInteger(product.product_id) || product.product_id < 1
                || !product.name || !Number.isSafeInteger(product.quantity)
                || !Number.isSafeInteger(product.max_quantity)
                || product.quantity < 1 || product.max_quantity < product.quantity) {
                throw new TypeError('Data produk tidak valid.');
            }
            parseMoneyCents(product.price);

            const existing = cart.find((item) => Number(item.product_id) === product.product_id);
            if (existing) {
                const nextQuantity = Number(existing.quantity) + product.quantity;
                if (!Number.isSafeInteger(nextQuantity) || nextQuantity > product.max_quantity) {
                    showCartToast('Jumlah maksimum produk ini sudah tercapai.');
                    return;
                }
                existing.quantity = nextQuantity;
                existing.name = product.name;
                existing.price = product.price;
                existing.min_quantity = product.min_quantity;
                existing.max_quantity = product.max_quantity;
            } else {
                cart.push(product);
            }

            writeCart(cart);
            showCartToast(product.name + ' ditambahkan ke keranjang.');
        } catch (error) {
            console.error('Produk tidak dapat ditambahkan ke keranjang.', error);
            showCartToast('Keranjang tidak dapat diperbarui. Muat ulang halaman dan coba lagi.');
        }
    });

    document.addEventListener('change', (event) => {
        if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-cart-quantity]')) {
            return;
        }
        const productId = Number(event.target.dataset.cartQuantity);
        const quantity = Number(event.target.value);
        try {
            const cart = readCart();
            const item = cart.find((cartItem) => Number(cartItem.product_id) === productId);
            if (!item) {
                return;
            }
            const minimum = Math.max(1, Number(item.min_quantity) || 1);
            const maximum = Math.max(minimum, Number(item.max_quantity) || 99);
            if (!Number.isSafeInteger(quantity) || quantity < minimum || quantity > maximum) {
                showCartToast('Jumlah harus antara ' + minimum + ' dan ' + maximum + '.');
                renderCart();
                return;
            }
            item.quantity = quantity;
            writeCart(cart);
        } catch (error) {
            console.error('Jumlah keranjang tidak dapat diperbarui.', error);
            showCartToast('Jumlah tidak dapat diperbarui.');
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-checkout-form]')) {
            return;
        }
        try {
            const cart = readCart();
            if (cart.length === 0 || cart.some((item) => !validCartItem(item))) {
                event.preventDefault();
                showCartToast('Keranjang kosong atau berisi data yang tidak valid.');
                return;
            }
            form.querySelector('[name="cart_json"]').value = JSON.stringify(
                cart.map((item) => ({
                    product_id: Number(item.product_id),
                    quantity: Number(item.quantity)
                }))
            );
        } catch (error) {
            event.preventDefault();
            console.error('Keranjang gagal disiapkan untuk checkout.', error);
            showCartToast('Keranjang tidak dapat dibaca untuk checkout.');
        }
    });

    const orderType = document.getElementById('orderType');
    const bookingField = document.querySelector('[data-booking-field]');
    if (orderType && bookingField) {
        orderType.addEventListener('change', () => {
            const showBooking = orderType.value === 'dine_in';
            bookingField.hidden = !showBooking;
            if (!showBooking) {
                const bookingInput = bookingField.querySelector('input');
                if (bookingInput) {
                    bookingInput.value = '';
                }
            }
        });
    }

    const trackingRoot = document.querySelector('[data-order-tracking]');
    if (trackingRoot) {
        const orderStatus = trackingRoot.querySelector('[data-order-status]');
        const paymentStatus = trackingRoot.querySelector('[data-payment-status]');
        const paymentCountdown = trackingRoot.querySelector('[data-payment-countdown]');
        const paymentDeadlineLabel = trackingRoot.querySelector('[data-payment-deadline-label]');
        const countdownPanel = trackingRoot.querySelector('[data-countdown-panel]');
        const historyList = trackingRoot.querySelector('[data-order-history]');
        const substitutionsRoot = trackingRoot.querySelector('[data-order-substitutions]');
        const substitutionsSection = trackingRoot.querySelector('[data-substitution-section]');
        const trackingMessage = trackingRoot.querySelector('[data-tracking-message]');
        const totalAmount = trackingRoot.querySelector('[data-order-total]');

        function appendTrackingText(parent, tag, className, value) {
            const element = document.createElement(tag);
            if (className) {
                element.className = className;
            }
            element.textContent = value;
            parent.appendChild(element);
            return element;
        }

        function updateCountdown() {
            const deadlineValue = trackingRoot.dataset.paymentDeadline;
            const paymentIsFinal = ['paid', 'refunded'].includes(trackingRoot.dataset.paymentStatus);
            if (!deadlineValue || paymentIsFinal) {
                countdownPanel.hidden = true;
                return;
            }

            countdownPanel.hidden = false;
            const deadline = new Date(deadlineValue).getTime();
            if (!Number.isFinite(deadline)) {
                paymentCountdown.textContent = 'Tidak tersedia';
                return;
            }

            const remainingSeconds = Math.floor((deadline - Date.now()) / 1000);
            if (remainingSeconds <= 0) {
                paymentCountdown.textContent = 'Waktu pembayaran habis';
                return;
            }

            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            paymentCountdown.textContent = [hours, minutes, seconds]
                .map((value) => String(value).padStart(2, '0'))
                .join(':');
        }

        function renderHistory(histories) {
            historyList.replaceChildren();
            histories.forEach((history) => {
                const item = document.createElement('li');
                appendTrackingText(item, 'span', 'tracking-timeline-dot', '');
                const details = document.createElement('div');
                appendTrackingText(details, 'strong', '', history.status_label);
                appendTrackingText(details, 'time', '', history.changed_at);
                item.appendChild(details);
                historyList.appendChild(item);
            });
        }

        function renderSubstitutions(substitutions) {
            substitutionsRoot.replaceChildren();
            substitutionsSection.hidden = substitutions.length === 0;
            substitutions.forEach((substitution) => {
                const item = document.createElement('article');
                item.className = 'tracking-substitution';
                const details = document.createElement('div');
                appendTrackingText(
                    details,
                    'strong',
                    '',
                    substitution.original_product_name + ' → ' + substitution.replacement_product_name
                );
                appendTrackingText(
                    details,
                    'span',
                    '',
                    substitution.quantity + ' item · ' + window.CozyCafe.formatRupiah(substitution.replacement_price)
                );
                item.appendChild(details);

                const status = document.createElement('div');
                status.className = 'tracking-substitution-status';
                appendTrackingText(status, 'strong', '', substitution.status_label);
                if (substitution.status_label === 'Menunggu jawaban') {
                    appendTrackingText(status, 'span', '', 'Batas respons ' + substitution.expires_at);
                }
                item.appendChild(status);
                substitutionsRoot.appendChild(item);
            });
        }

        async function refreshTracking() {
            if (document.hidden || trackingRoot.dataset.refreshing === '1') {
                return;
            }
            trackingRoot.dataset.refreshing = '1';
            try {
                const response = await fetch(trackingRoot.dataset.statusUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    cache: 'no-store'
                });
                if (!response.ok) {
                    throw new Error('Status pesanan tidak dapat diperbarui.');
                }
                const result = await response.json();
                const order = result.order;
                orderStatus.textContent = order.order_status_label;
                paymentStatus.textContent = order.payment_status_label;
                trackingRoot.dataset.paymentStatus = order.payment_status;
                trackingRoot.dataset.paymentDeadline = order.payment_deadline || '';
                paymentDeadlineLabel.textContent = order.payment_deadline_label;
                totalAmount.textContent = window.CozyCafe.formatRupiah(order.total_amount);
                renderHistory(order.histories);
                renderSubstitutions(order.substitutions);
                trackingMessage.textContent = 'Status diperbarui otomatis setiap 20 detik.';
            } catch (error) {
                trackingMessage.textContent = 'Pembaruan tertunda. Periksa koneksi, status akan dicoba kembali.';
                console.error('Status pesanan tidak dapat diperbarui.', error);
            } finally {
                trackingRoot.dataset.refreshing = '0';
                updateCountdown();
            }
        }

        trackingRoot.dataset.paymentStatus = trackingRoot.dataset.paymentStatus || 'unpaid';
        updateCountdown();
        window.setInterval(updateCountdown, 1000);
        window.setInterval(refreshTracking, 20000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refreshTracking();
            }
        });
    }

    const orderCreated = document.querySelector('[data-order-created][data-clear-cart="1"]');
    if (orderCreated) {
        try {
            window.localStorage.removeItem(storageKey);
            window.dispatchEvent(new CustomEvent('cozycafe:cart-updated'));
        } catch (error) {
            console.error('Keranjang tidak dapat dikosongkan setelah checkout.', error);
        }
    }
})();
