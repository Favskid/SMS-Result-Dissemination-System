        <!-- End content-area -->
    </div><!-- end #main -->
</div>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle mobile sidebar
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
    }
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth < 768 && !sidebar.contains(e.target)
            && !e.target.closest('[onclick="toggleSidebar()"]')) {
            sidebar.classList.remove('show');
        }
    });
</script>
</body>
</html>
