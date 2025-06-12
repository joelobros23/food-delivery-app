// js/cart.js
document.addEventListener('DOMContentLoaded', function() {
    const cartContainer = document.querySelector('.lg\\:col-span-2');
    if (!cartContainer) return; // Only run on cart page

    const paymentModal = document.getElementById('payment-modal');
    
    function updateCartTotal() {
        let subtotal = 0;
        document.querySelectorAll('.item-total').forEach(itemTotalEl => {
            subtotal += parseFloat(itemTotalEl.textContent.replace('₱', '').replace(',', ''));
        });
        
        const deliveryFee = 50.00;
        const total = subtotal + deliveryFee;

        document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
        document.getElementById('total').textContent = `₱${total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
    }

    function handleCartAction(formData) {
        return fetch('manage_cart.php', { method: 'POST', body: formData }).then(res => res.json());
    }

    cartContainer.addEventListener('click', function(e) {
        const itemRow = e.target.closest('.flex.items-center.space-x-4');
        if (!itemRow) return;
        
        const cartId = itemRow.dataset.cartId;
        
        if (e.target.closest('.quantity-btn')) {
            const btn = e.target.closest('.quantity-btn');
            const quantityDisplay = itemRow.querySelector('.quantity-display');
            const itemTotalEl = itemRow.querySelector('.item-total');
            const pricePerItem = parseFloat(itemRow.querySelector('.font-semibold.text-orange-600').textContent.replace('₱', ''));
            
            let currentQuantity = parseInt(quantityDisplay.textContent);
            if(btn.dataset.action === 'increase') { currentQuantity++; } 
            else if (currentQuantity > 1) { currentQuantity--; }

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('cart_id', cartId);
            formData.append('quantity', currentQuantity);
            handleCartAction(formData).then(data => {
                if (data.status === 'success') {
                    quantityDisplay.textContent = currentQuantity;
                    itemTotalEl.textContent = `₱${(pricePerItem * currentQuantity).toFixed(2)}`;
                    updateCartTotal();
                }
            });
        }
        
        if (e.target.closest('.remove-item-btn')) {
            if (confirm('Are you sure you want to remove this item?')) {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('cart_id', cartId);
                handleCartAction(formData).then(data => {
                    if(data.status === 'success') {
                        itemRow.remove();
                        updateCartTotal();
                    }
                });
            }
        }
    });

    // --- Checkout and Payment Modal Logic ---
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => paymentModal.classList.remove('hidden'));
    }
    
    document.getElementById('close-modal-btn')?.addEventListener('click', () => paymentModal.classList.add('hidden'));
    paymentModal?.addEventListener('click', (e) => { if(e.target === paymentModal) paymentModal.classList.add('hidden'); });

    document.querySelectorAll('.payment-option-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (btn.textContent.includes('Not Available')) {
                e.preventDefault();
                alert('This payment method is not available.');
            }
        });
    });

    const codBtn = document.getElementById('cod-btn');
    if(codBtn) {
        codBtn.addEventListener('click', () => {
            codBtn.textContent = 'Placing Order...';
            codBtn.disabled = true;

            fetch('manage_checkout.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('Order placed successfully!');
                    window.location.href = 'orders.php';
                } else {
                    alert('Error: ' + data.message);
                    codBtn.textContent = 'Cash on Delivery';
                    codBtn.disabled = false;
                }
            });
        });
    }
});
