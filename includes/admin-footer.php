        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            });
        }, 5000);
        
        // Confirm delete actions
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Confirm block/unblock actions
        document.querySelectorAll('.btn-block').forEach(button => {
            button.addEventListener('click', function(e) {
                const action = this.dataset.action;
                if (!confirm(`Are you sure you want to ${action} this user?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
