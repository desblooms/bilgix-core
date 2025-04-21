/**
 * Enhanced Sales Dashboard Functionality
 * Modern UI interactions and animations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize app components
    initializeUI();
    setupEventListeners();
    applyStyleEnhancements();
    initializeToastSystem();
});

/**
 * Initialize UI components and apply dynamic classes
 */
function initializeUI() {
    // Add classes to sales cards
    const salesCards = document.querySelectorAll('.grid-cols-2 > div');
    if (salesCards.length >= 2) {
        salesCards[0].classList.add('sales-stat-card', 'today-sales', 'fade-in');
        salesCards[1].classList.add('sales-stat-card', 'monthly-sales', 'fade-in');
    }
    
    // Add classes to dashboard sections
    const dashboardSections = document.querySelectorAll('.bg-white.rounded-lg.shadow');
    dashboardSections.forEach(section => {
        section.classList.add('dashboard-card', 'fade-in');
    });
    
    // Add classes to quick action buttons
    const quickActions = document.querySelectorAll('.grid-cols-3 > a');
    if (quickActions.length >= 3) {
        quickActions[0].classList.add('quick-action', 'new-sale');
        quickActions[1].classList.add('quick-action', 'add-product');
        quickActions[2].classList.add('quick-action', 'reports');
    }
    
    // Add classes to low stock alert
    const lowStockAlert = document.querySelector('h3.text-lg.font-medium.p-4.border-b.text-red-600')?.closest('.bg-white.rounded-lg.shadow');
    if (lowStockAlert) {
        lowStockAlert.classList.add('low-stock-alert');
    }
    
    // Add classes to recent sales
    const recentSales = document.querySelector('h3.text-lg.font-medium')?.closest('.bg-white.rounded-lg.shadow');
    if (recentSales) {
        recentSales.classList.add('recent-sales');
        const header = recentSales.querySelector('.flex.justify-between.items-center.p-4.border-b');
        if (header) header.classList.add('header');
    }
    
    // Enhance bottom navigation
    const bottomNav = document.querySelector('.bottom-nav');
    if (bottomNav) {
        const newSaleBtn = bottomNav.querySelector('.bg-teal-950.text-white.rounded-full');
        if (newSaleBtn) {
            newSaleBtn.classList.add('new-sale-btn');
            
            // Create animated plus icon
            const plusIcon = newSaleBtn.querySelector('i.fas.fa-plus');
            if (plusIcon) {
                const plusAnimation = document.createElement('div');
                plusAnimation.className = 'plus-animation';
                newSaleBtn.replaceChild(plusAnimation, plusIcon);
            }
        }
        
        // Set active state for current page
        const currentPath = window.location.pathname;
        const navLinks = bottomNav.querySelectorAll('a');
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath || 
                (currentPath === '/' && link.getAttribute('href') === 'index.php')) {
                link.classList.add('active');
            }
        });
    }
}

/**
 * Set up event listeners for interactive elements
 */
function setupEventListeners() {
    // Side menu functionality (already in the original code)
    const menuButton = document.getElementById('menuButton');
    const closeMenu = document.getElementById('closeMenu');
    const sideMenu = document.getElementById('sideMenu');
    
    if (menuButton && closeMenu && sideMenu) {
        menuButton.addEventListener('click', () => {
            sideMenu.classList.remove('hidden');
            sideMenu.querySelector('div').classList.remove('-translate-x-full');
        });
        
        closeMenu.addEventListener('click', () => {
            sideMenu.querySelector('div').classList.add('-translate-x-full');
            setTimeout(() => {
                sideMenu.classList.add('hidden');
            }, 300);
        });
        
        sideMenu.addEventListener('click', (e) => {
            if (e.target === sideMenu) {
                sideMenu.querySelector('div').classList.add('-translate-x-full');
                setTimeout(() => {
                    sideMenu.classList.add('hidden');
                }, 300);
            }
        });
    }
    
    // Interactions for quick action buttons
    const quickActions = document.querySelectorAll('.quick-action');
    quickActions.forEach(action => {
        action.addEventListener('mouseenter', () => {
            const icon = action.querySelector('i');
            if (icon) icon.classList.add('animated-icon');
        });
        
        action.addEventListener('mouseleave', () => {
            const icon = action.querySelector('i');
            if (icon) icon.classList.remove('animated-icon');
        });
    });
}

/**
 * Apply fade-in animations on page load
 */
function applyStyleEnhancements() {
    // Apply entrance animations with staggered delay
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s forwards`;
    });
    
    // Apply entrance animations to list items
    const listItems = document.querySelectorAll('li');
    listItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.animation = `fadeIn 0.3s ease-out ${index * 0.05 + 0.3}s forwards`;
    });
}

/**
 * Toast notification system
 */
function initializeToastSystem() {
    // Create toast container if it doesn't exist
    if (!document.querySelector('.toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    // Add global method to show toast notifications
    window.showToast = function(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                          type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
        
        toast.innerHTML = `
            <i class="fas ${iconClass} toast-icon"></i>
            <div class="toast-content">
                <p>${message}</p>
            </div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        document.querySelector('.toast-container').appendChild(toast);
        
        // Show toast with animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Set up close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        });
        
        // Auto close after duration
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 300);
            }
        }, duration);
    };
    
    // Example usage (uncomment to test):
    // setTimeout(() => {
    //     window.showToast('Welcome to your Sales Dashboard!', 'success');
    // }, 1000);
}

/**
 * Sales statistics counter animation
 * Creates a counting animation effect for sales figures
 */
function animateCounters() {
    const counters = document.querySelectorAll('.text-2xl.font-bold');
    
    counters.forEach(counter => {
        // Get the target value
        const text = counter.textContent;
        const value = parseFloat(text.replace(/[^0-9.-]+/g, ''));
        
        if (!isNaN(value)) {
            const prefix = text.substring(0, text.indexOf(value.toString().charAt(0)));
            const suffix = text.substring(text.indexOf(value.toString()) + value.toString().length);
            
            // Set start value to 0
            counter.textContent = prefix + '0' + suffix;
            
            // Create animation
            let startValue = 0;
            const duration = 1500;
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsedTime = currentTime - startTime;
                const progress = Math.min(elapsedTime / duration, 1);
                
                // Use easeOutExpo for more natural animation
                const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const currentValue = Math.floor(easeProgress * value);
                
                counter.textContent = prefix + currentValue.toLocaleString() + suffix;
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = text; // Ensure final value is exact
                }
            }
            
            requestAnimationFrame(updateCounter);
        }
    });
}

// Call this after a small delay to let the page render
setTimeout(animateCounters, 300);