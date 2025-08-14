<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Simple and safe chevron rotation
    const sidebarToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    sidebarToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Prevent any text changes
            e.preventDefault();
            
            const chevron = this.querySelector('.bi-chevron-right, .bi-chevron-down');
            if (chevron) {
                // Only rotate the icon, don't change classes
                if (chevron.classList.contains('bi-chevron-right')) {
                    chevron.style.transform = 'rotate(90deg)';
                } else {
                    chevron.style.transform = 'rotate(0deg)';
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
});
</script>
</body>
</html>