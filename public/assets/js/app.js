window.CozyCafe = {
    formatRupiah: (amount) => 'Rp' + Number(amount).toLocaleString('id-ID')
};

(() => {
    const storageKey = 'cozycafe_cart';

    function updateCartCount() {
        const badges = document.querySelectorAll('[data-cart-count]');
        if (badges.length === 0) {
            return;
        }

        const rawCart = window.localStorage.getItem(storageKey);
        const cart = rawCart ? JSON.parse(rawCart) : [];
        const count = Array.isArray(cart)
            ? cart.reduce((total, item) => total + Math.max(0, Number(item.quantity) || 0), 0)
            : 0;

        badges.forEach((badge) => {
            badge.textContent = String(count > 99 ? '99+' : count);
            badge.hidden = count < 1;
        });
    }

    try {
        updateCartCount();
        window.addEventListener('storage', (event) => {
            if (event.key === storageKey || event.key === null) {
                updateCartCount();
            }
        });
        window.addEventListener('cozycafe:cart-updated', updateCartCount);
    } catch (error) {
        console.error('Keranjang lokal tidak dapat dibaca.', error);
    }
})();
