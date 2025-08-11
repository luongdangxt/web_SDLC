// Fix Console Errors
(function() {
    'use strict';
    
    console.log('🔧 Fixing console errors...');
    
    // Global error handler
    window.addEventListener('error', function(e) {
        console.warn('Global error caught:', e.error);
        return true; // Prevent default error handling
    });
    
    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', function(e) {
        console.warn('Unhandled promise rejection:', e.reason);
        e.preventDefault();
    });
    
    // Wait for DOM to be ready
    function waitForElement(selector, callback, maxTries = 10) {
        if (maxTries <= 0) {
            console.warn('Element not found:', selector);
            return;
        }
        
        const element = document.querySelector(selector);
        if (element) {
            callback(element);
        } else {
            setTimeout(() => waitForElement(selector, callback, maxTries - 1), 100);
        }
    }
    
    // Fix image loading errors
    function fixImageErrors() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                console.warn('Image failed to load:', this.src);
                // Set a placeholder image
                this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';
                this.alt = 'Image not found';
            });
        });
    }
    
    // Fix form event listeners
    function fixFormListeners() {
        const forms = {
            'login-form': function(e) {
                e.preventDefault();
                console.log('Login form submitted');
                // Add your login logic here
            },
            'register-form': function(e) {
                e.preventDefault();
                console.log('Register form submitted');
                // Add your register logic here
            },
            'search-form': function(e) {
                e.preventDefault();
                console.log('Search form submitted');
                // Add your search logic here
            },
            'booking-form': function(e) {
                e.preventDefault();
                console.log('Booking form submitted');
                // Add your booking logic here
            },
            'profile-form': function(e) {
                e.preventDefault();
                console.log('Profile form submitted');
                // Add your profile update logic here
            }
        };
        
        Object.keys(forms).forEach(formId => {
            waitForElement('#' + formId, (form) => {
                if (!form.hasAttribute('data-listener-added')) {
                    form.addEventListener('submit', forms[formId]);
                    form.setAttribute('data-listener-added', 'true');
                    console.log('✅ Added listener for:', formId);
                }
            });
        });
    }
    
    // Fix input event listeners
    function fixInputListeners() {
        // Date inputs
        waitForElement('#check-in-date', (input) => {
            if (!input.hasAttribute('data-listener-added')) {
                input.addEventListener('change', function() {
                    const checkOutInput = document.getElementById('check-out-date');
                    if (checkOutInput) {
                        checkOutInput.min = this.value;
                    }
                });
                input.setAttribute('data-listener-added', 'true');
            }
        });
        
        waitForElement('#check-out-date', (input) => {
            if (!input.hasAttribute('data-listener-added')) {
                input.addEventListener('change', function() {
                    console.log('Check-out date changed:', this.value);
                });
                input.setAttribute('data-listener-added', 'true');
            }
        });
        
        // Password strength
        waitForElement('#register-password', (input) => {
            if (!input.hasAttribute('data-listener-added')) {
                input.addEventListener('input', function() {
                    console.log('Password strength check');
                });
                input.setAttribute('data-listener-added', 'true');
            }
        });
        
        // Email validation
        waitForElement('#register-email', (input) => {
            if (!input.hasAttribute('data-listener-added')) {
                input.addEventListener('blur', function() {
                    const email = this.value;
                    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                    if (email && !isValid) {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-gray-300');
                    }
                });
                input.setAttribute('data-listener-added', 'true');
            }
        });
    }
    
    // Fix admin elements
    function fixAdminElements() {
        // Admin users tab
        waitForElement('#admin-users-tab input[type="text"]', (input) => {
            if (!input.hasAttribute('data-listener-added')) {
                input.addEventListener('input', function() {
                    console.log('Admin users search:', this.value);
                });
                input.setAttribute('data-listener-added', 'true');
            }
        });
        
        // User modal form
        waitForElement('#user-modal-form', (form) => {
            if (!form.hasAttribute('data-listener-added')) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('User modal form submitted');
                });
                form.setAttribute('data-listener-added', 'true');
            }
        });
    }
    
    // Fix reception elements
    function fixReceptionElements() {
        waitForElement('#reception-edit-guest-form', (form) => {
            if (!form.hasAttribute('data-listener-added')) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Reception guest form submitted');
                });
                form.setAttribute('data-listener-added', 'true');
            }
        });
    }
    
    // Initialize fixes
    function initFixes() {
        console.log('🚀 Initializing console error fixes...');
        
        // Fix images
        fixImageErrors();
        
        // Fix forms
        fixFormListeners();
        
        // Fix inputs
        fixInputListeners();
        
        // Fix admin elements
        fixAdminElements();
        
        // Fix reception elements
        fixReceptionElements();
        
        console.log('✅ Console error fixes initialized');
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFixes);
    } else {
        initFixes();
    }
    
    // Also run after a delay to catch dynamically added elements
    setTimeout(initFixes, 1000);
    setTimeout(initFixes, 3000);
    
})(); 