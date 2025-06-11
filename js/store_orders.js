document.addEventListener('DOMContentLoaded', function() {
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
            document.getElementById(tab.dataset.target).classList.remove('hidden');
        });
    });

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

    // --- Main Event Listener for All Order Actions ---
    document.body.addEventListener('click', function(e) {
        const card = e.target.closest('.order-card');
        if (!card) return;
        
        const orderId = card.dataset.orderId;

        // --- Handle Accept Button ---
        if (e.target.classList.contains('accept-btn')) {
            handleOrderAction(orderId, 'accept').then(data => {
                if (data.status === 'success') {
                    // Change the card's appearance to match the "Active" style
                    card.classList.remove('border-orange-500');
                    const actionsContainer = card.querySelector('.mt-4.flex');
                    actionsContainer.innerHTML = `
                        <button class="px-3 py-1 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">View Order</button>
                        <div class="actions-container text-right">
                            <p class="text-sm font-semibold text-gray-700 mb-2">Now preparing and waiting for Rider</p>
                            <button class="ready-btn px-3 py-1 bg-orange-500 text-white text-sm rounded-md hover:bg-orange-600">Ready for Delivery</button>
                        </div>
                    `;
                    
                    const header = card.querySelector('.flex.justify-between');
                    const newStatusBadge = document.createElement('p');
                    newStatusBadge.className = 'status-badge text-sm font-semibold text-blue-800';
                    newStatusBadge.textContent = 'Preparing';
                    header.appendChild(newStatusBadge);

                    // Move the modified card to the Active tab
                    const activeTab = document.getElementById('active');
                    const pendingTab = document.getElementById('pending');
                    activeTab.querySelector('.no-orders-msg')?.remove();
                    activeTab.prepend(card);
                    
                    // Update counts
                    updateTabCount('pending-count', -1);
                    updateTabCount('active-count', 1);

                    // If pending tab is now empty, show the placeholder message
                    if (!pendingTab.querySelector('.order-card')) {
                        pendingTab.innerHTML = `<p class="no-orders-msg text-gray-500 text-center py-8">No pending orders.</p>`;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    });
});
