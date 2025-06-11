document.addEventListener('DOMContentLoaded', function() {
    // Tab switching logic
    const tabs = document.querySelectorAll('.tab-btn');
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
            document.getElementById('content-' + tab.id.split('-')[1]).classList.remove('hidden');
        });
    });

    // Star Rating Helper
    function setupStarRating(containerId) {
        const ratingContainer = document.getElementById(containerId);
        if (!ratingContainer) return;
        const stars = ratingContainer.querySelectorAll('.star');
        const ratingInput = ratingContainer.nextElementSibling;
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                ratingInput.value = star.dataset.value;
                stars.forEach(s => {
                    s.classList.toggle('selected', s.dataset.value <= ratingInput.value);
                });
            });
        });
    }
    setupStarRating('add-rating-stars');
    setupStarRating('edit-rating-stars');
    
    function setStarRating(containerId, rating) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.nextElementSibling.value = rating;
        container.querySelectorAll('.star').forEach(s => {
            s.classList.toggle('selected', s.dataset.value <= rating);
        });
    }

    // Review form submission
    const reviewForm = document.getElementById('review-form');
    if(reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const messageDiv = document.getElementById('review-message');
            messageDiv.textContent = '';
            
            if (formData.get('rating') === '0') {
                messageDiv.innerHTML = `<p class="text-red-600">Please select a rating.</p>`;
                return;
            }

            fetch('customer_dashboard_settings/add_review.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        messageDiv.innerHTML = `<p class="text-green-600">${data.message}</p>`;
                        reviewForm.reset();
                        document.querySelectorAll('#add-rating-stars .star').forEach(s => s.classList.add('selected'));
                        document.getElementById('rating-value').value = 5;
                        
                        const reviewList = document.getElementById('reviews-list');
                        const newReviewHTML = `
                            <div class="bg-white p-5 rounded-lg shadow-md border-l-4 border-orange-400" data-review-id="${data.review.id}">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-bold text-gray-800">${data.review.customer_name}</p>
                                        <div class="flex items-center my-2 star-display" data-rating="${data.review.rating}">
                                            ${[...Array(5)].map((_, i) => `<i data-lucide="star" class="w-5 h-5 ${i < data.review.rating ? 'text-yellow-500 fill-current' : 'text-gray-300'}"></i>`).join('')}
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-xs text-gray-500">${data.review.review_date}</span>
                                    </div>
                                </div>
                                <p class="text-gray-600 mt-2 comment-display">${data.review.comment}</p>
                            </div>
                        `;
                        reviewList.insertAdjacentHTML('afterbegin', newReviewHTML);
                        lucide.createIcons();
                        document.getElementById('review-form-container').style.display = 'none';
                        document.getElementById('no-reviews-placeholder')?.remove();
                    } else {
                        messageDiv.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                    }
                });
        });
    }

    // Consolidated Event Listener for dynamic content
    document.addEventListener('click', function(e) {
        const optionsBtn = e.target.closest('.review-options-btn');
        if (optionsBtn) {
            e.stopPropagation();
            document.querySelectorAll('.review-options-menu').forEach(menu => {
                if (menu !== optionsBtn.nextElementSibling) menu.classList.add('hidden');
            });
            optionsBtn.nextElementSibling.classList.toggle('hidden');
            return;
        }
        
        if (!e.target.closest('.review-options-menu')) {
            document.querySelectorAll('.review-options-menu').forEach(menu => menu.classList.add('hidden'));
        }

        const deleteBtn = e.target.closest('.delete-review-btn');
        if (deleteBtn) {
            e.preventDefault();
            reviewToDelete = deleteBtn.closest('.bg-white[data-review-id]');
            deleteModal.classList.remove('hidden');
        }
        
        const editBtn = e.target.closest('.edit-review-btn');
        if(editBtn) {
            e.preventDefault();
            reviewToEdit = editBtn.closest('.bg-white[data-review-id]');
            const reviewId = reviewToEdit.dataset.reviewId;
            const currentRating = reviewToEdit.querySelector('.star-display').dataset.rating;
            const currentComment = reviewToEdit.querySelector('.comment-display').textContent;
            document.getElementById('edit-review-id').value = reviewId;
            document.getElementById('edit-comment').value = currentComment;
            setStarRating('edit-rating-stars', currentRating);
            editModal.classList.remove('hidden');
        }
    });

    // Modal Logic
    const deleteModal = document.getElementById('delete-confirm-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    let reviewToDelete = null;

    cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.add('hidden'));
    confirmDeleteBtn.addEventListener('click', () => {
        if (!reviewToDelete) return;
        const reviewId = reviewToDelete.dataset.reviewId;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        
        fetch('customer_dashboard_settings/manage_review.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(d => {
            if (d.status === 'success') { reviewToDelete.remove(); } 
            else { alert(d.message); }
            deleteModal.classList.add('hidden');
        });
    });

    const editModal = document.getElementById('edit-review-modal');
    const editForm = document.getElementById('edit-review-form');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    let reviewToEdit = null;

    cancelEditBtn.addEventListener('click', () => editModal.classList.add('hidden'));
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'edit');
        fetch('customer_dashboard_settings/manage_review.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            if(data.status === 'success') {
                const commentDisplay = reviewToEdit.querySelector('.comment-display');
                const starDisplay = reviewToEdit.querySelector('.star-display');
                commentDisplay.textContent = data.review.comment;
                starDisplay.dataset.rating = data.review.rating;
                let starHTML = '';
                for(let i = 0; i < 5; i++) {
                    starHTML += `<i data-lucide="star" class="w-5 h-5 ${i < data.review.rating ? 'text-yellow-500 fill-current' : 'text-gray-300'}"></i>`;
                }
                starDisplay.innerHTML = starHTML;
                lucide.createIcons();
                editModal.classList.add('hidden');
            } else {
                document.getElementById('edit-review-message').textContent = data.message;
            }
        });
    });

    
});