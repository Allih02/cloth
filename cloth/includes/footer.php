</main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Clothing Shop Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo getBasePath(); ?>assests/js/script.js"></script>
    <script>
        // Document ready function
        document.addEventListener('DOMContentLoaded', function() {
            // Confirm before deleting
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });

            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    let valid = true;
                    const requiredFields = form.querySelectorAll('[required]');
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            valid = false;
                            field.style.borderColor = '#e74c3c';
                            field.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
                        } else {
                            field.style.borderColor = '#e1e8ed';
                            field.style.boxShadow = 'none';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        showNotification('Please fill in all required fields.', 'error');
                    }
                });
            });

            // Calculate total price in sales form
            const salesForm = document.getElementById('sales-form');
            if (salesForm) {
                const productSelect = salesForm.querySelector('#product');
                const quantityInput = salesForm.querySelector('#quantity');
                const priceDisplay = salesForm.querySelector('#price-display');
                const totalDisplay = salesForm.querySelector('#total-display');
                
                // Update price when product changes
                productSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const price = selectedOption.getAttribute('data-price') || 0;
                    const stock = selectedOption.getAttribute('data-stock') || 0;
                    
                    priceDisplay.textContent = `$${parseFloat(price).toFixed(2)}`;
                    
                    if (quantityInput.value) {
                        quantityInput.setAttribute('max', stock);
                        if (parseInt(quantityInput.value) > parseInt(stock)) {
                            quantityInput.value = stock;
                        }
                    }
                    
                    calculateTotal();
                });
                
                // Update total when quantity changes
                quantityInput.addEventListener('input', function() {
                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const stock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock')) : 0;
                    
                    if (parseInt(this.value) > stock) {
                        this.value = stock;
                        showNotification(`Maximum available quantity is ${stock}`, 'warning');
                    }
                    
                    calculateTotal();
                });
                
                function calculateTotal() {
                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) : 0;
                    const quantity = parseInt(quantityInput.value) || 0;
                    const total = price * quantity;
                    totalDisplay.textContent = `$${total.toFixed(2)}`;
                }
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Add smooth scrolling to all anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.style.animation = 'slideIn 0.5s ease';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Loading state for buttons
        function setLoadingState(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<span class="loading"></span> Loading...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
            }
        }

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        // Validate email
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Validate phone number
        function validatePhone(phone) {
            const re = /^[\+]?[1-9][\d]{0,15}$/;
            return re.test(phone.replace(/\s/g, ''));
        }
    </script>
</body>
</html>