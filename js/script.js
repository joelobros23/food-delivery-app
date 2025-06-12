// js/script.js


function createIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Handles the logic for toggling the favorite status for a FOOD ITEM.
 * @param {HTMLElement} button - The button element that was clicked.
 */
function handleItemFavorite(button) {
    const id = button.dataset.itemId;
    if (!id) {
        console.error("Error: data-item-id attribute not found on button.", button);
        return;
    }

    const formData = new FormData();
    formData.append('item_id', id);

    fetch('../customer_dashboard_settings/toggle_favorite_item.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const allMatchingButtons = document.querySelectorAll(`button[data-item-id="${id}"]`);
                allMatchingButtons.forEach(btn => {
                    const btnIcon = btn.querySelector('svg');
                    if (btnIcon) {
                        if (data.action === 'favorited') {
                            btnIcon.classList.add('fill-red-500', 'text-red-500');
                        } else {
                            btnIcon.classList.remove('fill-red-500', 'text-red-500');
                        }
                    }
                });
            } else {
                alert(data.message || 'An error occurred with item favorite.');
            }
        })
        .catch(error => console.error('Network error:', error));
}

/*
/**
 * Handles the logic for toggling the favorite status for a RESTAURANT.
 * @param {HTMLElement} button - The button element that was clicked.
 */
function handleRestaurantFavorite(button) {
    const id = button.dataset.restaurantId;
     if (!id) {
        console.error("Error: data-restaurant-id attribute not found on button.", button);
        return;
    }
    const formData = new FormData();
    formData.append('restaurant_id', id);

    fetch('../customer_dashboard_settings/toggle_favorite_restaurant.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const allMatchingButtons = document.querySelectorAll(`button[data-restaurant-id="${id}"]`);
                allMatchingButtons.forEach(btn => {
                    const btnIcon = btn.querySelector('svg');
                    const btnText = btn.querySelector('span');
                    if (btnIcon) {
                        if (data.action === 'favorited') {
                            btnIcon.classList.add('fill-red-500', 'text-red-500');
                            if(btnText) btnText.textContent = 'Favorited';
                        } else {
                            btnIcon.classList.remove('fill-red-500', 'text-red-500');
                            if(btnText) btnText.textContent = 'Favorite';
                        }
                    }
                });
            } else {
                alert(data.message || 'An error occurred with restaurant favorite.');
            }
        })
        .catch(error => console.error('Network error:', error));
}


        // --- STORE ORDERS PAGE ---


function initializeStoreOrdersPage() {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    
    // Tab switching logic
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => { 
                t.classList.remove('text-orange-600', 'border-orange-500'); 
                t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'); 
            });
            tab.classList.add('text-orange-600', 'border-orange-500');
            tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            contents.forEach(content => content.classList.add('hidden'));
            document.getElementById(tab.dataset.target)?.classList.remove('hidden');
        });
    });

    // Helper to send order updates to the backend
    function handleOrderAction(orderId, action) {
        return fetch('manage_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${orderId}&action=${action}`
        }).then(response => response.json());
    }

    // Helper to update the number badge in a tab
    function updateTabCount(tabId, change) {
        const countSpan = document.getElementById(tabId);
        if (countSpan) {
            const currentCount = parseInt(countSpan.textContent) || 0;
            countSpan.textContent = Math.max(0, currentCount + change);
        }
    }

    // Helper to create the HTML for a new "Active" order card
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

    // Main event listener for actions within the order tabs
    document.body.addEventListener('click', function(e) {
        const card = e.target.closest('.order-card');
        if (!card) return;
        const orderId = card.dataset.orderId;

        // --- Handle Accept Button ---
        if (e.target.classList.contains('accept-btn')) {
            handleOrderAction(orderId, 'accept').then(data => {
                if (data.status === 'success') {
                    const orderData = JSON.parse(card.dataset.orderData);
                    const pendingTab = document.getElementById('pending');
                    const activeTab = document.getElementById('active');
                    
                    const newActiveCardHTML = createActiveOrderCardHTML(orderData);
                    activeTab.querySelector('.no-orders-msg')?.remove();
                    activeTab.insertAdjacentHTML('afterbegin', newActiveCardHTML);
                    
                    card.remove();

                    updateTabCount('pending-count', -1);
                    updateTabCount('active-count', 1);

                    if (!pendingTab.querySelector('.order-card')) {
                        pendingTab.innerHTML = `<p class="no-orders-msg text-gray-500 text-center py-8">No pending orders.</p>`;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // --- Handle Reject Button ---
        if (e.target.classList.contains('reject-btn')) {
            if (!confirm('Are you sure you want to reject this order?')) return;
            handleOrderAction(orderId, 'reject').then(data => {
                if (data.status === 'success') {
                    card.remove();
                    updateTabCount('pending-count', -1);
                } else { alert('Error: ' + data.message); }
            });
        }
        
        // --- Handle Ready for Delivery Button ---
        if (e.target.classList.contains('ready-btn')) {
             handleOrderAction(orderId, 'ready_for_delivery').then(data => {
                if(data.status === 'success') {
                    const statusText = card.querySelector('.status-badge');
                    if(statusText) {
                        statusText.textContent = 'Out for Delivery';
                        statusText.classList.remove('text-blue-800');
                        statusText.classList.add('text-purple-800');
                    }
                    const actionsContainer = card.querySelector('.actions-container');
                    if(actionsContainer) {
                        actionsContainer.innerHTML = `<p class="text-sm font-semibold text-purple-800">Waiting for rider</p>`;
                    }
                } else { alert('Error: ' + data.message); }
            });
        }
    });
}

// --- Main Script Execution ---
document.addEventListener('DOMContentLoaded', function() {
    createIcons();

    // --- GLOBAL EVENT LISTENER for all clicks ---
    document.body.addEventListener('click', function(e) {
        const favoriteItemBtn = e.target.closest('.favorite-item-btn');
        if (favoriteItemBtn) {
            e.preventDefault(); e.stopPropagation();
            handleItemFavorite(favoriteItemBtn);
            return;
        }
        
        const favoriteRestaurantBtn = e.target.closest('.favorite-restaurant-btn');
        if (favoriteRestaurantBtn) {
            e.preventDefault(); e.stopPropagation();
            handleRestaurantFavorite(favoriteRestaurantBtn);
            return;
        }

           if (document.getElementById('pending') && document.getElementById('active')) {
        initializeStoreOrdersPage();
    }

        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileButton && profileButton.contains(e.target)) {
             e.stopPropagation();
             profileDropdown.classList.toggle('hidden');
             if (!profileDropdown.classList.contains('hidden')) { createIcons(); }
        } else if (profileDropdown && !profileDropdown.classList.contains('hidden') && !profileDropdown.contains(e.target)) {
             profileDropdown.classList.add('hidden');
        }
    });

    // --- MOBILE & SIDEBAR ---
    const sidebarDrawer = document.getElementById('sidebar-drawer');
    if (sidebarDrawer) {
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const openBtn = document.getElementById('sidebar-open-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const openSidebar = () => { sidebarDrawer.classList.remove('-translate-x-full'); sidebarOverlay.classList.remove('hidden'); };
        const closeSidebar = () => { sidebarDrawer.classList.add('-translate-x-full'); sidebarOverlay.classList.add('hidden'); };
        openBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);
    }
    
    // --- SEARCH PAGE LOGIC ---
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        const autosuggestContainer = document.getElementById('autosuggest-container');
        const searchForm = document.getElementById('search-form');
        let debounceTimer;
        searchInput.addEventListener('keyup', function(e) {
            const query = e.target.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (query.length < 2) { autosuggestContainer.classList.add('hidden'); return; }
                fetch(`customer_dashboard_settings/autosuggest.php?term=${encodeURIComponent(query)}`).then(r => r.json()).then(data => {
                    if (data.length > 0) {
                        let suggestionsHTML = '<ul class="divide-y divide-gray-100">';
                        data.forEach(item => {
                            const icon = item.type === 'restaurant' ? '<i data-lucide="store" class="w-5 h-5 mr-3 text-gray-400"></i>' : '<i data-lucide="utensils" class="w-5 h-5 mr-3 text-gray-400"></i>';
                            suggestionsHTML += `<li class="p-3 hover:bg-gray-100 cursor-pointer flex items-center" data-value="${item.name}">${icon}<span>${item.name}</span></li>`;
                        });
                        suggestionsHTML += '</ul>';
                        autosuggestContainer.innerHTML = suggestionsHTML;
                        autosuggestContainer.classList.remove('hidden');
                        createIcons();
                    } else { autosuggestContainer.classList.add('hidden'); }
                });
            }, 300);
        });
        autosuggestContainer.addEventListener('click', function(e) {
            if (e.target.closest('li')) { searchInput.value = e.target.closest('li').dataset.value; autosuggestContainer.classList.add('hidden'); searchForm.submit(); }
        });
        document.addEventListener('click', function (e) {
            if (autosuggestContainer && !autosuggestContainer.contains(e.target) && e.target !== searchInput) { autosuggestContainer.classList.add('hidden'); }
        });
    }
    
    // --- GENERIC TAB SWITCHING LOGIC for any page with tabs ---
    const tabs = document.querySelectorAll('.tab-btn');
    if (tabs.length > 0) {
        const contents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => { 
                    t.classList.remove('text-orange-600', 'border-orange-500'); 
                    t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'); 
                });
                tab.classList.add('text-orange-600', 'border-orange-500');
                tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                contents.forEach(content => content.classList.add('hidden'));
                const contentId = 'content-' + tab.id.split('-')[1];
                const contentToShow = document.getElementById(contentId);
                if (contentToShow) {
                    contentToShow.classList.remove('hidden');
                }
            });
        });
    }


});
