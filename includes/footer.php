<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
// Global notification auto-hide functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide all alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Mobile sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle && sidebar && sidebarOverlay) {
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
});
</script>
</body>
</html>