    </div><!-- /.main-content -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('.datatable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
            
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
            
            // Sidebar toggle
            $('#sidebarToggle').click(function() {
                $('.sidebar').toggleClass('d-none');
                $('.main-content').toggleClass('ms-0');
            });
            
            // Sidebar dropdown persistence
            $('.sidebar-dropdown-toggle').on('click', function(e) {
                const targetId = $(this).attr('href');
                const isExpanded = $(targetId).hasClass('show');
                
                // Save state to localStorage
                if (isExpanded) {
                    localStorage.removeItem('sidebar_' + targetId.substring(1));
                } else {
                    localStorage.setItem('sidebar_' + targetId.substring(1), 'open');
                }
            });
            
            // Restore sidebar dropdown states from localStorage
            $('.sidebar-dropdown-menu').each(function() {
                const menuId = $(this).attr('id');
                const isOpen = localStorage.getItem('sidebar_' + menuId) === 'open';
                
                if (isOpen) {
                    $(this).addClass('show');
                    $('[href="#' + menuId + '"]').removeClass('collapsed').attr('aria-expanded', 'true');
                }
            });
            
            // Highlight active menu item
            const currentPage = window.location.pathname.split('/').pop();
            $('.sidebar-dropdown-menu .nav-link').each(function() {
                const linkHref = $(this).attr('href');
                if (linkHref === currentPage) {
                    $(this).addClass('active');
                    // Expand parent dropdown
                    $(this).closest('.sidebar-dropdown-menu').addClass('show');
                    $(this).closest('.sidebar-dropdown-menu').prev('.sidebar-dropdown-toggle')
                        .removeClass('collapsed').attr('aria-expanded', 'true');
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        });
        
        // Confirm delete
        function confirmDelete(message = 'Are you sure you want to delete this?') {
            return confirm(message);
        }
        
        // Show loading
        function showLoading() {
            $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        }
        
        function hideLoading() {
            $('.loading-overlay').remove();
        }
    </script>
    
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    </style>
</body>
</html>
