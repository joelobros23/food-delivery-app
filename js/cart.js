// js/cart.js
document.addEventListener('DOMContentLoaded', function() {
    const mainContainer = document.querySelector('.lg\\:col-span-2');
    if (!mainContainer) return; // Only run on cart page

    const paymentModal = document.getElementById('payment-modal');

    // --- Checkout and Payment Modal Logic ---
    document.getElementById('checkout-btn')?.addEventListener('click', () => paymentModal.classList.remove('hidden'));
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

            // This now sends a single request, the backend handles splitting the order.
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
