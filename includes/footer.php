<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<script>
// Global functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all Bootstrap components
    initializeBootstrapComponents();
    
    // Initialize custom functionality
    initializeCustomFunctionality();
    
    // Initialize mobile sidebar
    initializeMobileSidebar();
    
    // Initialize sidebar toggles
    initializeSidebarToggles();
});

function initializeBootstrapComponents() {
    // Initialize all Bootstrap dropdowns
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    
    // Special handling for profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        try {
            const profileDropdownInstance = new bootstrap.Dropdown(profileDropdown, {
                boundary: 'viewport',
                display: 'dynamic'
            });
            profileDropdown.bootstrapDropdown = profileDropdownInstance;
            
            // Test if dropdown is working
            profileDropdown.addEventListener('shown.bs.dropdown', function() {
                // Dropdown shown successfully
            });
            
            profileDropdown.addEventListener('hidden.bs.dropdown', function() {
                // Dropdown hidden successfully
            });
            
        } catch (error) {
            // Fallback: manually handle dropdown
            addManualDropdownFunctionality(profileDropdown);
        }
    }
    
    // Initialize other dropdowns
    dropdownElementList.forEach((dropdown, index) => {
        // Initialize other dropdowns (not profile dropdown)
        if (dropdown.id !== 'profileDropdown') {
            try {
                const dropdownInstance = new bootstrap.Dropdown(dropdown);
            } catch (error) {
                addManualDropdownFunctionality(dropdown);
            }
        }
    });
    
    // Initialize all Bootstrap tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => {
        try {
            new bootstrap.Tooltip(tooltipTriggerEl);
        } catch (error) {
            // Silent fail
        }
    });
    
    // Initialize all Bootstrap popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(popoverTriggerEl => {
        try {
            new bootstrap.Popover(popoverTriggerEl);
        } catch (error) {
            // Silent fail
        }
    });
}

function addManualDropdownFunctionality(dropdownToggle) {
    const dropdownMenu = dropdownToggle.nextElementSibling;
    if (!dropdownMenu) return;
    
    // Ensure dropdown menu is properly styled
    dropdownMenu.style.position = 'absolute';
    dropdownMenu.style.top = '100%';
    dropdownMenu.style.right = '0';
    dropdownMenu.style.left = 'auto';
    dropdownMenu.style.zIndex = '1050';
    
    dropdownToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle dropdown visibility
        const isVisible = dropdownMenu.classList.contains('show');
        
        if (isVisible) {
            dropdownMenu.classList.remove('show');
            dropdownMenu.style.display = 'none';
        } else {
            dropdownMenu.classList.add('show');
            dropdownMenu.style.display = 'block';
            dropdownMenu.style.opacity = '1';
            dropdownMenu.style.visibility = 'visible';
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownToggle.contains(e.target)) {
            dropdownMenu.classList.remove('show');
            dropdownMenu.style.display = 'none';
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdownMenu.classList.remove('show');
            dropdownMenu.style.display = 'none';
        }
    });
    
    // Handle dropdown menu items
    const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Close dropdown after item click
            dropdownMenu.classList.remove('show');
            dropdownMenu.style.display = 'none';
        });
    });
}

function initializeCustomFunctionality() {
    // Auto-hide all alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (error) {
                    // Fallback: remove alert manually
                    alert.remove();
                }
            }
        }, 5000);
    });
    
    // Profile dropdown enhancement
    enhanceProfileDropdown();
    
    // Add fallback profile functionality
    addFallbackProfileFunctionality();
    
    // Make all clickable elements more responsive
    enhanceClickableElements();
}

function addFallbackProfileFunctionality() {
    // Add fallback for profile dropdown if Bootstrap fails
    const profileDropdown = document.getElementById('profileDropdown');
    if (!profileDropdown) return;
    
    // Add keyboard navigation
    profileDropdown.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
}

function enhanceProfileDropdown() {
    const profileDropdown = document.getElementById('profileDropdown');
    if (!profileDropdown) {
        return;
    }
    
    // Ensure Bootstrap dropdown is properly initialized
    try {
        const dropdown = new bootstrap.Dropdown(profileDropdown, {
            boundary: 'viewport',
            display: 'dynamic'
        });
        
        // Store dropdown instance for later use
        profileDropdown.bootstrapDropdown = dropdown;
    } catch (error) {
        // Fallback: manually handle dropdown
        addManualDropdownFunctionality(profileDropdown);
    }
    
    // Handle dropdown menu items
    const dropdownMenu = profileDropdown.nextElementSibling;
    if (dropdownMenu) {
        const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
        
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Close dropdown after item click
                try {
                    if (profileDropdown.bootstrapDropdown) {
                        profileDropdown.bootstrapDropdown.hide();
                    } else {
                        const dropdown = bootstrap.Dropdown.getInstance(profileDropdown);
                        if (dropdown) {
                            dropdown.hide();
                        }
                    }
                } catch (error) {
                    // Fallback: manually hide dropdown
                    if (dropdownMenu) {
                        dropdownMenu.classList.remove('show');
                    }
                }
            });
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!profileDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
            try {
                if (profileDropdown.bootstrapDropdown) {
                    profileDropdown.bootstrapDropdown.hide();
                } else {
                    const dropdown = bootstrap.Dropdown.getInstance(profileDropdown);
                    if (dropdown) {
                        dropdown.hide();
                    }
                }
            } catch (error) {
                // Fallback: manually hide dropdown
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show');
                }
            }
        }
    });
    
    // Add keyboard navigation
    profileDropdown.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
    
    // Ensure dropdown is visible when toggled
    profileDropdown.addEventListener('shown.bs.dropdown', function() {
        if (dropdownMenu) {
            dropdownMenu.style.display = 'block';
            dropdownMenu.style.opacity = '1';
            dropdownMenu.style.visibility = 'visible';
        }
    });
    
    // Handle dropdown hide event
    profileDropdown.addEventListener('hidden.bs.dropdown', function() {
        if (dropdownMenu) {
            dropdownMenu.style.display = 'none';
        }
    });
}

function enhanceClickableElements() {
    // Enhance all buttons with better click feedback
    const buttons = document.querySelectorAll('.btn, .nav-link, .dropdown-item');
    buttons.forEach(button => {
        // Add click feedback
        button.addEventListener('click', function(e) {
            // Add ripple effect
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = (e.clientX - this.offsetLeft) + 'px';
            ripple.style.top = (e.clientY - this.offsetTop) + 'px';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Add hover effects
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Enhance form inputs
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
}

function initializeMobileSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (!sidebarToggle || !sidebar || !sidebarOverlay) {
        return;
    }
    
    // Toggle sidebar on mobile
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });
    
    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });
    
    // Close sidebar when clicking on a link (mobile)
    const sidebarLinks = sidebar.querySelectorAll('.nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });
    
    // Close sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
}

function initializeSidebarToggles() {
    // Chevron rotation for sidebar toggles
    const sidebarToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            const chevron = this.querySelector('.bi-chevron-right');
            if (chevron) {
                // Toggle chevron rotation
                if (chevron.style.transform === 'rotate(90deg)') {
                    chevron.style.transform = 'rotate(0deg)';
                } else {
                    chevron.style.transform = 'rotate(90deg)';
                }
            }
        });
    });
    
    // Initialize chevron states for already expanded sections
    const expandedToggles = document.querySelectorAll('[aria-expanded="true"]');
    expandedToggles.forEach(toggle => {
        const chevron = toggle.querySelector('.bi-chevron-right');
        if (chevron) {
            chevron.style.transform = 'rotate(90deg)';
        }
    });
    
    // Listen for Bootstrap collapse events to update chevron rotation
    document.addEventListener('show.bs.collapse', function(e) {
        const toggle = e.target.previousElementSibling;
        if (toggle && toggle.hasAttribute('data-bs-toggle')) {
            const chevron = toggle.querySelector('.bi-chevron-right');
            if (chevron) {
                chevron.style.transform = 'rotate(90deg)';
            }
        }
    });
    
    document.addEventListener('hide.bs.collapse', function(e) {
        const toggle = e.target.previousElementSibling;
        if (toggle && toggle.hasAttribute('data-bs-toggle')) {
            const chevron = toggle.querySelector('.bi-chevron-right');
            if (chevron) {
                chevron.style.transform = 'rotate(0deg)';
            }
        }
    });
}
</script>

<style>
/* Ripple effect animation */
@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Enhanced focus states */
.focused input,
.focused select,
.focused textarea {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
}

/* Better button states */
.btn:active {
    transform: translateY(1px);
}

/* Enhanced dropdown styling */
.dropdown-menu.show {
    animation: dropdownFadeIn 0.2s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>