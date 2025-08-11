// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
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
                    field.style.borderColor = 'red';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
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
            const price = selectedOption.getAttribute('data-price');
            priceDisplay.textContent = `$${parseFloat(price).toFixed(2)}`;
            calculateTotal();
        });
        
        // Update total when quantity changes
        quantityInput.addEventListener('input', calculateTotal);
        
        function calculateTotal() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.getAttribute('data-price')) : 0;
            const quantity = parseInt(quantityInput.value) || 0;
            const total = price * quantity;
            totalDisplay.textContent = `$${total.toFixed(2)}`;
        }
    }
});

// Responsive helpers: ensure any .table without a wrapper becomes scrollable on small screens
document.addEventListener('DOMContentLoaded', function() {
  const tables = document.querySelectorAll('table.table');
  tables.forEach(function(tbl) {
    // If not already inside a .table-responsive, wrap it
    if (!tbl.parentElement || !tbl.parentElement.classList.contains('table-responsive')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'table-responsive';
      tbl.parentNode.insertBefore(wrapper, tbl);
      wrapper.appendChild(tbl);
    }
  });
});