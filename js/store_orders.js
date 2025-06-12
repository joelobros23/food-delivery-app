// js/script.js - Main script for the Foodie Application

/**
 * Initializes Lucide icons on the page.
 */
function createIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// --- FAVORITING LOGIC ---
function handleFavoriteToggle(button, type) {
    const idKey = type === 'item' ? 'itemId' : 'restaurantId';
    const id = button.dataset[idKey];
    if (!id) { return; }

    const endpoint = type === 'item' ? 'toggle_favorite_item.php' : 'toggle_favorite_restaurant.php';
    const formData = new FormData();
    formData.append(type === 'item' ? 'item_id' : 'restaurant_id', id);

    fetch(endpoint, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.querySelectorAll(`button[data-${type}-id="${id}"]`).forEach(btn => {
                    const btnIcon = btn.querySelector('svg');
                    const btnText = btn.querySelector('span');
                    if (btnIcon) {
                        if (data.action === 'favorited') {
                            btnIcon.classList.add('fill-red-500', 'text-red-500');
                            if (btnText) btnText.textContent = 'Favorited';
                        } else {
                            btnIcon.classList.remove('fill-red-500', 'text-red-500');
                            if (btnText) btnText.textContent = 'Favorite';
                        }
                    }
                });
            } else { alert(data.message || 'An error occurred.'); }
        })
        .catch(error => console.error('Network error:', error));
}

    function handleOrderAction(orderId, action) {
        return fetch('manage_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${orderId}&action=${action}`
        }).then(response => response.json());
    }

function updateTabCount(tabId, change) {
    const countSpan = document.getElementById(tabId);
    if (countSpan) {
        countSpan.textContent = Math.max(0, parseInt(countSpan.textContent) + change);
    }
}

function createActiveOrderCardHTML(orderData) {
    return `
        <div class="order-card bg-white p-4 rounded-lg shadow-md" data-order-id="${orderData.id}" data-order-data='${JSON.stringify(orderData)}'>
            <div class="flex justify-between items-center">
                <p class="font-bold">Order #${orderData.id}</p>
                <p class="status-badge text-sm font-semibold text-blue-800">Preparing</p>
            </div>
            <p class="text-sm text-gray-600">Customer: ${orderData.customer_name}</p>
            <p class="text-sm text-gray-600 mt-1">Payment: <span class="font-medium text-gray-800">${orderData.payment_method.toUpperCase()}</span></p>
            <div class="mt-4 border-t pt-4 flex justify-between items-center">
                <button class="px-3 py-1 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">View Order</button>
                <div class="actions-container text-right">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Prepare and wait for the rider to pick-up</p>
                    <button class="ready-btn px-3 py-1 bg-orange-500 text-white text-sm rounded-md hover:bg-orange-600">Ready for Delivery</button>
                </div>
            </div>
        </div>`;
}

// --- MAIN SCRIPT EXECUTION ---
document.addEventListener('DOMContentLoaded', function() {
    createIcons();

    document.body.addEventListener('click', function(e) {
        const card = e.target.closest('.order-card');
        if (!card) return;
        const orderId = card.dataset.orderId;

        if (e.target.classList.contains('accept-btn') || e.target.classList.contains('ready-btn')) {
            const action = e.target.classList.contains('accept-btn') ? 'accept' : 'ready_for_delivery';
            handleOrderAction(orderId, action).then(data => {
                if (data.status === 'success') {
                    window.location.reload(); // Simple reload to show changes
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        if (e.target.classList.contains('reject-btn')) {
            if (!confirm('Are you sure you want to reject this order?')) return;
            handleOrderAction(orderId, 'reject').then(data => {
                if (data.status === 'success') {
                    window.location.reload(); // Simple reload to show changes
                } else { alert('Error: ' + data.message); }
            });
        }
    });

    // --- MOBILE SIDEBAR ---
    const sidebarDrawer = document.getElementById('sidebar-drawer');
    if (sidebarDrawer) {
        const openBtn = document.getElementById('sidebar-open-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');
        openBtn?.addEventListener('click', () => { sidebarDrawer.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); });
        closeBtn?.addEventListener('click', () => { sidebarDrawer.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
        overlay?.addEventListener('click', () => { sidebarDrawer.classList.add('-translate-x-full'); overlay.classList.add('hidden'); });
    }

    // --- STORE ORDERS PAGE ---
    const storeOrdersPage = document.getElementById('pending');
    if (storeOrdersPage) {
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => { t.classList.remove('text-orange-600', 'border-orange-500'); t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'); });
                tab.classList.add('text-orange-600', 'border-orange-500');
                contents.forEach(c => c.classList.add('hidden'));
                document.getElementById(tab.dataset.target)?.classList.remove('hidden');
            });
        });

        document.body.addEventListener('click', function(e) {
            const card = e.target.closest('.order-card');
            if (!card) return;
            const orderId = card.dataset.orderId;

            if (e.target.classList.contains('accept-btn')) {
                handleOrderAction(orderId, 'accept').then(data => {
                    if (data.status === 'success') {
                        const orderData = JSON.parse(card.dataset.orderData);
                        const activeTab = document.getElementById('active');
                        activeTab.querySelector('.no-orders-msg')?.remove();
                        activeTab.insertAdjacentHTML('afterbegin', createActiveOrderCardHTML(orderData));
                        card.remove();
                        updateTabCount('pending-count', -1);
                        updateTabCount('active-count', 1);
                        if (!document.getElementById('pending').querySelector('.order-card')) {
                            document.getElementById('pending').innerHTML = `<p class="no-orders-msg text-gray-500 text-center py-8">No pending orders.</p>`;
                        }
                    } else { alert('Error: ' + data.message); }
                });
            }
        });
    }
});
