// Function to preview image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            
            // Remove placeholder style if exists
            const previewContainer = preview.parentElement;
            if (previewContainer.classList.contains('receipt-preview-placeholder')) {
                previewContainer.classList.remove('receipt-preview-placeholder');
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Add event listener to file inputs
document.addEventListener('DOMContentLoaded', function() {
    const receiptImageInput = document.getElementById('receipt_image');
    if (receiptImageInput) {
        receiptImageInput.addEventListener('change', function() {
            previewImage(this, 'receipt-preview');
        });
    }
    
    // Initialize receipt preview container if exists
    const previewContainer = document.querySelector('.receipt-preview-container');
    const preview = document.getElementById('receipt-preview');
    if (previewContainer && preview && preview.classList.contains('hidden')) {
        previewContainer.classList.add('receipt-preview-placeholder');
    }
    
    // Add animation to tier progress bars
    const tierProgressBars = document.querySelectorAll('.tier-progress-bar');
    if (tierProgressBars.length > 0) {
        tierProgressBars.forEach(bar => {
            bar.classList.add('relative', 'overflow-hidden');
        });
    }
    
    // Add form validation for donation form
    const donationForm = document.querySelector('form[action="process_donation.php"]');
    if (donationForm) {
        donationForm.addEventListener('submit', function(e) {
            const amount = document.getElementById('amount').value;
            const paymentMethod = document.getElementById('payment_method').value;
            const referenceNumber = document.getElementById('reference_number').value;
            const receiptImage = document.getElementById('receipt_image').files[0];
            
            let isValid = true;
            let errorMessages = [];
            
            if (!amount || parseFloat(amount) <= 0) {
                isValid = false;
                errorMessages.push('Please enter a valid donation amount');
            }
            
            if (!paymentMethod) {
                isValid = false;
                errorMessages.push('Please select a payment method');
            }
            
            if (!referenceNumber) {
                isValid = false;
                errorMessages.push('Please enter a reference number');
            }
            
            if (!receiptImage) {
                isValid = false;
                errorMessages.push('Please upload a receipt screenshot');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n' + errorMessages.join('\n'));
            }
        });
    }
    
    // Submit button effect
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    if (submitButtons.length > 0) {
        submitButtons.forEach(button => {
            button.classList.add('donation-submit-btn');
        });
    }
});

// Format currency input
function formatCurrency(input) {
    // Get input value
    let value = input.value.replace(/[^\d]/g, '');
    
    // Format with peso sign and commas
    if (value) {
        // Convert to number and format
        value = parseFloat(value) / 100;
        value = '₱' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // Update input value
    input.value = value;
}

// Handle print and export functions for reports
function printReport() {
    window.print();
}

// Function to toggle password visibility
function togglePasswordVisibility(inputId, toggleId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleId);
    
    if (passwordInput && toggleButton) {
        // Toggle password visibility
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle icon
        const eyeIcon = toggleButton.querySelector('svg');
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            `;
        }
    }
}

// Initialize password toggles when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners for password toggle buttons
    document.querySelectorAll('[id^="toggle-"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const inputId = this.id.replace('toggle-', '');
            togglePasswordVisibility(inputId, this.id);
        });
    });
});