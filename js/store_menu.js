// js/store_menu.js
document.addEventListener('DOMContentLoaded', function() {
    const itemModal = document.getElementById('item-modal');
    const itemForm = document.getElementById('item-form');
    const modalTitle = document.getElementById('modal-title');
    const tableBody = document.querySelector('table tbody');
    const tableContainer = document.querySelector('.overflow-x-auto');
    const writeWithAiBtn = document.getElementById('write-with-ai-btn');

    // --- Modal Functions ---
    const showModal = () => itemModal.classList.remove('hidden');
    const hideModal = () => {
        itemModal.classList.add('hidden');
        itemForm.reset();
    };

    // --- Dynamic Table Row Update/Add ---
    function upsertTableRow(item) {
        const existingRow = tableBody.querySelector(`tr[data-item-id="${item.id}"]`);
        const newRowHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-12 w-12"><img class="item-image h-12 w-12 rounded-md object-cover" src="${item.image_url || 'https://placehold.co/100x100/F0F0F0/333?text=Dish'}" alt=""></div>
                    <div class="ml-4"><div class="item-name text-sm font-medium text-gray-900">${item.name}</div></div>
                </div>
            </td>
            <td class="item-category px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.category}</td>
            <td class="item-price px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱${parseFloat(item.price).toFixed(2)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" data-id="${item.id}" class="availability-toggle sr-only peer" ${item.is_available ? 'checked' : ''}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-orange-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                </label>
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium">
                <div class="relative inline-block text-left">
                    <button class="item-options-btn p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <i data-lucide="more-vertical" class="w-5 h-5"></i>
                    </button>
                    <div class="item-options-menu hidden absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg border z-20 overflow-hidden">
                        <a href="#" class="edit-btn w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i data-lucide="pencil" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span>Edit</span>
                        </a>
                        <a href="#" class="delete-btn w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2 flex-shrink-0"></i>
                            <span>Delete</span>
                        </a>
                    </div>
                </div>
            </td>`;

        if (existingRow) {
            existingRow.innerHTML = newRowHTML;
        } else {
            const noItemsRow = tableBody.querySelector('td[colspan="5"]');
            if (noItemsRow) noItemsRow.parentElement.remove();
            tableBody.insertAdjacentHTML('beforeend', `<tr data-item-id="${item.id}">${newRowHTML}</tr>`);
        }
        lucide.createIcons();
    }
    
    // --- Event Listeners ---
    document.getElementById('add-item-btn').addEventListener('click', () => {
        modalTitle.textContent = 'Add New Item';
        itemForm.reset();
        document.getElementById('form-action').value = 'add';
        document.getElementById('item-id').value = '';
        document.getElementById('item-is-available').checked = true;
        showModal();
    });

    document.getElementById('cancel-item-btn').addEventListener('click', hideModal);

    itemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('manage_menu_item.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    upsertTableRow(data.item);
                    hideModal();
                } else { alert('Error: ' + data.message); }
            }).catch(err => alert('An error occurred.'));
    });
    

    document.body.addEventListener('click', function(e){
        // Handle options menu toggle
        const optionsBtn = e.target.closest('.item-options-btn');
        if (optionsBtn) {
            e.stopPropagation();
            const currentMenu = optionsBtn.nextElementSibling;
            const isOpening = currentMenu.classList.contains('hidden');
            
            document.querySelectorAll('.item-options-menu').forEach(menu => menu.classList.add('hidden'));
            if(tableContainer) tableContainer.style.overflowX = 'auto';

            if (isOpening) {
                currentMenu.classList.remove('hidden');
                if(tableContainer) tableContainer.style.overflowX = 'visible';
            }
            return;
        }
        if (!e.target.closest('.item-options-menu')) {
            document.querySelectorAll('.item-options-menu').forEach(menu => menu.classList.add('hidden'));
            if(tableContainer) tableContainer.style.overflowX = 'auto';
        }

        // Handle Edit Button Click
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            e.preventDefault();
            const itemRow = editBtn.closest('tr');
            const itemId = itemRow.dataset.itemId;
            const formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('item_id', itemId);

            fetch('manage_menu_item.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    const item = data.item;
                    modalTitle.textContent = 'Edit Item';
                    document.getElementById('form-action').value = 'edit';
                    document.getElementById('item-id').value = item.id;
                    document.getElementById('item-name').value = item.name;
                    document.getElementById('item-description').value = item.description;
                    document.getElementById('item-category').value = item.category;
                    document.getElementById('item-price').value = item.price;
                    document.getElementById('item-image-url').value = item.image_url;
                    document.getElementById('item-is-available').checked = !!parseInt(item.is_available);
                    showModal();
                } else { alert('Error: ' + data.message); }
            });
        }
        
        // Handle Delete Button Click
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            e.preventDefault();
            if(confirm("Are you sure you want to delete this item?")){
                const itemRow = deleteBtn.closest('tr');
                const itemId = itemRow.dataset.itemId;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('item_id', itemId);

                 fetch('manage_menu_item.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        itemRow.remove();
                    } else { alert('Error: ' + data.message); }
                });
            }
        }
    });
});
