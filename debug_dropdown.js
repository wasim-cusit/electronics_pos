// Debug script for profile dropdown
console.log('Debug script loaded');

function debugProfileDropdown() {
    const profileDropdown = document.getElementById('profileDropdown');
    if (!profileDropdown) {
        console.error('Profile dropdown element not found');
        return;
    }
    
    console.log('Profile dropdown element found:', profileDropdown);
    console.log('Profile dropdown classes:', profileDropdown.className);
    console.log('Profile dropdown attributes:', {
        'data-bs-toggle': profileDropdown.getAttribute('data-bs-toggle'),
        'aria-expanded': profileDropdown.getAttribute('aria-expanded'),
        'aria-haspopup': profileDropdown.getAttribute('aria-haspopup')
    });
    
    // Check dropdown menu
    const dropdownMenu = profileDropdown.nextElementSibling;
    if (dropdownMenu) {
        console.log('Dropdown menu found:', dropdownMenu);
        console.log('Dropdown menu classes:', dropdownMenu.className);
        console.log('Dropdown menu display:', dropdownMenu.style.display);
        console.log('Dropdown menu position:', dropdownMenu.style.position);
        console.log('Dropdown menu z-index:', dropdownMenu.style.zIndex);
    } else {
        console.error('No dropdown menu found');
    }
    
    // Check Bootstrap
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap is loaded');
        try {
            const dropdown = bootstrap.Dropdown.getInstance(profileDropdown);
            if (dropdown) {
                console.log('Bootstrap dropdown instance found');
            } else {
                console.log('No Bootstrap dropdown instance found');
            }
        } catch (error) {
            console.error('Error checking Bootstrap dropdown:', error);
        }
    } else {
        console.error('Bootstrap is not loaded');
    }
    
    // Test click functionality
    profileDropdown.addEventListener('click', function(e) {
        console.log('Profile dropdown clicked');
        console.log('Event target:', e.target);
        console.log('Event currentTarget:', e.currentTarget);
        
        // Check if dropdown menu is visible
        if (dropdownMenu) {
            const isVisible = dropdownMenu.classList.contains('show');
            console.log('Dropdown menu visible:', isVisible);
            console.log('Dropdown menu display style:', dropdownMenu.style.display);
            console.log('Dropdown menu classes after click:', dropdownMenu.className);
        }
    });
    
    // Test manual toggle
    window.testDropdownToggle = function() {
        if (dropdownMenu) {
            const isVisible = dropdownMenu.classList.contains('show');
            if (isVisible) {
                dropdownMenu.classList.remove('show');
                dropdownMenu.style.display = 'none';
                console.log('Dropdown manually hidden');
            } else {
                dropdownMenu.classList.add('show');
                dropdownMenu.style.display = 'block';
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.visibility = 'visible';
                console.log('Dropdown manually shown');
            }
        }
    };
    
    console.log('Use testDropdownToggle() to manually test the dropdown');
}

// Run debug when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, running dropdown debug...');
    debugProfileDropdown();
});

// Also run when page is fully loaded
window.addEventListener('load', function() {
    console.log('Page fully loaded, running dropdown debug...');
    debugProfileDropdown();
});
